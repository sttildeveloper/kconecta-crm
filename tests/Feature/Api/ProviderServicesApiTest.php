<?php

namespace Tests\Feature\Api;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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
            ->assertJsonPath('data.gallery_max_images', 5)
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
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['gallery_delete_ids']]);

        $this->assertDatabaseHas('more_images', ['id' => (int) $ownImage->id]);
        $this->assertDatabaseHas('more_images', ['id' => (int) $foreignImage->id]);
    }

    public function test_custom_upload_replaces_provider_default_media(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-replace-default@test.dev');
        $providerDirectory = public_path('img/uploads/providers/'.$provider->id);
        File::ensureDirectoryExists($providerDirectory);
        File::put($providerDirectory.'/default-cover.webp', 'default-cover');
        File::put($providerDirectory.'/default-gallery-1.webp', 'default-gallery-1');
        File::put($providerDirectory.'/default-gallery-2.webp', 'default-gallery-2');
        CoverImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'providers/'.$provider->id.'/default-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'providers/'.$provider->id.'/default-gallery-1.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'is_provider_default' => true,
            'source_provider_user_id' => 67,
            'url' => 'providers/'.$provider->id.'/default-gallery-2.webp',
        ]);

        try {
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
            $this->assertFileDoesNotExist($providerDirectory.'/default-cover.webp');
            $this->assertFileDoesNotExist($providerDirectory.'/default-gallery-1.webp');
            $this->assertFileDoesNotExist($providerDirectory.'/default-gallery-2.webp');
        } finally {
            File::deleteDirectory($providerDirectory);
        }
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

    public function test_profile_patch_rejects_more_than_five_new_gallery_images_before_updating_profile(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-gallery-batch-limit@test.dev');

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'title' => 'No debe guardarse',
                'more_images' => collect(range(1, 6))
                    ->map(fn (int $number) => UploadedFile::fake()->image('gallery-'.$number.'.jpg'))
                    ->all(),
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['more_images']]);

        $this->assertNull($provider->fresh()->provider_title);
        $this->assertSame(0, MoreImage::query()->where('provider_user_id', $provider->id)->count());
    }

    public function test_mobile_legacy_service_alias_uses_the_same_gallery_limit(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-mobile-gallery-limit@test.dev');

        $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/services', [
                'more_images' => collect(range(1, 6))
                    ->map(fn (int $number) => UploadedFile::fake()->image('mobile-gallery-'.$number.'.jpg'))
                    ->all(),
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['more_images']]);

        $this->assertSame(0, MoreImage::query()->where('provider_user_id', $provider->id)->count());
    }

    public function test_profile_patch_enforces_projected_gallery_total_and_allows_delete_then_replace_at_limit(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-gallery-total-limit@test.dev');
        $images = collect(range(1, 5))->map(fn (int $number) => MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => 'existing-gallery-'.$number.'.webp',
            'is_provider_default' => false,
        ]));

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'more_images' => [UploadedFile::fake()->image('sixth.jpg')],
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['more_images']]);

        $this->assertSame(5, MoreImage::query()->where('provider_user_id', $provider->id)->count());

        $this->actingAs($provider, 'sanctum')
            ->patch('/api/agent/provider-profile', [
                'delete_more_images' => [(int) $images->first()->id],
                'more_images' => [UploadedFile::fake()->image('replacement.jpg')],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('more_images', ['id' => (int) $images->first()->id]);
        $this->assertSame(5, MoreImage::query()->where('provider_user_id', $provider->id)->count());
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

    public function test_commercial_patch_is_partial_and_does_not_modify_personal_fields(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'commercial-separation@test.dev');
        $provider->forceFill([
            'address' => 'Direccion personal',
            'provider_phone' => '600111222',
            'provider_landline_phone' => '930001111',
        ])->save();

        $this->actingAs($provider, 'sanctum')
            ->patchJson('/api/agent/provider-profile', [
                'title' => 'Ficha comercial',
                'phone' => '699999999',
                'first_name' => 'Manipulado',
                'email' => 'manipulado@test.dev',
                'document_number' => 'OTRO',
                'password' => 'manipulada',
                'photo' => 'otra.webp',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Ficha comercial')
            ->assertJsonPath('data.phone', '699999999');

        $provider->refresh();
        $this->assertSame('Test', $provider->first_name);
        $this->assertSame('commercial-separation@test.dev', $provider->email);
        $this->assertNull($provider->document_number);
        $this->assertSame('600000000', $provider->phone);
        $this->assertSame('Direccion personal', $provider->address);
        $this->assertSame('699999999', $provider->provider_phone);
        $this->assertTrue(Hash::check('password', $provider->password));

        $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/provider-profile', [
                '_method' => 'PATCH',
                'provider_logo' => UploadedFile::fake()->image('must-be-ignored.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertNull($provider->fresh()->photo);
    }

    public function test_multipart_contract_decodes_specialties_and_can_clear_them(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-multipart@test.dev');
        $first = ServiceType::query()->create(['name' => 'Electricidad']);
        $second = ServiceType::query()->create(['name' => 'Fontaneria']);

        $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/provider-profile', [
                '_method' => 'PATCH',
                'specialty_ids' => json_encode([(int) $first->id, (int) $second->id]),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.specialty_ids', [(int) $first->id, (int) $second->id]);

        $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/provider-profile', [
                '_method' => 'PATCH',
                'specialty_ids' => '[]',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.specialty_ids', []);

        $this->assertDatabaseMissing('provider_services', ['provider_id' => (int) $provider->id]);
    }

    public function test_canonical_gallery_upload_appends_real_images_and_converts_to_webp(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-gallery-upload@test.dev');
        $existingName = 'existing-'.$provider->id.'.webp';
        File::put(public_path('img/uploads/'.$existingName), 'existing');
        $existing = MoreImage::query()->create([
            'provider_user_id' => (int) $provider->id,
            'url' => $existingName,
            'position' => 0,
            'is_provider_default' => false,
        ]);

        try {
            $response = $this->actingAs($provider, 'sanctum')
                ->post('/api/agent/provider-profile', [
                    '_method' => 'PATCH',
                    'gallery_images' => [UploadedFile::fake()->image('new-image.png', 1200, 800)],
                ], ['Accept' => 'application/json']);

            $response->assertOk()
                ->assertJsonCount(2, 'data.gallery')
                ->assertJsonPath('data.gallery.0.id', (int) $existing->id)
                ->assertJsonPath('data.gallery.0.position', 0)
                ->assertJsonPath('data.gallery.1.position', 1);

            $newImage = MoreImage::query()
                ->where('provider_user_id', $provider->id)
                ->where('id', '<>', $existing->id)
                ->firstOrFail();
            $this->assertStringEndsWith('.webp', $newImage->url);
            $this->assertFileExists(public_path('img/uploads/'.$newImage->url));
        } finally {
            MoreImage::query()->where('provider_user_id', $provider->id)->pluck('url')->each(
                fn ($file) => File::delete(public_path('img/uploads/'.$file))
            );
        }
    }

    public function test_gallery_order_and_delete_are_validated_persisted_and_remove_physical_file(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-gallery-order@test.dev');
        $images = collect(range(1, 3))->map(function (int $number) use ($provider) {
            $name = 'ordered-'.$provider->id.'-'.$number.'.webp';
            File::put(public_path('img/uploads/'.$name), 'image-'.$number);

            return MoreImage::query()->create([
                'provider_user_id' => (int) $provider->id,
                'url' => $name,
                'position' => $number - 1,
            ]);
        });
        $reversedIds = $images->reverse()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        try {
            $this->actingAs($provider, 'sanctum')
                ->patchJson('/api/agent/provider-profile', ['gallery_order' => $reversedIds])
                ->assertOk()
                ->assertJsonPath('data.gallery.0.id', $reversedIds[0])
                ->assertJsonPath('data.gallery.1.id', $reversedIds[1])
                ->assertJsonPath('data.gallery.2.id', $reversedIds[2]);

            $deleteImage = $images->first();
            $this->actingAs($provider, 'sanctum')
                ->post('/api/agent/provider-profile', [
                    '_method' => 'PATCH',
                    'gallery_delete_ids' => json_encode([(int) $deleteImage->id]),
                ], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonCount(2, 'data.gallery');

            $this->assertDatabaseMissing('more_images', ['id' => (int) $deleteImage->id]);
            $this->assertFileDoesNotExist(public_path('img/uploads/'.$deleteImage->url));
        } finally {
            $images->each(fn (MoreImage $image) => File::delete(public_path('img/uploads/'.$image->url)));
        }
    }

    public function test_cover_and_video_replacement_keep_new_files_and_delete_old_files(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-media-replace@test.dev');
        $oldCover = 'old-cover-'.$provider->id.'.webp';
        $oldVideo = 'old-video-'.$provider->id.'.mp4';
        File::put(public_path('img/uploads/'.$oldCover), 'old cover');
        File::put(public_path('video/uploads/'.$oldVideo), 'old video');
        CoverImage::query()->create(['provider_user_id' => $provider->id, 'url' => $oldCover]);
        Video::query()->create(['provider_user_id' => $provider->id, 'url' => $oldVideo]);

        $response = $this->actingAs($provider, 'sanctum')
            ->post('/api/agent/provider-profile', [
                '_method' => 'PATCH',
                'cover_image' => UploadedFile::fake()->image('cover.png', 1600, 900),
                'video' => UploadedFile::fake()->create('presentation.mp4', 100, 'video/mp4'),
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.cover_image_path', fn ($path) => is_string($path) && str_ends_with($path, '.webp'))
            ->assertJsonPath('data.video_path', fn ($path) => is_string($path) && str_ends_with($path, '.mp4'));

        $cover = CoverImage::query()->where('provider_user_id', $provider->id)->firstOrFail();
        $video = Video::query()->where('provider_user_id', $provider->id)->firstOrFail();
        $this->assertFileExists(public_path('img/uploads/'.$cover->url));
        $this->assertFileExists(public_path('video/uploads/'.$video->url));
        $this->assertFileDoesNotExist(public_path('img/uploads/'.$oldCover));
        $this->assertFileDoesNotExist(public_path('video/uploads/'.$oldVideo));

        File::delete(public_path('img/uploads/'.$cover->url));
        File::delete(public_path('video/uploads/'.$video->url));
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
