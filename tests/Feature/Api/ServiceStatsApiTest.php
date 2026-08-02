<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_service_visit_persists_event(): void
    {
        $provider = $this->makeProvider('provider-stats-visit@test.dev');
        $service = Service::query()->create([
            'title' => 'Servicio visita',
            'description' => 'Servicio prueba',
            'availability' => 'Lun-Vie',
            'user_id' => (int) $provider->id,
        ]);

        $response = $this->post('/api/service_stats/register_visit', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => (int) $service->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_profile_visits', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
    }

    public function test_register_service_contact_click_persists_event(): void
    {
        $provider = $this->makeProvider('provider-stats-click@test.dev');
        $service = Service::query()->create([
            'title' => 'Servicio click',
            'description' => 'Servicio prueba',
            'availability' => 'Lun-Vie',
            'user_id' => (int) $provider->id,
        ]);

        $response = $this->post('/api/service_stats/register_contact_click', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => (int) $service->id,
            'channel' => 'whatsapp',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_contact_clicks', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
            'channel' => 'whatsapp',
        ]);
    }

    public function test_register_service_visit_requires_provider_user_id(): void
    {
        $response = $this->post('/api/service_stats/register_visit', [
            'provider_user_id' => 0,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    private function makeProvider(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Provider',
            'last_name' => 'Stats',
            'user_name' => 'provider-'.md5($email),
            'email' => $email,
            'phone' => '600000099',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);
    }
}
