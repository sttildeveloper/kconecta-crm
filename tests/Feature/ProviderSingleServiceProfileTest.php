<?php

namespace Tests\Feature;

use App\Models\CoverImage;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderSingleServiceProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_is_redirected_from_legacy_create_form_to_canonical_profile(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-single-profile@test.dev');
        $service = Service::query()->create([
            'title' => null,
            'description' => 'Perfil actual',
            'availability' => '24/7',
            'user_id' => (int) $provider->id,
        ]);

        $this->actingAs($provider)
            ->get('/post/create_form/service')
            ->assertRedirect('/post/provider-profile/edit');
    }

    public function test_legacy_create_endpoint_never_creates_another_service(): void
    {
        Storage::fake('local');

        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-second-profile@test.dev');
        $existingService = Service::query()->create([
            'title' => null,
            'description' => 'Perfil actual',
            'availability' => '24/7',
            'user_id' => (int) $provider->id,
        ]);
        $type = ServiceType::query()->create(['name' => 'Albanileria']);

        $response = $this->actingAs($provider)
            ->post('/post/create_service', [
                'availability' => 'Lunes a viernes',
                'description' => 'No deberia crear una segunda ficha',
                'page_url' => 'https://example.test',
                'service_type' => [(int) $type->id],
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            ]);

        $response->assertRedirect('/post/provider-profile/edit');

        $this->assertSame(1, Service::query()->where('user_id', (int) $provider->id)->count());
        $this->assertDatabaseMissing('provider_services', [
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $type->id,
        ]);
        $this->assertSame(0, CoverImage::query()->count());
    }

    public function test_provider_without_legacy_service_can_open_and_update_profile(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-direct-profile@test.dev');
        $type = ServiceType::query()->create(['name' => 'Fontaneria']);

        $this->actingAs($provider)
            ->get('/post/provider-profile/edit')
            ->assertOk()
            ->assertSee('Actualizar ficha del proveedor')
            ->assertSee('/post/provider-profile', false);

        $this->actingAs($provider)
            ->post('/post/provider-profile', [
                'title' => 'Fontanero Barcelona',
                'description' => 'Reparaciones e instalaciones',
                'availability' => 'Lunes a viernes',
                'page_url' => 'https://fontanero.test',
                'service_type' => [(int) $type->id],
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
                'more_images' => [UploadedFile::fake()->image('gallery.jpg')],
            ])
            ->assertRedirect('/post/services');

        $this->assertDatabaseHas('user', [
            'id' => (int) $provider->id,
            'provider_title' => 'Fontanero Barcelona',
            'provider_description' => 'Reparaciones e instalaciones',
        ]);
        $this->assertDatabaseHas('cover_image', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
        $this->assertDatabaseHas('more_images', [
            'provider_user_id' => (int) $provider->id,
            'service_id' => null,
        ]);
        $this->assertDatabaseHas('provider_services', [
            'provider_id' => (int) $provider->id,
            'service_type_id' => (int) $type->id,
        ]);
        $this->assertSame(0, Service::query()->where('user_id', (int) $provider->id)->count());
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
