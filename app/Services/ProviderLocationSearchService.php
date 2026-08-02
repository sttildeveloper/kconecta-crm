<?php

namespace App\Services;

use App\Models\UserAddress;

class ProviderLocationSearchService
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function hasValidCoordinates(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        return $latitude >= -90 && $latitude <= 90
            && $longitude >= -180 && $longitude <= 180;
    }

    /**
     * @return array<int>
     */
    public function providerIdsWithinRadius(
        float $latitude,
        float $longitude,
        ?float $radiusKm = null
    ): array {
        $radiusKm ??= (float) config('services.provider_search.radius_km', 30);

        return UserAddress::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '<>', '')
            ->where('longitude', '<>', '')
            ->get(['user_id', 'latitude', 'longitude'])
            ->filter(function (UserAddress $address) use ($latitude, $longitude, $radiusKm) {
                if (! $this->hasValidCoordinates($address->latitude, $address->longitude)) {
                    return false;
                }

                return $this->distanceInKm(
                    $latitude,
                    $longitude,
                    (float) $address->latitude,
                    (float) $address->longitude
                ) <= $radiusKm;
            })
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function distanceInKm(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA))
            * cos(deg2rad($latitudeB))
            * sin($longitudeDelta / 2) ** 2;
        $haversine = min(1.0, max(0.0, $haversine));

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
