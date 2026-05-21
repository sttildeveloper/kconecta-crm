<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_token_can_access_protected_endpoint(): void
    {
        $user = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'User',
            'user_name' => 'api-user',
            'email' => 'api-user@test.dev',
            'phone' => '600000000',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('token_type', 'Bearer');

        $token = $loginResponse->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_revokes_current_token_and_token_cannot_be_reused(): void
    {
        $user = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'User',
            'user_name' => 'api-user-logout',
            'email' => 'api-user-logout@test.dev',
            'phone' => '600000001',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $token = (string) $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_me_includes_provider_logo_fields(): void
    {
        $user = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'Logo',
            'user_name' => 'api-user-logo',
            'email' => 'api-user-logo@test.dev',
            'phone' => '600000002',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'photo' => 'provider-test.webp',
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $token = (string) $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider_logo_path', 'provider-test.webp')
            ->assertJsonPath('data.provider_logo_url', asset('img/photo_profile/provider-test.webp'));
    }

    public function test_me_includes_provider_rating_aggregates(): void
    {
        $provider = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'Provider',
            'user_name' => 'api-user-ratings',
            'email' => 'api-user-ratings@test.dev',
            'phone' => '600000003',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);

        $clientA = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'Client',
            'user_name' => 'api-client-a',
            'email' => 'api-client-a@test.dev',
            'phone' => '600000004',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_FINAL_CLIENT,
            'email_verified_at' => now(),
        ]);

        $clientB = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'Client',
            'user_name' => 'api-client-b',
            'email' => 'api-client-b@test.dev',
            'phone' => '600000005',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_FINAL_CLIENT,
            'email_verified_at' => now(),
        ]);

        DB::table('service_provider_ratings')->insert([
            [
                'provider_user_id' => (int) $provider->id,
                'client_user_id' => (int) $clientA->id,
                'stars' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_user_id' => (int) $provider->id,
                'client_user_id' => (int) $clientB->id,
                'stars' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $provider->email,
            'password' => 'password123',
        ])->assertOk();

        $token = (string) $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating_avg', 4.5)
            ->assertJsonPath('data.reviews_count', 2)
            ->assertJsonPath('rating_avg', 4.5)
            ->assertJsonPath('reviews_count', 2);
    }
}
