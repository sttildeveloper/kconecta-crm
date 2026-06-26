<?php

namespace Tests\Feature;

use App\Models\ServiceProviderRating;
use App\Models\ServiceWorkCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersProviderMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_cards_include_provider_metrics(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-users-metrics@test.dev');
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-users-metrics@test.dev');
        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-users-metrics@test.dev');

        if (DB::getSchemaBuilder()->hasTable('service_profile_visits')) {
            DB::table('service_profile_visits')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => null,
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('service_contact_clicks')) {
            DB::table('service_contact_clicks')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => null,
                'channel' => 'whatsapp',
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        ServiceWorkCode::query()->create([
            'provider_user_id' => (int) $provider->id,
            'code' => 'WORK-CODE-001',
            'is_used' => true,
            'used_by_user_id' => (int) $client->id,
            'used_at' => now(),
        ]);

        ServiceProviderRating::query()->create([
            'provider_user_id' => (int) $provider->id,
            'client_user_id' => (int) $client->id,
            'stars' => 5,
        ]);

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('Vistas')
            ->assertSee('Contacto')
            ->assertSee('Tickets')
            ->assertSee('Valoraciones')
            ->assertSee('5.0')
            ->assertSee('(1)');
    }

    private function makeUser(int $levelId, string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user-' . md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => $levelId,
            'email_verified_at' => now(),
        ]);
    }
}
