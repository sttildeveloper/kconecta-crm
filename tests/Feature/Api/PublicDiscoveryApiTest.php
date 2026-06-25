<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicDiscoveryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_properties_returns_v1_contract_with_legacy_compatibility(): void
    {
        $response = $this->getJson('/api/properties?text=barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
                'status',
                'province',
            ]);
    }

    public function test_search_services_returns_v1_contract_with_legacy_compatibility(): void
    {
        $response = $this->getJson('/api/services?text=barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
                'status',
                'province',
            ]);
    }

    public function test_map_endpoints_return_v1_contract_with_legacy_status(): void
    {
        $propertiesMapResponse = $this->getJson('/api/properties_for_map');
        $propertiesMapResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
                'status',
            ]);

        $servicesMapResponse = $this->getJson('/api/services_for_map');
        $servicesMapResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
                'status',
            ]);
    }

    public function test_public_service_types_returns_only_publicly_discoverable_types_sorted_by_name(): void
    {
        $providerA = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'public-types-a@test.dev');
        $providerB = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'public-types-b@test.dev');
        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'public-types-client@test.dev');

        $cerrajeria = ServiceType::query()->create(['name' => 'Cerrajeria']);
        $electricista = ServiceType::query()->create(['name' => 'Electricista']);
        $fontaneria = ServiceType::query()->create(['name' => 'Fontaneria']);

        $serviceWithOwnCoords = Service::query()->create([
            'title' => 'Cerrajeria 24h',
            'description' => 'Servicio publico',
            'availability' => 'Siempre',
            'user_id' => (int) $providerA->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $serviceWithOwnCoords->id,
            'latitude' => '41.3874',
            'longitude' => '2.1686',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceWithOwnCoords->id,
            'service_type_id' => (int) $cerrajeria->id,
        ]);

        $serviceUsingUserCoords = Service::query()->create([
            'title' => 'Electricidad integral',
            'description' => 'Servicio publico con direccion del usuario',
            'availability' => 'Siempre',
            'user_id' => (int) $providerB->id,
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $providerB->id,
            'latitude' => '40.4168',
            'longitude' => '-3.7038',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceUsingUserCoords->id,
            'service_type_id' => (int) $electricista->id,
        ]);

        $serviceWithoutPublicCoords = Service::query()->create([
            'title' => 'Fontaneria privada',
            'description' => 'No visible en mapa',
            'availability' => 'Siempre',
            'user_id' => (int) $providerA->id,
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceWithoutPublicCoords->id,
            'service_type_id' => (int) $fontaneria->id,
        ]);

        $nonProviderService = Service::query()->create([
            'title' => 'Cliente final',
            'description' => 'No debe aparecer',
            'availability' => 'Siempre',
            'user_id' => (int) $client->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $nonProviderService->id,
            'latitude' => '39.4699',
            'longitude' => '-0.3763',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $nonProviderService->id,
            'service_type_id' => (int) $fontaneria->id,
        ]);

        $response = $this->getJson('/api/service-types');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', null)
            ->assertJsonPath('errors', null)
            ->assertJsonPath('status', 200)
            ->assertExactJson([
                'success' => true,
                'data' => [
                    ['id' => (int) $cerrajeria->id, 'name' => 'Cerrajeria'],
                    ['id' => (int) $electricista->id, 'name' => 'Electricista'],
                ],
                'message' => null,
                'errors' => null,
                'status' => 200,
            ]);
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
