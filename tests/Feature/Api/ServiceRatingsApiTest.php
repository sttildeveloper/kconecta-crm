<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceRatingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_final_client_cannot_rate_provider(): void
    {
        [$provider, $client] = $this->seedProviderAndFinalClient(false);

        $code = DB::table('service_work_codes')->insertGetId([
            'provider_user_id' => $provider->id,
            'code' => 'WK-UNVERIFIED-1',
            'is_used' => 0,
            'used_by_user_id' => null,
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($code);

        $response = $this->actingAs($client, 'sanctum')->postJson('/api/service-ratings', [
            'provider_user_id' => $provider->id,
            'work_code' => 'WK-UNVERIFIED-1',
            'stars' => 4,
        ]);

        $response->assertStatus(403)->assertJsonPath('errors.code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_verified_final_client_can_rate_with_valid_code_and_code_is_consumed(): void
    {
        [$provider, $client] = $this->seedProviderAndFinalClient(true);

        DB::table('service_work_codes')->insert([
            'provider_user_id' => $provider->id,
            'code' => 'WK-VALID-1',
            'is_used' => 0,
            'used_by_user_id' => null,
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')->postJson('/api/service-ratings', [
            'provider_user_id' => $provider->id,
            'work_code' => 'WK-VALID-1',
            'stars' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.average_stars', 5)
            ->assertJsonPath('data.ratings_count', 1)
            ->assertJsonPath('data.my_stars', 5);

        $this->assertDatabaseHas('service_provider_ratings', [
            'provider_user_id' => $provider->id,
            'client_user_id' => $client->id,
            'stars' => 5,
        ]);

        $this->assertDatabaseHas('service_work_codes', [
            'provider_user_id' => $provider->id,
            'code' => 'WK-VALID-1',
            'is_used' => 1,
            'used_by_user_id' => $client->id,
        ]);
    }

    public function test_second_vote_updates_existing_rating_and_does_not_duplicate_row(): void
    {
        [$provider, $client] = $this->seedProviderAndFinalClient(true);

        DB::table('service_provider_ratings')->insert([
            'provider_user_id' => $provider->id,
            'client_user_id' => $client->id,
            'stars' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('service_work_codes')->insert([
            [
                'provider_user_id' => $provider->id,
                'code' => 'WK-UPDATE-1',
                'is_used' => 0,
                'used_by_user_id' => null,
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_user_id' => $provider->id,
                'code' => 'WK-UPDATE-2',
                'is_used' => 0,
                'used_by_user_id' => null,
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($client, 'sanctum')->postJson('/api/service-ratings', [
            'provider_user_id' => $provider->id,
            'work_code' => 'WK-UPDATE-1',
            'stars' => 4,
        ])->assertOk();

        $this->actingAs($client, 'sanctum')->postJson('/api/service-ratings', [
            'provider_user_id' => $provider->id,
            'work_code' => 'WK-UPDATE-2',
            'stars' => 5,
        ])->assertOk();

        $this->assertSame(1, DB::table('service_provider_ratings')
            ->where('provider_user_id', $provider->id)
            ->where('client_user_id', $client->id)
            ->count());

        $this->assertDatabaseHas('service_provider_ratings', [
            'provider_user_id' => $provider->id,
            'client_user_id' => $client->id,
            'stars' => 5,
        ]);
    }

    public function test_provider_summary_returns_average_count_and_my_stars_when_authenticated(): void
    {
        [$provider, $client] = $this->seedProviderAndFinalClient(true);
        $otherClient = $this->makeUser(User::LEVEL_FINAL_CLIENT, true, 'other-client@example.com');

        DB::table('service_provider_ratings')->insert([
            [
                'provider_user_id' => $provider->id,
                'client_user_id' => $client->id,
                'stars' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_user_id' => $provider->id,
                'client_user_id' => $otherClient->id,
                'stars' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/service-ratings/provider/' . $provider->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ratings_count', 2)
            ->assertJsonPath('data.average_stars', 4)
            ->assertJsonPath('data.my_stars', 3);
    }

    private function seedProviderAndFinalClient(bool $clientVerified): array
    {
        $this->seedUserLevels();

        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, true, 'provider@example.com');
        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, $clientVerified, 'client@example.com');

        return [$provider, $client];
    }

    private function seedUserLevels(): void
    {
        UserLevel::query()->updateOrCreate(
            ['id' => User::LEVEL_SERVICE_PROVIDER],
            ['name' => 'Proveedor de servicios']
        );

        UserLevel::query()->updateOrCreate(
            ['id' => User::LEVEL_FINAL_CLIENT],
            ['name' => 'Cliente final']
        );
    }

    private function makeUser(int $levelId, bool $verified, string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'test-' . $levelId . '-' . md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => $levelId,
            'email_verified_at' => $verified ? now() : null,
        ]);
    }
}
