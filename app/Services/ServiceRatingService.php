<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceRatingService
{
    public function isFinalClient(User $user): bool
    {
        return (int) $user->user_level_id === User::LEVEL_FINAL_CLIENT;
    }

    public function createWorkCode(int $providerUserId): string
    {
        $now = now();

        for ($i = 0; $i < 5; $i++) {
            $code = 'WK-' . Str::upper(Str::random(10));
            $exists = DB::table('service_work_codes')->where('code', $code)->exists();
            if ($exists) {
                continue;
            }

            DB::table('service_work_codes')->insert([
                'provider_user_id' => $providerUserId,
                'code' => $code,
                'is_used' => 0,
                'used_by_user_id' => null,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $code;
        }

        throw new \RuntimeException('No se pudo generar un codigo unico.');
    }

    public function submitRating(User $client, int $providerUserId, string $workCode, int $stars): void
    {
        DB::transaction(function () use ($client, $providerUserId, $workCode, $stars): void {
            $provider = DB::table('user')
                ->where('id', $providerUserId)
                ->first(['id', 'user_level_id']);

            if (! $provider || (int) $provider->user_level_id !== User::LEVEL_SERVICE_PROVIDER) {
                throw new \DomainException('PROVIDER_NOT_ALLOWED');
            }

            if ((int) $client->id === $providerUserId) {
                throw new \DomainException('SELF_RATING_NOT_ALLOWED');
            }

            $codeRow = DB::table('service_work_codes')
                ->where('code', trim($workCode))
                ->lockForUpdate()
                ->first();

            if (! $codeRow || (int) $codeRow->provider_user_id !== $providerUserId) {
                throw new \DomainException('WORK_CODE_INVALID');
            }

            if ((int) $codeRow->is_used === 1) {
                throw new \DomainException('WORK_CODE_USED');
            }

            $now = now();

            DB::table('service_work_codes')
                ->where('id', $codeRow->id)
                ->update([
                    'is_used' => 1,
                    'used_by_user_id' => (int) $client->id,
                    'used_at' => $now,
                    'updated_at' => $now,
                ]);

            $existing = DB::table('service_provider_ratings')
                ->where('provider_user_id', $providerUserId)
                ->where('client_user_id', (int) $client->id)
                ->first(['id']);

            if ($existing) {
                DB::table('service_provider_ratings')
                    ->where('id', $existing->id)
                    ->update([
                        'stars' => $stars,
                        'updated_at' => $now,
                    ]);

                return;
            }

            DB::table('service_provider_ratings')->insert([
                'provider_user_id' => $providerUserId,
                'client_user_id' => (int) $client->id,
                'stars' => $stars,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function submitRatingByWorkCode(User $client, string $workCode, int $stars): int
    {
        return DB::transaction(function () use ($client, $workCode, $stars): int {
            $codeRow = DB::table('service_work_codes')
                ->where('code', trim($workCode))
                ->lockForUpdate()
                ->first();

            if (! $codeRow) {
                throw new \DomainException('WORK_CODE_INVALID');
            }

            $providerUserId = (int) $codeRow->provider_user_id;

            $provider = DB::table('user')
                ->where('id', $providerUserId)
                ->first(['id', 'user_level_id']);

            if (! $provider || (int) $provider->user_level_id !== User::LEVEL_SERVICE_PROVIDER) {
                throw new \DomainException('PROVIDER_NOT_ALLOWED');
            }

            if ((int) $client->id === $providerUserId) {
                throw new \DomainException('SELF_RATING_NOT_ALLOWED');
            }

            if ((int) $codeRow->is_used === 1) {
                throw new \DomainException('WORK_CODE_USED');
            }

            $now = now();

            DB::table('service_work_codes')
                ->where('id', $codeRow->id)
                ->update([
                    'is_used' => 1,
                    'used_by_user_id' => (int) $client->id,
                    'used_at' => $now,
                    'updated_at' => $now,
                ]);

            $existing = DB::table('service_provider_ratings')
                ->where('provider_user_id', $providerUserId)
                ->where('client_user_id', (int) $client->id)
                ->first(['id']);

            if ($existing) {
                DB::table('service_provider_ratings')
                    ->where('id', $existing->id)
                    ->update([
                        'stars' => $stars,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('service_provider_ratings')->insert([
                    'provider_user_id' => $providerUserId,
                    'client_user_id' => (int) $client->id,
                    'stars' => $stars,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $providerUserId;
        });
    }

    public function providerRatingSummary(int $providerUserId, ?User $authUser = null): array
    {
        $aggregate = DB::table('service_provider_ratings')
            ->where('provider_user_id', $providerUserId)
            ->selectRaw('COUNT(*) as ratings_count, AVG(stars) as average_stars')
            ->first();

        $average = $aggregate && $aggregate->average_stars !== null
            ? round((float) $aggregate->average_stars, 2)
            : 0.0;
        $count = $aggregate ? (int) $aggregate->ratings_count : 0;

        $payload = [
            'average_stars' => $average,
            'ratings_count' => $count,
        ];

        if ($authUser) {
            $myStars = DB::table('service_provider_ratings')
                ->where('provider_user_id', $providerUserId)
                ->where('client_user_id', (int) $authUser->id)
                ->value('stars');

            if ($myStars !== null) {
                $payload['my_stars'] = (int) $myStars;
            }
        }

        return $payload;
    }
}
