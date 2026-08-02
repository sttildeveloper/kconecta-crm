<?php

namespace App\Services;

use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProviderCsvImportService
{
    private ?array $cityProvinceMap = null;

    public function analyzeFile(string $filePath, bool $commit = false, bool $updateExisting = false): array
    {
        if (! is_file($filePath)) {
            throw new \RuntimeException("No se encontro el archivo: {$filePath}");
        }

        $rows = $this->readCsv($filePath);
        $types = ServiceType::query()->orderBy('name')->get(['id', 'name']);
        $typeIndex = $this->buildServiceTypeIndex($types->all());

        $hasIsActive = Schema::hasColumn('user', 'is_active');
        $hasEmailVerifiedAt = Schema::hasColumn('user', 'email_verified_at');
        $hasProviderTitle = Schema::hasColumn('user', 'provider_title');
        $hasProviderDescription = Schema::hasColumn('user', 'provider_description');
        $hasProviderPageUrl = Schema::hasColumn('user', 'provider_page_url');
        $hasProviderAvailability = Schema::hasColumn('user', 'provider_availability');

        $summary = [
            'rows' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'unmapped' => 0,
            'missing_coordinates' => 0,
            'missing_province' => 0,
            'errors' => 0,
            'blocked' => false,
        ];

        $report = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $normalized = $this->normalizeCsvRow($row, $line);
            $typeResolution = $this->resolveTypeIds($normalized, $typeIndex);
            $normalized['type_ids'] = $typeResolution['matched_ids'];
            $normalized['missing_type_labels'] = $typeResolution['missing_labels'];

            $existing = $this->findExistingProvider($normalized);
            $action = $existing ? ($updateExisting ? 'update' : 'conflict') : 'create';
            $normalized['action_summary'] = $this->buildActionSummary($normalized, $existing, $action);

            $issues = [];
            if ($normalized['company_name'] === '') {
                $issues[] = 'Nombre / razon social vacio';
            }
            if (empty($normalized['type_ids']) && ! $existing) {
                $issues[] = 'Sin especialidades mapeadas';
            }
            foreach ($normalized['missing_type_labels'] as $missingLabel) {
                if (! $existing) {
                    $issues[] = "Especialidad sin catalogo: {$missingLabel}";
                }
            }
            if (($normalized['latitude'] ?? null) === null || ($normalized['longitude'] ?? null) === null) {
                $issues[] = 'Faltan coordenadas';
            }
            $effectiveProvince = $normalized['province'] ?? null;
            if ($effectiveProvince === null && $existing && $updateExisting) {
                $effectiveProvince = UserAddress::query()
                    ->where('user_id', (int) $existing->id)
                    ->value('province');
            }
            if (($normalized['latitude'] ?? null) !== null
                && ($normalized['longitude'] ?? null) !== null
                && ! $this->isValidProvince($effectiveProvince)) {
                $issues[] = 'Provincia no resuelta para proveedor con coordenadas';
            }
            if ($action === 'conflict') {
                $issues[] = 'Proveedor existente detectado; usa --update-existing para sincronizarlo';
            }

            if (! empty($issues)) {
                if ($action === 'conflict') {
                    $summary['conflicts']++;
                } elseif (in_array('Faltan coordenadas', $issues, true)) {
                    $summary['missing_coordinates']++;
                    $summary['blocked'] = true;
                } elseif (in_array('Provincia no resuelta para proveedor con coordenadas', $issues, true)) {
                    $summary['missing_province']++;
                    $summary['blocked'] = true;
                } elseif (empty($normalized['type_ids'])) {
                    $summary['unmapped']++;
                } else {
                    $summary['errors']++;
                }
                $summary['skipped']++;
                $report[] = $this->formatReportRow($normalized, $action, 'skip', $issues, $existing?->id);

                continue;
            }

            if (! $commit) {
                $report[] = $this->formatReportRow($normalized, $action, 'dry-run', [], $existing?->id);
                if ($action === 'update') {
                    $summary['updated']++;
                } else {
                    $summary['created']++;
                }

                continue;
            }

            try {
                $user = DB::transaction(function () use (
                    $normalized,
                    $existing,
                    $updateExisting,
                    $hasIsActive,
                    $hasEmailVerifiedAt,
                    $hasProviderTitle,
                    $hasProviderDescription,
                    $hasProviderPageUrl,
                    $hasProviderAvailability
                ) {
                    if ($existing && $updateExisting) {
                        $payload = [
                            'first_name' => $normalized['first_name'],
                            'last_name' => null,
                            'user_name' => $normalized['company_name'],
                            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
                        ];

                        if ($normalized['phone'] !== null) {
                            $payload['phone'] = $normalized['phone'];
                        }
                        if ($normalized['landline_phone'] !== null) {
                            $payload['landline_phone'] = $normalized['landline_phone'];
                        }
                        if ($normalized['address_full'] !== null) {
                            $payload['address'] = $normalized['address_full'];
                        }
                        if ($normalized['email'] !== null) {
                            $payload['email'] = $normalized['email'];
                        }
                        if ($hasProviderTitle && $normalized['provider_title'] !== '') {
                            $payload['provider_title'] = $normalized['provider_title'];
                        }
                        if ($hasProviderDescription && $normalized['provider_description'] !== null) {
                            $payload['provider_description'] = $normalized['provider_description'];
                        }
                        if ($hasIsActive) {
                            $payload['is_active'] = 1;
                        }

                        $existing->fill($payload);
                        $existing->save();
                        $user = $existing;
                    } else {
                        $payload = [
                            'first_name' => $normalized['first_name'],
                            'last_name' => null,
                            'user_name' => $normalized['company_name'],
                            'phone' => $normalized['phone'],
                            'landline_phone' => $normalized['landline_phone'],
                            'address' => $normalized['address_full'],
                            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
                            'email' => $normalized['email'],
                        ];

                        if ($hasProviderTitle) {
                            $payload['provider_title'] = $normalized['provider_title'];
                        }

                        if ($hasProviderDescription) {
                            $payload['provider_description'] = $normalized['provider_description'];
                        }

                        if ($hasProviderPageUrl) {
                            $payload['provider_page_url'] = null;
                        }

                        if ($hasProviderAvailability) {
                            $payload['provider_availability'] = null;
                        }

                        if ($hasIsActive) {
                            $payload['is_active'] = 1;
                        }

                        $payload['password'] = Hash::make(Str::random(32));

                        if ($hasEmailVerifiedAt) {
                            $payload['email_verified_at'] = null;
                        }

                        $user = User::query()->create($payload);
                    }

                    $address = UserAddress::query()->firstOrNew([
                        'user_id' => (int) $user->id,
                    ]);

                    $address->address = $normalized['address_full'] ?? ($existing && $updateExisting ? $address->address : null);
                    $address->street_name = null;
                    $address->street_number = null;
                    $address->neighborhood = null;
                    $address->city = $normalized['city'] ?? ($existing && $updateExisting ? $address->city : null);
                    $address->province = $normalized['province'] ?? ($existing && $updateExisting ? $address->province : null);
                    $address->postal_code = $normalized['postal_code'] ?? ($existing && $updateExisting ? $address->postal_code : null);
                    $address->state = $normalized['province'] ?? ($existing && $updateExisting ? $address->state : null);
                    $address->country = $normalized['country'] ?? ($existing && $updateExisting ? $address->country : null);
                    $address->latitude = $normalized['latitude'] ?? ($existing && $updateExisting ? $address->latitude : null);
                    $address->longitude = $normalized['longitude'] ?? ($existing && $updateExisting ? $address->longitude : null);
                    $address->additional_info = null;
                    $address->save();

                    if (! empty($normalized['type_ids']) || ! ($existing && $updateExisting)) {
                        app(ProviderServiceTypeService::class)->syncForProvider((int) $user->id, $normalized['type_ids']);
                    }

                    return $user;
                });

                $report[] = $this->formatReportRow($normalized, $action, 'committed', [], (int) $user->id);
                if ($action === 'update') {
                    $summary['updated']++;
                } else {
                    $summary['created']++;
                }
            } catch (\Throwable $e) {
                $summary['errors']++;
                $summary['skipped']++;
                $report[] = $this->formatReportRow($normalized, $action, 'error', [$e->getMessage()], $existing?->id);
            }
        }

        return [
            'summary' => $summary,
            'report' => $report,
        ];
    }

    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$filePath}");
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($value) => $this->sanitizeCsvCell((string) $value), $header);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $this->sanitizeCsvCell((string) ($data[$index] ?? ''));
            }

            if (collect($row)->every(fn ($value) => $value === '')) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function buildServiceTypeIndex(array $types): array
    {
        $index = [
            'by_normalized' => [],
            'rows' => [],
        ];

        foreach ($types as $type) {
            $normalized = $this->normalizeLabel((string) $type->name);
            $index['by_normalized'][$normalized] = (int) $type->id;
            $index['rows'][] = [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
                'normalized' => $normalized,
            ];
        }

        return $index;
    }

    private function normalizeCsvRow(array $row, int $line): array
    {
        $companyName = $this->cleanSpaces((string) ($row['nombre_razon_social'] ?? ''));
        $address = $this->cleanSpaces((string) ($this->normalizeNullableField((string) ($row['direccion'] ?? '')) ?? ''));
        $category = $this->cleanSpaces((string) ($this->normalizeNullableField((string) ($row['categoria'] ?? '')) ?? ''));
        $serviceList = $this->cleanSpaces((string) ($this->normalizeNullableField((string) ($row['tipos_servicios'] ?? '')) ?? ''));
        $city = $this->cleanSpaces((string) ($this->normalizeNullableField((string) ($row['ciudad'] ?? '')) ?? ''));
        $providedProvince = $this->normalizeNullableField((string) ($row['provincia'] ?? ($row['province'] ?? '')));

        $postalCode = null;
        if (preg_match('/\b(\d{5})\b/u', $address, $matches)) {
            $postalCode = $matches[1];
        }

        $province = $this->resolveProvince($city, $providedProvince);

        $phone = $this->normalizePhone((string) ($row['whatsapp'] ?? ($row['telefono_movil'] ?? '')));
        $landlinePhone = $this->normalizePhone((string) ($row['landing_phone'] ?? ($row['telefono_fijo'] ?? '')));
        $email = $this->normalizeNullableField((string) ($row['email'] ?? ''));
        $latitude = $this->normalizeCoordinate((string) ($row['latitude'] ?? ($row['lat'] ?? '')));
        $longitude = $this->normalizeCoordinate((string) ($row['longitude'] ?? ($row['lng'] ?? '')));
        $coordinateQuality = $this->normalizeNullableField((string) ($row['coordinate_quality'] ?? ''));

        $serviceTokens = collect(explode(';', $serviceList))
            ->map(fn ($token) => $this->cleanSpaces($token))
            ->filter()
            ->values()
            ->all();

        return [
            'line' => $line,
            'company_name' => Str::limit($companyName, 100, ''),
            'first_name' => Str::limit($companyName !== '' ? $companyName : 'Proveedor importado', 50, ''),
            'email' => $email !== null ? Str::limit($email, 50, '') : null,
            'phone' => $phone,
            'landline_phone' => $landlinePhone,
            'address_full' => $address !== '' ? Str::limit($address, 255, '') : null,
            'city' => $city !== '' ? Str::limit($city, 100, '') : null,
            'province' => $province !== null ? Str::limit($province, 100, '') : null,
            'postal_code' => $postalCode,
            'country' => 'España',
            'category' => $category,
            'provider_title' => Str::limit($category !== '' ? $category : $companyName, 255, ''),
            'provider_description' => $serviceList !== '' ? Str::limit($serviceList, 65535, '') : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'coordinate_quality' => $coordinateQuality,
            'service_tokens' => $serviceTokens,
        ];
    }

    private function resolveTypeIds(array $row, array $typeIndex): array
    {
        $aliases = (array) config('provider_import.specialty_aliases', []);
        $matchedIds = [];
        $missingLabels = [];

        $rawCandidates = array_merge([$row['category']], $row['service_tokens']);

        foreach ($rawCandidates as $candidate) {
            $candidate = $this->cleanSpaces((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $normalized = $this->normalizeLabel($candidate);

            foreach ($aliases as $aliasKey => $aliasTargets) {
                if (! $this->containsNormalizedPhrase($normalized, (string) $aliasKey)) {
                    continue;
                }

                foreach ((array) $aliasTargets as $aliasTarget) {
                    $this->collectResolvedTypeIds((string) $aliasTarget, $typeIndex, $matchedIds, $missingLabels);
                }
            }

            $this->collectResolvedTypeIds($candidate, $typeIndex, $matchedIds, $missingLabels);
            foreach ($typeIndex['rows'] as $typeRow) {
                if ($this->containsNormalizedPhrase($normalized, $typeRow['normalized'])) {
                    $matchedIds[$typeRow['id']] = $typeRow['id'];
                }
            }
        }

        $missingLabels = array_values(array_unique(array_filter($missingLabels, function ($label) use ($matchedIds, $typeIndex) {
            $normalized = $this->normalizeLabel($label);

            return ! isset($typeIndex['by_normalized'][$normalized]) && empty($matchedIds);
        })));

        if (! empty($matchedIds)) {
            $missingLabels = [];
        }

        return [
            'matched_ids' => array_values($matchedIds),
            'missing_labels' => $missingLabels,
        ];
    }

    private function collectResolvedTypeIds(string $label, array $typeIndex, array &$matchedIds, array &$missingLabels): void
    {
        $normalized = $this->normalizeLabel($label);
        if ($normalized === '') {
            return;
        }

        if (isset($typeIndex['by_normalized'][$normalized])) {
            $typeId = (int) $typeIndex['by_normalized'][$normalized];
            $matchedIds[$typeId] = $typeId;

            return;
        }

        $missingLabels[] = $label;
    }

    private function findExistingProvider(array $row): ?User
    {
        $phones = collect([
            $row['phone'] ?? null,
            $row['landline_phone'] ?? null,
        ])
            ->filter()
            ->map(fn ($phone) => ltrim((string) $phone, '+'))
            ->unique()
            ->values()
            ->all();

        foreach ($phones as $phone) {
            $match = User::query()
                ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
                ->where(function ($query) use ($phone) {
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'.',''),'+','') = ?", [$phone])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(landline_phone,' ',''),'-',''),'.',''),'+','') = ?", [$phone]);
                })
                ->first();

            if ($match) {
                return $match;
            }
        }

        if ($row['company_name'] === '') {
            return null;
        }

        return User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->whereRaw('LOWER(TRIM(user_name)) = ?', [mb_strtolower($row['company_name'])])
            ->first();
    }

    private function formatReportRow(array $row, string $action, string $result, array $issues, ?int $userId): array
    {
        return [
            'linea' => $row['line'],
            'empresa' => $row['company_name'],
            'direccion' => $row['address_full'] ?? null,
            'email' => $row['email'] ?? null,
            'telefono_fijo' => $row['landline_phone'] ?? null,
            'whatsapp' => $row['phone'] ?? null,
            'accion' => $action,
            'resultado' => $result,
            'user_id' => $userId ? (string) $userId : '-',
            'especialidades' => isset($row['type_ids']) ? implode(',', $row['type_ids']) : '-',
            'coordenadas' => $this->formatCoordinatesSummary($row),
            'modificacion' => $row['action_summary'] ?? '-',
            'observaciones' => $this->buildObservations($row, $issues),
        ];
    }

    private function buildObservations(array $row, array $issues): string
    {
        $issues = array_values(array_unique(array_filter($issues)));

        if ($issues !== []) {
            return implode(' | ', $issues);
        }

        if (($row['latitude'] ?? null) === null || ($row['longitude'] ?? null) === null) {
            return 'Faltan coordenadas';
        }

        return '';
    }

    private function buildActionSummary(array $row, ?User $existing, string $action): string
    {
        if ($action === 'create') {
            return 'Insertara proveedor, direccion, especialidades y coordenadas si vienen en el CSV';
        }

        if (! $existing) {
            return '-';
        }

        $changes = [];
        if (($row['email'] ?? null) !== null && (string) ($existing->email ?? '') !== (string) $row['email']) {
            $changes[] = 'email';
        }
        if (($row['phone'] ?? null) !== null && (string) ($existing->phone ?? '') !== (string) $row['phone']) {
            $changes[] = 'telefono';
        }
        if (($row['landline_phone'] ?? null) !== null && (string) ($existing->landline_phone ?? '') !== (string) $row['landline_phone']) {
            $changes[] = 'telefono fijo';
        }
        if (($row['address_full'] ?? null) !== null && (string) ($existing->address ?? '') !== (string) $row['address_full']) {
            $changes[] = 'direccion';
        }

        $address = UserAddress::query()->where('user_id', (int) $existing->id)->first();
        if (($row['latitude'] ?? null) !== null && (string) ($address->latitude ?? '') !== (string) $row['latitude']) {
            $changes[] = 'latitude';
        }
        if (($row['longitude'] ?? null) !== null && (string) ($address->longitude ?? '') !== (string) $row['longitude']) {
            $changes[] = 'longitude';
        }
        if (! empty($row['type_ids'])) {
            $currentTypeIds = app(ProviderServiceTypeService::class)->typeIdsForProvider((int) $existing->id);
            if ($currentTypeIds !== array_values($row['type_ids'])) {
                $changes[] = 'especialidades';
            }
        }

        if (empty($changes)) {
            return 'Coincide con el proveedor existente; no se detectan cambios efectivos';
        }

        return 'Actualizara: '.implode(', ', $changes);
    }

    private function formatCoordinatesSummary(array $row): string
    {
        $latitude = $row['latitude'] ?? null;
        $longitude = $row['longitude'] ?? null;
        if ($latitude === null || $longitude === null) {
            return 'Sin coordenadas';
        }

        $quality = $row['coordinate_quality'] ?? null;

        return trim($latitude.', '.$longitude.($quality ? ' ('.$quality.')' : ''));
    }

    private function normalizePhone(string $value): ?string
    {
        $trimmed = $this->normalizeNullableField($value);
        if ($trimmed === '' || $trimmed === null) {
            return null;
        }

        $hasPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        return $hasPlus ? '+'.$digits : $digits;
    }

    private function resolveProvince(string $city, ?string $providedProvince): ?string
    {
        $normalizedCity = $this->normalizeLabel($city);
        if ($normalizedCity !== '' && isset($this->getCityProvinceMap()[$normalizedCity])) {
            return $this->getCityProvinceMap()[$normalizedCity];
        }

        if (! $this->isValidProvince($providedProvince)) {
            return null;
        }

        return Str::limit($this->cleanSpaces((string) $providedProvince), 100, '');
    }

    private function getCityProvinceMap(): array
    {
        if ($this->cityProvinceMap !== null) {
            return $this->cityProvinceMap;
        }

        $path = database_path('data/provider_city_provinces.json');
        $contents = is_file($path) ? file_get_contents($path) : false;
        $rows = $contents !== false ? json_decode($contents, true) : null;
        if (! is_array($rows)) {
            throw new \RuntimeException('No se pudo cargar el catalogo ciudad-provincia de proveedores.');
        }

        $this->cityProvinceMap = [];
        foreach ($rows as $city => $province) {
            $this->cityProvinceMap[$this->normalizeLabel((string) $city)] = (string) $province;
        }

        return $this->cityProvinceMap;
    }

    private function isValidProvince(?string $province): bool
    {
        if ($province === null || trim($province) === '') {
            return false;
        }

        return ! in_array($this->normalizeLabel($province), ['espana', 'spain'], true);
    }

    private function normalizeLabel(string $value): string
    {
        $value = Str::ascii(mb_strtolower($this->cleanSpaces($value)));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function cleanSpaces(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function sanitizeCsvCell(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return trim($value);
    }

    private function normalizeNullableField(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $normalized = Str::ascii(mb_strtolower($trimmed));
        if (str_contains($normalized, 'direccion completa no visible en la captura')) {
            return null;
        }

        return match (mb_strtolower($trimmed)) {
            'null', 'nulo', 'none', 'n/a', 'na', '-' => null,
            default => $trimmed,
        };
    }

    private function normalizeCoordinate(string $value): ?string
    {
        $trimmed = $this->normalizeNullableField($value);
        if ($trimmed === null) {
            return null;
        }

        $normalized = str_replace(',', '.', $trimmed);
        if (! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 6, '.', '');
    }

    private function containsNormalizedPhrase(string $haystack, string $needle): bool
    {
        $haystack = trim($haystack);
        $needle = trim($needle);

        if ($haystack === '' || $needle === '') {
            return false;
        }

        return preg_match('/(?:^|\s)'.preg_quote($needle, '/').'(?:$|\s)/u', $haystack) === 1;
    }
}
