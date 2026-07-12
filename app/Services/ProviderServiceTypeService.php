<?php

namespace App\Services;

use App\Models\ProviderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderServiceTypeService
{
    public function normalizedTypeIds(array $serviceTypeIds): array
    {
        return collect($serviceTypeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function linkRowsForProvider(int $providerId): Collection
    {
        return ProviderService::query()
            ->where('provider_id', $providerId)
            ->orderBy('service_type_id')
            ->get();
    }

    public function typeIdsForProvider(int $providerId): array
    {
        return $this->linkRowsForProvider($providerId)
            ->pluck('service_type_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function typeIdsForProviders(array $providerIds): Collection
    {
        $normalizedProviderIds = collect($providerIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($normalizedProviderIds)) {
            return collect();
        }

        return ProviderService::query()
            ->whereIn('provider_id', $normalizedProviderIds)
            ->get(['provider_id', 'service_type_id'])
            ->groupBy('provider_id')
            ->map(function (Collection $rows) {
                return $rows
                    ->pluck('service_type_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            });
    }

    public function providerIdsForTypeIds(array $serviceTypeIds): array
    {
        $normalizedTypeIds = $this->normalizedTypeIds($serviceTypeIds);
        if (empty($normalizedTypeIds)) {
            return [];
        }

        return ProviderService::query()
            ->whereIn('service_type_id', $normalizedTypeIds)
            ->pluck('provider_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function syncForProvider(int $providerId, array $serviceTypeIds): void
    {
        $normalizedTypeIds = $this->normalizedTypeIds($serviceTypeIds);

        DB::transaction(function () use ($providerId, $normalizedTypeIds): void {
            $query = ProviderService::query()->where('provider_id', $providerId);

            if (empty($normalizedTypeIds)) {
                $query->delete();

                return;
            }

            $query->whereNotIn('service_type_id', $normalizedTypeIds)->delete();

            $existingTypeIds = ProviderService::query()
                ->where('provider_id', $providerId)
                ->pluck('service_type_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $timestamp = now();
            $payload = collect($normalizedTypeIds)
                ->reject(fn ($typeId) => in_array($typeId, $existingTypeIds, true))
                ->map(fn ($typeId) => [
                    'provider_id' => $providerId,
                    'service_type_id' => $typeId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->values()
                ->all();

            if (! empty($payload)) {
                ProviderService::query()->insert($payload);
            }
        });
    }
}
