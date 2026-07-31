<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_create_update_and_delete_service_types(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-service-types@test.dev');
        $existing = ServiceType::query()->create(['name' => 'Cerrajeria']);

        $this->actingAs($admin)
            ->get('/admin/service-types')
            ->assertOk()
            ->assertSee('Tipos de servicio')
            ->assertSee('Cerrajeria');

        $this->actingAs($admin)
            ->post('/admin/service-types/save', ['name' => 'Electricista'])
            ->assertRedirect('/admin/service-types');

        $this->assertDatabaseHas('service_type', [
            'name' => 'Electricista',
        ]);

        $this->actingAs($admin)
            ->post('/admin/service-types/update/' . $existing->id, ['name' => 'Cerrajeria urgente'])
            ->assertRedirect('/admin/service-types');

        $this->assertDatabaseHas('service_type', [
            'id' => (int) $existing->id,
            'name' => 'Cerrajeria urgente',
        ]);

        $deletable = ServiceType::query()->create(['name' => 'Jardineria']);

        $this->actingAs($admin)
            ->post('/admin/service-types/delete', ['id' => (int) $deletable->id])
            ->assertRedirect('/admin/service-types');

        $this->assertDatabaseMissing('service_type', [
            'id' => (int) $deletable->id,
        ]);
    }

    public function test_admin_cannot_delete_service_type_that_is_linked_to_services(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-linked-service-types@test.dev');
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-linked-service-types@test.dev');
        $type = ServiceType::query()->create(['name' => 'Fontaneria']);
        $service = Service::query()->create([
            'title' => 'Fontanero express',
            'description' => 'Servicio de prueba',
            'availability' => 'Siempre',
            'user_id' => (int) $provider->id,
        ]);

        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $type->id,
        ]);

        $this->actingAs($admin)
            ->post('/admin/service-types/delete', ['id' => (int) $type->id])
            ->assertRedirect('/admin/service-types')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('service_type', [
            'id' => (int) $type->id,
            'name' => 'Fontaneria',
        ]);
    }

    public function test_non_admin_cannot_access_service_type_management(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-service-types@test.dev');

        $this->actingAs($provider)
            ->get('/admin/service-types')
            ->assertStatus(403);
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
