<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyUtilityApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_save_returns_v1_contract_and_legacy_fields(): void
    {
        $response = $this->postJson('/api/visitor/save', [
            'post_id' => 123,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'data' => ['id'],
                'meta',
                'message',
                'errors',
                'status',
                'id',
            ]);
    }

    public function test_property_stats_register_returns_v1_contract_and_legacy_status(): void
    {
        $response = $this->postJson('/api/property_stats/register', [
            '_i' => 1,
            'views_detail' => 1,
        ]);

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
            ]);
    }
}

