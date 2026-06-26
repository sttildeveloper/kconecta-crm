<?php

namespace Tests\Feature;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceProviderRating;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\ServiceWorkCode;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProviderDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_service_provider_and_cleanup_related_service_data(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-delete-provider@test.dev');
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-delete-provider@test.dev');
        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-delete-provider@test.dev');

        $serviceType = ServiceType::query()->create(['name' => 'Electricista']);
        $service = Service::query()->create([
            'title' => 'Servicio provider',
            'description' => 'Descripcion service',
            'availability' => '24/7',
            'user_id' => (int) $provider->id,
        ]);

        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Calle test',
        ]);

        CoverImage::query()->create([
            'service_id' => (int) $service->id,
            'url' => 'cover.webp',
        ]);

        MoreImage::query()->create([
            'service_id' => (int) $service->id,
            'url' => 'gallery.webp',
        ]);

        Video::query()->create([
            'service_id' => (int) $service->id,
            'url' => 'video.mp4',
        ]);

        ServiceAddress::query()->create([
            'service_id' => (int) $service->id,
            'address' => 'Direccion service',
        ]);

        ServiceTypeLink::query()->create([
            'service_id' => (int) $service->id,
            'service_type_id' => (int) $serviceType->id,
        ]);

        ServiceProviderRating::query()->create([
            'provider_user_id' => (int) $provider->id,
            'client_user_id' => (int) $client->id,
            'stars' => 5,
        ]);

        ServiceWorkCode::query()->create([
            'provider_user_id' => (int) $provider->id,
            'code' => 'PROVIDER-CODE-001',
            'is_used' => true,
            'used_by_user_id' => (int) $client->id,
            'used_at' => now(),
        ]);

        if (DB::getSchemaBuilder()->hasTable('service_profile_visits')) {
            DB::table('service_profile_visits')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => (int) $service->id,
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('service_contact_clicks')) {
            DB::table('service_contact_clicks')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => (int) $service->id,
                'channel' => 'whatsapp',
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get('/user/delete?id=' . $provider->id)
            ->assertOk()
            ->assertJsonPath('status', 200);

        $this->assertDatabaseMissing('user', ['id' => (int) $provider->id]);
        $this->assertDatabaseMissing('service', ['id' => (int) $service->id]);
        $this->assertDatabaseMissing('user_address', ['user_id' => (int) $provider->id]);
        $this->assertDatabaseMissing('cover_image', ['service_id' => (int) $service->id]);
        $this->assertDatabaseMissing('more_images', ['service_id' => (int) $service->id]);
        $this->assertDatabaseMissing('video', ['service_id' => (int) $service->id]);
        $this->assertDatabaseMissing('service_address', ['service_id' => (int) $service->id]);
        $this->assertDatabaseMissing('service_types', ['service_id' => (int) $service->id]);
        $this->assertDatabaseMissing('service_provider_ratings', ['provider_user_id' => (int) $provider->id]);
        $this->assertDatabaseMissing('service_work_codes', ['provider_user_id' => (int) $provider->id]);

        if (DB::getSchemaBuilder()->hasTable('service_profile_visits')) {
            $this->assertDatabaseMissing('service_profile_visits', ['provider_user_id' => (int) $provider->id]);
        }

        if (DB::getSchemaBuilder()->hasTable('service_contact_clicks')) {
            $this->assertDatabaseMissing('service_contact_clicks', ['provider_user_id' => (int) $provider->id]);
        }
    }

    public function test_admin_cannot_delete_non_provider_user_from_dashboard_action(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-delete-non-provider@test.dev');
        $agent = $this->makeUser(User::LEVEL_AGENT, 'agent-delete-non-provider@test.dev');

        $this->actingAs($admin)
            ->get('/user/delete?id=' . $agent->id)
            ->assertStatus(403)
            ->assertJsonPath('message', 'Solo puedes eliminar proveedores de servicio.');

        $this->assertDatabaseHas('user', ['id' => (int) $agent->id]);
    }

    public function test_admin_delete_also_cleans_provider_metrics_without_service_profile(): void
    {
        $admin = $this->makeUser(User::LEVEL_ADMIN, 'admin-delete-provider-metrics@test.dev');
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-delete-provider-metrics@test.dev');

        if (DB::getSchemaBuilder()->hasTable('service_profile_visits')) {
            DB::table('service_profile_visits')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => null,
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('service_contact_clicks')) {
            DB::table('service_contact_clicks')->insert([
                'provider_user_id' => (int) $provider->id,
                'service_id' => null,
                'channel' => 'whatsapp',
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get('/user/delete?id=' . $provider->id)
            ->assertOk()
            ->assertJsonPath('status', 200);

        $this->assertDatabaseMissing('user', ['id' => (int) $provider->id]);

        if (DB::getSchemaBuilder()->hasTable('service_profile_visits')) {
            $this->assertDatabaseMissing('service_profile_visits', ['provider_user_id' => (int) $provider->id]);
        }

        if (DB::getSchemaBuilder()->hasTable('service_contact_clicks')) {
            $this->assertDatabaseMissing('service_contact_clicks', ['provider_user_id' => (int) $provider->id]);
        }
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
