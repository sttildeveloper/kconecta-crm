<?php

namespace Tests\Feature\Api;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProviderServicesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_profile_requires_authentication_and_provider_role(): void
    {
        $this->getJson('/api/agent/provider-profile')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $client = $this->makeUser(User::LEVEL_FINAL_CLIENT, 'client-profile@test.dev');
        $this->actingAs($client, 'sanctum')
            ->getJson('/api/agent/provider-profile')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_provider_can_read_profile_without_a_legacy_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-profile@test.dev');
        $provider->update([
            'provider_title' => 'Reformas Test',
            'provider_description' => 'Ficha directa del proveedor',
            'provider_availability' => 'Lunes a viernes',
            'provider_page_url' => 'https://example.test',
        ]);
        $type = ServiceType::query()->create(['name' => 'Reformas']);
        ProviderService::query()->create([
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $type->id,
        ]);
        UserAddress::query()->create([
            'user_id' => (int) $provider->id,
            'address' => 'Calle Test 1',
            'city' => 'Barcelona',
        ]);
        CoverImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
            'url' => 'provider-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
            'url' => 'provider-gallery.webp',
        ]);

        $this->actingAs($provider, 'sanctum')
            ->getJson('/api/agent/provider-profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Reformas Test')
            ->assertJsonPath('data.description', 'Ficha directa del proveedor')
            ->assertJsonPath('data.address', 'Calle Test 1')
            ->assertJsonPath('data.specialty_ids.0', (int) $type->id)
            ->assertJsonPath('data.more_images.0.file', 'provider-gallery.webp');

        $this->assertSame(0, Service::query()->where('user_id', $provider->id)->count());
    }

    public function test_profile_patch_updates_data_media_and_specialties_without_creating_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-update@test.dev');
        $type = ServiceType::query()->create(['name' => 'Electricidad']);

        $response = $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'title' => 'Electricista Barcelona',
                'description' => 'Instalaciones y reparaciones',
                'availability' => '24/7',
                'page_url' => 'https://electricista.test',
                'specialty_ids' => [(int) $type->id],
                'address' => 'Carrer de Mallorca 1',
                'city' => 'Barcelona',
                'province' => 'Barcelona',
                'postal_code' => '08001',
                'country' => 'Espana',
                'latitude' => '41.3874',
                'longitude' => '2.1686',
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
                'more_images' => [UploadedFile::fake()->image('gallery.jpg')],
                'video' => UploadedFile::fake()->create('intro.mp4', 512, 'video/mp4'),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Electricista Barcelona')
            ->assertJsonPath('data.city', 'Barcelona')
            ->assertJsonPath('data.specialty_ids.0', (int) $type->id);

        $this->assertDatabaseHas('user', [
            'id' => (int) $provider->id,
            'provider_title' => 'Electricista Barcelona',
            'provider_description' => 'Instalaciones y reparaciones',
        ]);
        $this->assertDatabaseHas('cover_image', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
        $this->assertDatabaseHas('more_images', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
        $this->assertDatabaseHas('video', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
        $this->assertDatabaseHas('provider_services', [
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $type->id,
        ]);
        $this->assertSame(0, Service::query()->where('user_id', $provider->id)->count());
    }

    public function test_legacy_service_alias_updates_the_provider_profile_without_creating_a_service(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-alias@test.dev');

        $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/services', [
                'title' => 'Ficha mediante alias',
                'description' => 'Compatibilidad movil',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Ficha mediante alias');

        $this->actingAs($provider, 'sanctum')
            ->getJson('/api/agent/services/999999')
            ->assertOk()
            ->assertJsonPath('data.title', 'Ficha mediante alias');

        $this->assertSame(0, Service::query()->where('user_id', $provider->id)->count());
    }

    public function test_legacy_delete_endpoint_cannot_delete_provider_profile(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-delete@test.dev');

        $this->actingAs($provider, 'sanctum')
            ->deleteJson('/api/agent/services/123')
            ->assertStatus(410)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('user', ['id' => (int) $provider->id]);
    }

    public function test_provider_can_only_delete_gallery_images_from_own_profile(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-gallery@test.dev');
        $otherProvider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'other-gallery@test.dev');
        $ownImage = MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => 'own.webp',
        ]);
        $foreignImage = MoreImage::query()->create([
            'provider_user_id' => (int) $otherProvider->id,
            'url' => 'foreign.webp',
        ]);

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'delete_more_images' => [(int) $ownImage->id, (int) $foreignImage->id],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('more_images', ['id' => (int) $ownImage->id]);
        $this->assertDatabaseHas('more_images', ['id' => (int) $foreignImage->id]);
    }

    public function test_custom_upload_replaces_provider_default_media(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-replace-default@test.dev');
        CoverImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'default-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'default-gallery-1.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'default-gallery-2.webp',
        ]);

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'cover_image' => UploadedFile::fake()->image('custom-cover.jpg'),
                'more_images' => [UploadedFile::fake()->image('custom-gallery.jpg')],
            ])
            ->assertOk();

        $this->assertDatabaseHas('cover_image', [
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => false,
            'source_provider_user_id' => null,
        ]);
        $this->assertSame(1, MoreImage::query()->where('provider_user_id', $provider->id)->count());
        $this->assertDatabaseHas('more_images', [
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => false,
            'source_provider_user_id' => null,
        ]);
    }

    public function test_profile_patch_validates_media_and_specialty_inputs(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-validation@test.dev');

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'specialty_ids' => [999999],
                'cover_image' => UploadedFile::fake()->create('cover.pdf', 32, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['specialty_ids.0', 'cover_image']]);
    }

    public function test_provider_can_list_specialty_catalog(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-catalog@test.dev');
        $type = ServiceType::query()->create(['name' => 'Pintura']);

        $this->actingAs($provider, 'sanctum')
            ->getJson('/api/agent/service-types')
            ->assertOk()
            ->assertJsonPath('data.0.id', (int) $type->id)
            ->assertJsonPath('data.0.name', 'Pintura');
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
