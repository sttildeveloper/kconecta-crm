<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_public_service_types_returns_all_types_sorted_by_name(): void
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
                    ['id' => (int) $fontaneria->id, 'name' => 'Fontaneria'],
                ],
                'message' => null,
                'errors' => null,
                'status' => 200,
            ]);
    }

    public function test_services_for_map_returns_unique_providers_with_representative_service_id_and_detail_compatibility(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'reformas-buele@test.dev');
        $provider->forceFill([
            'user_name' => 'Reformas Buele',
            'photo' => 'reformas-buele.webp',
            'phone' => '+34 600 111 222',
        ])->save();

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer del Duc 4',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3874',
            'longitude' => '2.1686',
        ]);

        $carpinteria = ServiceType::query()->create(['name' => 'Carpinteria']);
        $fontaneria = ServiceType::query()->create(['name' => 'Fontaneria']);

        $serviceA = Service::query()->create([
            'title' => 'Reformas integrales',
            'description' => 'Servicio A',
            'availability' => 'Siempre',
            'user_id' => (int) $provider->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $serviceA->id,
            'latitude' => '41.3900',
            'longitude' => '2.1700',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceA->id,
            'service_type_id' => (int) $carpinteria->id,
        ]);

        $serviceB = Service::query()->create([
            'title' => 'Fontaneria express',
            'description' => 'Servicio B',
            'availability' => 'Siempre',
            'user_id' => (int) $provider->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $serviceB->id,
            'latitude' => '41.3910',
            'longitude' => '2.1710',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceB->id,
            'service_type_id' => (int) $fontaneria->id,
        ]);

        $clientA = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-a-ratings@test.dev');
        $clientB = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-b-ratings@test.dev');

        DB::table('service_provider_ratings')->insert([
            [
                'provider_user_id' => (int) $provider->id,
                'client_user_id' => (int) $clientA->id,
                'stars' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_user_id' => (int) $provider->id,
                'client_user_id' => (int) $clientB->id,
                'stars' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/services_for_map?city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (int) $serviceA->id)
            ->assertJsonPath('data.0.service_id', (int) $serviceA->id)
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.title', 'Reformas Buele')
            ->assertJsonPath('data.0.average_stars', 4.5)
            ->assertJsonPath('data.0.ratings_count', 2)
            ->assertJsonPath('data.0.city', 'Barcelona')
            ->assertJsonPath('data.0.province', 'Barcelona')
            ->assertJsonPath('data.0.lat', '41.3900')
            ->assertJsonPath('data.0.lng', '2.1700');

        $item = $response->json('data.0');
        $this->assertSame([$carpinteria->id, $fontaneria->id], $item['service_type_ids']);
        $this->assertStringContainsString('/img/photo_profile/reformas-buele.webp', (string) $item['logo_url']);

        $this->getJson('/api/services/' . $item['id'])
            ->assertOk()
            ->assertJsonPath('data.id', (int) $serviceA->id)
            ->assertJsonPath('data.provider_user_id', (int) $provider->id);
    }

    public function test_services_for_map_keeps_type_filter_while_deduplicating_by_provider(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-sti@test.dev');
        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Sants 15',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3700',
            'longitude' => '2.1400',
        ]);

        $otherProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-other@test.dev');
        UserAddress::query()->create([
            'user_id' => (int) $otherProvider->id,
            'address' => 'Gran Via 1',
            'city' => 'Madrid',
            'province' => 'Madrid',
            'latitude' => '40.4168',
            'longitude' => '-3.7038',
        ]);

        $carpinteria = ServiceType::query()->create(['name' => 'Carpinteria']);
        $fontaneria = ServiceType::query()->create(['name' => 'Fontaneria']);
        $electricidad = ServiceType::query()->create(['name' => 'Electricidad']);

        $serviceA = Service::query()->create([
            'title' => 'Carpinteria',
            'description' => 'Servicio A',
            'availability' => 'Siempre',
            'user_id' => (int) $provider->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $serviceA->id,
            'latitude' => '41.3701',
            'longitude' => '2.1401',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceA->id,
            'service_type_id' => (int) $carpinteria->id,
        ]);

        $serviceB = Service::query()->create([
            'title' => 'Fontaneria',
            'description' => 'Servicio B',
            'availability' => 'Siempre',
            'user_id' => (int) $provider->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $serviceB->id,
            'latitude' => '41.3702',
            'longitude' => '2.1402',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $serviceB->id,
            'service_type_id' => (int) $fontaneria->id,
        ]);

        $otherService = Service::query()->create([
            'title' => 'Electricidad',
            'description' => 'Servicio C',
            'availability' => 'Siempre',
            'user_id' => (int) $otherProvider->id,
        ]);
        ServiceAddress::query()->create([
            'service_id' => (int) $otherService->id,
            'city' => 'Madrid',
            'province' => 'Madrid',
            'latitude' => '40.4169',
            'longitude' => '-3.7039',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $otherService->id,
            'service_type_id' => (int) $electricidad->id,
        ]);

        $response = $this->getJson('/api/services_for_map?sti=' . $fontaneria->id . '&city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.id', (int) $serviceB->id)
            ->assertJsonPath('data.0.service_id', (int) $serviceB->id)
            ->assertJsonPath('data.0.city', 'Barcelona')
            ->assertJsonPath('data.0.service_type_ids', [(int) $fontaneria->id]);

        $emptyResponse = $this->getJson('/api/services_for_map?sti=999999');

        $emptyResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
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
