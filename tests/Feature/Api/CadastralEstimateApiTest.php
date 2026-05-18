<?php

namespace Tests\Feature\Api;

use App\Models\CadastralPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastralEstimateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_returns_data_for_existing_postal_code(): void
    {
        CadastralPrice::query()->create([
            'province' => 'Barcelona',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Gracia',
            'postal_code' => '08012',
            'price_m2_eur' => 3000,
            'import_batch_id' => 'batch-a',
        ]);
        CadastralPrice::query()->create([
            'province' => 'Barcelona',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Sarria',
            'postal_code' => '08012',
            'price_m2_eur' => 3500,
            'import_batch_id' => 'batch-a',
        ]);

        $response = $this->getJson('/api/cadastral/estimate?postal_code=08012&m2=100&municipality=Barcelona');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estimated_value', 325000)
            ->assertJsonPath('data.base_stats.total_areas', 2);
    }

    public function test_estimate_returns_404_for_unknown_postal_code_without_municipality_fallback(): void
    {
        CadastralPrice::query()->create([
            'province' => 'Barcelona',
            'municipality' => 'Barcelona',
            'neighborhood' => 'Gracia',
            'postal_code' => '08012',
            'price_m2_eur' => 3000,
            'import_batch_id' => 'batch-a',
        ]);

        $response = $this->getJson('/api/cadastral/estimate?postal_code=99999&m2=100');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
