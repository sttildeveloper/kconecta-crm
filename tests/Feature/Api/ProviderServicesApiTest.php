<?php

namespace Tests\Feature\Api;

use App\Models\CoverImage;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProviderServicesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/agent/services');
        $response->assertStatus(401);
    }

    public function test_non_provider_cannot_create_services(): void
    {
        $user = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client@test.dev');
        $serviceType = ServiceType::query()->create(['name' => 'Fontaneria']);

        $response = $this->actingAs($user, 'sanctum')->post('/api/agent/services', [
            'availability' => 'Lun-Vie',
            'description' => 'Servicio de prueba',
            'service_type' => [(int) $serviceType->id],
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_provider_can_create_and_list_only_own_services(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider@test.dev');
        $otherProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider2@test.dev');
        $serviceType = ServiceType::query()->create(['name' => 'Pintura']);

        Service::query()->create([
            'title' => 'Servicio de otro',
            'description' => 'No debe aparecer',
            'availability' => 'Siempre',
            'user_id' => (int) $otherProvider->id,
        ]);

        $createResponse = $this->actingAs($provider, 'sanctum')->post('/api/agent/services', [
            'title' => 'Mi servicio',
            'availability' => 'Lun-Vie',
            'description' => 'Detalle del servicio',
            'service_type' => [(int) $serviceType->id],
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Mi servicio');

        $listResponse = $this->actingAs($provider, 'sanctum')->getJson('/api/agent/services');

        $listResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mi servicio');
    }

    public function test_provider_cannot_access_foreign_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-a@test.dev');
        $otherProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-b@test.dev');

        $service = Service::query()->create([
            'title' => 'Servicio B',
            'description' => 'Privado',
            'availability' => 'Siempre',
            'user_id' => (int) $otherProvider->id,
        ]);

        $this->actingAs($provider, 'sanctum')
            ->getJson('/api/agent/services/' . $service->id)
            ->assertStatus(404);
    }

    public function test_provider_can_update_and_delete_own_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-c@test.dev');
        $serviceTypeA = ServiceType::query()->create(['name' => 'Electricidad']);
        $serviceTypeB = ServiceType::query()->create(['name' => 'Gas']);

        $service = Service::query()->create([
            'title' => 'Servicio original',
            'description' => 'Descripcion original',
            'availability' => 'Mananas',
            'user_id' => (int) $provider->id,
        ]);
        CoverImage::query()->create([
            'service_id' => (int) $service->id,
            'url' => 'old-cover.jpg',
        ]);
        ServiceTypeLink::query()->create([
            'service_id' => (int) $service->id,
            'service_type_id' => (int) $serviceTypeA->id,
        ]);

        $this->actingAs($provider, 'sanctum')->patch('/api/agent/services/' . $service->id, [
            'title' => 'Servicio actualizado',
            'service_type' => [(int) $serviceTypeB->id],
        ])->assertOk()
            ->assertJsonPath('data.title', 'Servicio actualizado');

        $this->assertDatabaseHas('service', [
            'id' => (int) $service->id,
            'title' => 'Servicio actualizado',
        ]);
        $this->assertDatabaseHas('service_types', [
            'service_id' => (int) $service->id,
            'service_type_id' => (int) $serviceTypeB->id,
        ]);

        $this->actingAs($provider, 'sanctum')
            ->deleteJson('/api/agent/services/' . $service->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('service', ['id' => (int) $service->id]);
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

