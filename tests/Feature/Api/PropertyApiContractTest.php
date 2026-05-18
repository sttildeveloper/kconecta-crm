<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PropertyApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_properties_requires_auth_and_returns_v1_error_contract(): void
    {
        $response = $this->getJson('/api/agent/properties');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
            ]);
    }

    public function test_property_types_returns_v1_success_contract_for_authenticated_user(): void
    {
        $user = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-property-contract@test.dev');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/agent/property-types');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
            ]);
    }

    public function test_property_form_catalogs_invalid_type_returns_v1_error_contract(): void
    {
        $user = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-property-catalogs@test.dev');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/agent/property-form-catalogs?type_id=9999');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
            ]);
    }

    public function test_legacy_delete_more_image_endpoint_returns_410_contract(): void
    {
        $response = $this->getJson('/api/delete_more_image?id=1');

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors',
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

