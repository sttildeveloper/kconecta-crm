<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

