<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

class SanitizeProviderAddresses extends Command
{
    protected $signature = 'providers:sanitize-addresses
        {--apply : Persiste las provincias y el país normalizados}';

    protected $description = 'Audita y normaliza provincia y país de proveedores con ubicación válida.';

    public function handle(): int
    {
        try {
            $provinceByCity = $this->loadProvinceMap();
        } catch (JsonException $exception) {
            $this->error('No se pudo leer el catálogo de ciudades: '.$exception->getMessage());

            return self::FAILURE;
        }

        $providerIds = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->pluck('id');
        $addresses = UserAddress::query()
            ->whereIn('user_id', $providerIds)
            ->orderBy('user_id')
            ->get();

        $incomplete = 0;
        $unresolved = [];
        $changes = [];

        foreach ($addresses as $address) {
            if (! $this->hasValidCoordinates($address) || trim((string) $address->city) === '') {
                $incomplete++;

                continue;
            }

            $cityKey = $this->normalizeLabel((string) $address->city);
            $province = $provinceByCity[$cityKey] ?? null;
            if ($province === null) {
                $unresolved[$cityKey] = trim((string) $address->city);

                continue;
            }

            if (trim((string) $address->province) !== $province || trim((string) $address->country) !== 'España') {
                $changes[] = [
                    'address' => $address,
                    'province' => $province,
                ];
            }
        }

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Proveedores', $providerIds->count()],
                ['Direcciones', $addresses->count()],
                ['Perfiles incompletos no publicables', $incomplete],
                ['Ciudades sin resolver', count($unresolved)],
                ['Direcciones por normalizar', count($changes)],
            ]
        );

        if ($unresolved !== []) {
            $this->error('Existen ciudades con coordenadas sin provincia resuelta:');
            foreach ($unresolved as $city) {
                $this->line('- '.$city);
            }

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->comment('Simulación completada. Ejecuta con --apply para persistir los cambios.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes): void {
            foreach ($changes as $change) {
                /** @var UserAddress $address */
                $address = $change['address'];
                $address->province = $change['province'];
                $address->state = $change['province'];
                $address->country = 'España';
                $address->save();
            }
        });

        $this->info('Saneamiento aplicado correctamente: '.count($changes).' direcciones actualizadas.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     *
     * @throws JsonException
     */
    private function loadProvinceMap(): array
    {
        $json = file_get_contents(database_path('data/provider_city_provinces.json'));
        $mapping = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);

        return collect($mapping)
            ->mapWithKeys(fn (string $province, string $city): array => [
                $this->normalizeLabel($city) => $province,
            ])
            ->all();
    }

    private function hasValidCoordinates(UserAddress $address): bool
    {
        $latitude = filter_var($address->latitude, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($address->longitude, FILTER_VALIDATE_FLOAT);

        return $latitude !== false
            && $longitude !== false
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }

    private function normalizeLabel(string $value): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value)));
    }
}
