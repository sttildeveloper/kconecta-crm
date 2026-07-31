<?php

namespace App\Console\Commands;

use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ProviderServiceTypeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportProviderCsv extends Command
{
    protected $signature = 'providers:export-csv
        {--path= : Ruta destino del CSV}';

    protected $description = 'Exporta los proveedores de servicios al formato CSV esperado por el importador.';

    public function handle(ProviderServiceTypeService $providerServiceTypeService): int
    {
        $defaultPath = storage_path('app/provider-exports/providers_export_' . now()->format('Ymd_His') . '.csv');
        $targetPath = (string) ($this->option('path') ?: $defaultPath);
        $directory = dirname($targetPath);

        if (! is_dir($directory)) {
            File::ensureDirectoryExists($directory);
        }

        $providers = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->orderBy('id')
            ->get();

        $providerIds = $providers->pluck('id')->map(fn ($id) => (int) $id)->all();
        $addresses = empty($providerIds)
            ? collect()
            : UserAddress::query()
                ->whereIn('user_id', $providerIds)
                ->get()
                ->keyBy('user_id');

        $typeIdsByProvider = $providerServiceTypeService->typeIdsForProviders($providerIds);
        $typeNamesById = ServiceType::query()
            ->pluck('name', 'id')
            ->map(fn ($name) => trim((string) $name))
            ->all();

        $handle = fopen($targetPath, 'wb');
        if (! $handle) {
            $this->error('No se pudo crear el archivo: ' . $targetPath);

            return self::FAILURE;
        }

        fputcsv($handle, [
            'nombre_razon_social',
            'direccion',
            'whatsapp',
            'landing_phone',
            'email',
            'tipos_servicios',
            'categoria',
            'ciudad',
        ]);

        foreach ($providers as $provider) {
            $address = $addresses->get((int) $provider->id);
            $typeIds = $typeIdsByProvider->get((int) $provider->id, []);
            $typeNames = collect($typeIds)
                ->map(fn ($id) => $typeNamesById[(int) $id] ?? null)
                ->filter()
                ->unique()
                ->values();

            $companyName = trim((string) ($provider->user_name ?: trim(($provider->first_name ?? '') . ' ' . ($provider->last_name ?? ''))));
            $addressValue = trim((string) ($address?->address ?: $provider->address ?: ''));
            $cityValue = trim((string) ($address?->city ?: ''));
            $categoryValue = (string) ($typeNames->first() ?? trim((string) ($provider->provider_title ?? '')));

            fputcsv($handle, [
                $this->csvValue($companyName),
                $this->csvValue($addressValue),
                $this->csvValue((string) ($provider->phone ?? '')),
                $this->csvValue((string) ($provider->landline_phone ?? '')),
                $this->csvValue((string) ($provider->email ?? '')),
                $this->csvValue($typeNames->implode('; ')),
                $this->csvValue($categoryValue),
                $this->csvValue($cityValue),
            ]);
        }

        fclose($handle);

        $this->info('CSV generado correctamente.');
        $this->line('Archivo: ' . $targetPath);
        $this->line('Registros exportados: ' . $providers->count());

        return self::SUCCESS;
    }

    private function csvValue(string $value): string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? 'null' : $trimmed;
    }
}
