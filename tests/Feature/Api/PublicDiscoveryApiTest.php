<?php

namespace Tests\Feature\Api;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public function test_results_page_does_not_return_every_provider_when_selected_type_has_no_matches(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-unmatched-page@test.dev');
        $provider->forceFill(['user_name' => 'Proveedor que no coincide'])->save();
        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Sants 100',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3750',
            'longitude' => '2.1350',
        ]);
        $unmatchedType = ServiceType::query()->create(['name' => 'Servicio sin proveedores']);

        $this->get('/result/services?mode=1&sti[]='.$unmatchedType->id)
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertDontSee('Proveedor que no coincide');
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
        ProviderService::query()->create([
            'provider_id' => (int) $providerA->id,
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
        ProviderService::query()->create([
            'provider_id' => (int) $providerB->id,
            'service_type_id' => (int) $electricista->id,
        ]);

        $serviceWithoutPublicCoords = Service::query()->create([
            'title' => 'Fontaneria privada',
            'description' => 'No visible en mapa',
            'availability' => 'Siempre',
            'user_id' => (int) $providerA->id,
        ]);
        ProviderService::query()->create([
            'provider_id' => (int) $providerA->id,
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
        ProviderService::query()->create([
            'provider_id' => (int) $client->id,
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

    public function test_services_for_map_returns_unique_providers_as_provider_entities(): void
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
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
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
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
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
            ->assertJsonPath('data.0.id', (int) $provider->id)
            ->assertJsonPath('data.0.service_id', null)
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.title', 'Reformas Buele')
            ->assertJsonPath('data.0.average_stars', 4.5)
            ->assertJsonPath('data.0.ratings_count', 2)
            ->assertJsonPath('data.0.city', 'Barcelona')
            ->assertJsonPath('data.0.province', 'Barcelona')
            ->assertJsonPath('data.0.lat', '41.3874')
            ->assertJsonPath('data.0.lng', '2.1686')
            ->assertJsonPath('data.0.provider_url', url('/result_provider/'.$provider->id));

        $item = $response->json('data.0');
        $this->assertSame([$carpinteria->id, $fontaneria->id], $item['service_type_ids']);
        $this->assertSame([$carpinteria->id, $fontaneria->id], $item['specialty_ids']);
        $this->assertStringContainsString('/img/photo_profile/reformas-buele.webp', (string) $item['logo_url']);
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
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
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
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
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
        ProviderService::query()->create([
            'provider_id' => (int) $otherProvider->id,
            'service_type_id' => (int) $electricidad->id,
        ]);

        $response = $this->getJson('/api/services_for_map?sti='.$fontaneria->id.'&city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.id', (int) $provider->id)
            ->assertJsonPath('data.0.service_id', null)
            ->assertJsonPath('data.0.city', 'Barcelona')
            ->assertJsonPath('data.0.specialty_ids', [(int) $fontaneria->id])
            ->assertJsonPath('data.0.service_type_ids', [(int) $fontaneria->id]);

        $emptyResponse = $this->getJson('/api/services_for_map?sti=999999');

        $emptyResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_services_for_map_uses_google_city_and_province_instead_of_literal_address(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-google-location@test.dev');
        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Sants 15',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3700',
            'longitude' => '2.1400',
        ]);

        $response = $this->getJson('/api/services_for_map?address=08029%20Barcelona&city=Barcelona&province=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.city', 'Barcelona')
            ->assertJsonPath('data.0.province', 'Barcelona');
    }

    public function test_empty_service_type_query_is_ignored_by_results_page_and_map_api(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-empty-sti@test.dev');
        $provider->forceFill(['user_name' => 'Proveedor sin filtro'])->save();
        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Avinguda de Josep Tarradellas 92',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3887',
            'longitude' => '2.1435',
        ]);

        $query = 'mode=2&city=Barcelona&province=Barcelona&sti%5B%5D=';

        $this->get('/result/services?'.$query)
            ->assertOk()
            ->assertSee('1 coincidencia');

        $this->getJson('/api/services_for_map?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id);
    }

    public function test_coordinates_find_nearby_providers_when_google_does_not_return_city_or_province(): void
    {
        $nearbyProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-near-postcode@test.dev');
        $nearbyProvider->forceFill(['user_name' => 'Proveedor Cercano'])->save();
        UserAddress::query()->create([
            'user_id' => (int) $nearbyProvider->id,
            'address' => 'Carrer de Sants 100',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3750',
            'longitude' => '2.1350',
        ]);

        $farProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-far-postcode@test.dev');
        $farProvider->forceFill(['user_name' => 'Proveedor Lejano'])->save();
        UserAddress::query()->create([
            'user_id' => (int) $farProvider->id,
            'address' => 'Gran Via 1',
            'city' => 'Madrid',
            'province' => 'Madrid',
            'latitude' => '40.4168',
            'longitude' => '-3.7038',
        ]);

        $query = 'address=08029&latitude=41.3828&longitude=2.1453&city=&province=';

        $this->get('/result/services?mode=1&'.$query)
            ->assertOk()
            ->assertSee('Proveedor Cercano')
            ->assertDontSee('Proveedor Lejano');

        $this->getJson('/api/services_for_map?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $nearbyProvider->id);
    }

    public function test_map_state_does_not_add_history_entries_that_block_browser_back(): void
    {
        $response = $this->get('/result/services?mode=2');

        $response->assertOk()
            ->assertSee('window.history.replaceState', false)
            ->assertDontSee('window.history.pushState', false)
            ->assertSee('@googlemaps/markerclusterer@2.6.2', false)
            ->assertSee('googleMarkerClusterer = new window.markerClusterer.MarkerClusterer', false)
            ->assertSee('new window.markerClusterer.SuperClusterAlgorithm({ radius: 80, maxZoom: 19 })', false)
            ->assertSee('id="map-cluster-panel"', false)
            ->assertSee('renderClusterPanel(providers)', false)
            ->assertSee('map-cluster-provider--without-logo', false)
            ->assertSee('onClusterClick:', false)
            ->assertSee('zoom = Math.max(13, zoom || 13)', false);
    }

    public function test_services_for_map_includes_provider_without_service_when_contact_and_coordinates_exist(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-no-service-map@test.dev');
        $provider->forceFill([
            'user_name' => 'Proveedor Sin Service',
            'phone' => '+34612345678',
        ])->save();

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Mallorca, 120',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3920',
            'longitude' => '2.1640',
        ]);

        $response = $this->getJson('/api/services_for_map?city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (int) $provider->id)
            ->assertJsonPath('data.0.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.0.service_id', null)
            ->assertJsonPath('data.0.title', 'Proveedor Sin Service')
            ->assertJsonPath('data.0.phone', '+34612345678')
            ->assertJsonPath('data.0.provider_url', url('/result_provider/'.$provider->id));
    }

    public function test_public_providers_returns_all_active_provider_users_even_without_services(): void
    {
        $providerWithServices = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-services@test.dev');
        $providerWithServices->forceFill([
            'user_name' => 'Reformas Buele',
            'photo' => 'reformas-buele.webp',
            'phone' => '653252923',
        ])->save();

        $providerWithoutServices = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-no-service@test.dev');
        $providerWithoutServices->forceFill([
            'user_name' => 'Proveedor Sin Servicio',
        ])->save();

        $inactiveProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-inactive@test.dev');
        $inactiveProvider->forceFill([
            'user_name' => 'Proveedor Inactivo',
        ])->save();
        if (Schema::hasColumn('user', 'is_active')) {
            $inactiveProvider->forceFill(['is_active' => 0])->save();
        }

        $agent = $this->makeUser(User::LEVEL_AGENT, 'agent@test.dev');

        UserAddress::query()->create([
            'user_id' => (int) $providerWithServices->id,
            'address' => 'Carrer de la Riera d\'Horta, 52',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.4281449',
            'longitude' => '2.1782515',
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $providerWithoutServices->id,
            'address' => 'Carrer de Sants, 100',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3750000',
            'longitude' => '2.1350000',
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $inactiveProvider->id,
            'address' => 'Gran Via 1',
            'city' => 'Madrid',
            'province' => 'Madrid',
            'latitude' => '40.4168',
            'longitude' => '-3.7038',
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $agent->id,
            'address' => 'Passeig de Gracia 1',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3900',
            'longitude' => '2.1650',
        ]);

        $reformasType = ServiceType::query()->create(['name' => 'Reformas integrales']);

        $service = Service::query()->create([
            'title' => 'Servicio publico',
            'description' => 'Servicio provider',
            'availability' => 'Siempre',
            'user_id' => (int) $providerWithServices->id,
        ]);
        ProviderService::query()->create([
            'provider_id' => (int) $providerWithServices->id,
            'service_type_id' => (int) $reformasType->id,
        ]);

        DB::table('service_provider_ratings')->insert([
            'provider_user_id' => (int) $providerWithServices->id,
            'client_user_id' => (int) $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-provider-contract@test.dev')->id,
            'stars' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/providers?city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $items = collect($response->json('data'))->keyBy('provider_user_id');

        $this->assertTrue($items->has((int) $providerWithServices->id));
        $this->assertTrue($items->has((int) $providerWithoutServices->id));
        if (Schema::hasColumn('user', 'is_active')) {
            $this->assertFalse($items->has((int) $inactiveProvider->id));
        }
        $this->assertFalse($items->has((int) $agent->id));

        $withService = $items->get((int) $providerWithServices->id);
        $this->assertSame('Reformas Buele', $withService['title']);
        $this->assertNull($withService['service_id']);
        $this->assertFalse($withService['has_public_service_detail']);
        $this->assertEquals(5.0, $withService['average_stars']);
        $this->assertSame(1, $withService['ratings_count']);
        $this->assertStringContainsString('/img/photo_profile/reformas-buele.webp', (string) $withService['logo_url']);
        $this->assertSame(url('/result_provider/'.$providerWithServices->id), $withService['provider_url']);

        $withoutService = $items->get((int) $providerWithoutServices->id);
        $this->assertSame('Proveedor Sin Servicio', $withoutService['title']);
        $this->assertNull($withoutService['service_id']);
        $this->assertFalse($withoutService['has_public_service_detail']);
        $this->assertSame('Barcelona', $withoutService['city']);
    }

    public function test_public_providers_filters_by_service_type_but_keeps_provider_entity_contract(): void
    {
        $providerMatching = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-matching@test.dev');
        $providerMatching->forceFill(['user_name' => 'Reformas Buele'])->save();
        $providerOther = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-other-type@test.dev');
        $providerNoService = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-no-service-filter@test.dev');

        UserAddress::query()->create([
            'user_id' => (int) $providerMatching->id,
            'address' => 'Carrer del Duc 4',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3874',
            'longitude' => '2.1686',
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $providerOther->id,
            'address' => 'Ronda del Mig 10',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3800',
            'longitude' => '2.1500',
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $providerNoService->id,
            'address' => 'Gran Via 20',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'latitude' => '41.3820',
            'longitude' => '2.1600',
        ]);

        $cerrajeria = ServiceType::query()->create(['name' => 'Cerrajeria']);
        $limpieza = ServiceType::query()->create(['name' => 'Limpieza']);

        $matchingService = Service::query()->create([
            'title' => 'Cerrajeria',
            'description' => 'Servicio matching',
            'availability' => 'Siempre',
            'user_id' => (int) $providerMatching->id,
        ]);
        ProviderService::query()->create([
            'provider_id' => (int) $providerMatching->id,
            'service_type_id' => (int) $cerrajeria->id,
        ]);

        $otherService = Service::query()->create([
            'title' => 'Limpieza',
            'description' => 'Servicio no matching',
            'availability' => 'Siempre',
            'user_id' => (int) $providerOther->id,
        ]);
        ProviderService::query()->create([
            'provider_id' => (int) $providerOther->id,
            'service_type_id' => (int) $limpieza->id,
        ]);

        $response = $this->getJson('/api/public/providers?sti='.$cerrajeria->id.'&city=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_user_id', (int) $providerMatching->id)
            ->assertJsonPath('data.0.title', 'Reformas Buele')
            ->assertJsonPath('data.0.service_id', null)
            ->assertJsonPath('data.0.has_public_service_detail', false)
            ->assertJsonPath('data.0.specialty_ids', [(int) $cerrajeria->id])
            ->assertJsonPath('data.0.service_type_ids', [(int) $cerrajeria->id]);
    }

    public function test_public_provider_detail_uses_provider_profile_without_legacy_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-detail@test.dev');
        $provider->forceFill([
            'user_name' => 'Reformas Canonicas',
            'provider_title' => 'Reformas integrales en Barcelona',
            'provider_description' => 'Ficha propiedad del proveedor.',
            'provider_availability' => 'Lunes a viernes',
            'provider_page_url' => 'https://reformas.test',
            'photo' => 'provider-logo.webp',
            'phone' => '+34 612 345 678',
        ])->save();

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Carrer de Mallorca, 120',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'postal_code' => '08036',
            'country' => 'España',
            'latitude' => '41.3920',
            'longitude' => '2.1640',
        ]);
        $specialty = ServiceType::query()->create(['name' => 'Reformas integrales']);
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $specialty->id,
        ]);
        CoverImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => 'provider-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => 'provider-gallery.webp',
        ]);
        Video::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => 'provider-video.mp4',
        ]);

        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'provider-detail-client@test.dev');
        DB::table('service_provider_ratings')->insert([
            'provider_user_id' => (int) $provider->id,
            'client_user_id' => (int) $client->id,
            'stars' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/public/providers/'.$provider->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.id', (int) $provider->id)
            ->assertJsonPath('data.provider_user_id', (int) $provider->id)
            ->assertJsonPath('data.service_id', null)
            ->assertJsonPath('data.title', 'Reformas integrales en Barcelona')
            ->assertJsonPath('data.description', 'Ficha propiedad del proveedor.')
            ->assertJsonPath('data.city', 'Barcelona')
            ->assertJsonPath('data.specialty_ids', [(int) $specialty->id])
            ->assertJsonPath('data.average_stars', 5)
            ->assertJsonPath('data.ratings_count', 1)
            ->assertJsonPath('data.has_public_provider_detail', true)
            ->assertJsonPath('data.has_public_service_detail', false)
            ->assertJsonCount(1, 'data.gallery')
            ->assertJsonMissingPath('data.profile_visits')
            ->assertJsonStructure(['data' => [
                'logo_url',
                'cover_image_url',
                'video_url',
                'whatsapp_url',
                'provider_url',
            ]]);
    }

    public function test_public_provider_detail_ignores_legacy_service_content_and_rejects_non_providers(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-detail-legacy@test.dev');
        $provider->forceFill([
            'user_name' => 'Proveedor Canonico',
            'provider_title' => 'Titulo canonico',
            'provider_description' => 'Descripcion canonica',
        ])->save();

        Service::query()->create([
            'title' => 'Titulo legacy que no debe salir',
            'description' => 'Descripcion legacy que no debe salir',
            'availability' => 'Legacy',
            'user_id' => (int) $provider->id,
        ]);

        $this->getJson('/api/providers/'.$provider->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'Titulo canonico')
            ->assertJsonPath('data.description', 'Descripcion canonica')
            ->assertJsonPath('data.service_id', null);

        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'not-provider-detail@test.dev');
        $this->getJson('/api/public/providers/'.$client->id)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Proveedor no encontrado');
    }

    public function test_public_provider_detail_rejects_non_numeric_provider_ids_without_server_error(): void
    {
        $this->getJson('/api/providers/not-a-number')->assertNotFound();
        $this->getJson('/api/public/providers/not-a-number')->assertNotFound();
    }

    public function test_public_provider_detail_returns_my_stars_for_sanctum_bearer_token(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-detail-token@test.dev');
        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'provider-detail-token-client@test.dev');
        DB::table('service_provider_ratings')->insert([
            'provider_user_id' => (int) $provider->id,
            'client_user_id' => (int) $client->id,
            'stars' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $client->createToken('public-provider-detail-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/public/providers/'.$provider->id)
            ->assertOk()
            ->assertJsonPath('data.my_stars', 4);
    }

    private function makeUser(int $levelId, string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => $levelId,
            'email_verified_at' => now(),
        ]);
    }
}
