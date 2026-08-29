<?php

namespace Tests\Feature;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackfillProviderServiceTypeMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_only_default_media_with_individual_service_type_copies(): void
    {
        $sourceDirectory = storage_path('framework/testing/service-type-media-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($sourceDirectory);
        File::put($sourceDirectory.'/7-carpinteria.webp', 'carpentry-image');
        File::put($sourceDirectory.'/8-cerrajeria.webp', 'locksmith-image');
        $fallbackPath = $sourceDirectory.'/general.webp';
        File::put($fallbackPath, 'general-service-image');

        DB::table('service_type')->updateOrInsert(['id' => 7], ['name' => 'Carpinteria']);
        DB::table('service_type')->updateOrInsert(['id' => 8], ['name' => 'Cerrajeria']);

        $single = $this->provider('service-type-single@test.dev');
        $multiple = $this->provider('service-type-multiple@test.dev');
        $realCover = $this->provider('service-type-real-cover@test.dev');
        $realGallery = $this->provider('service-type-real-gallery@test.dev');
        $withoutSpecialty = $this->provider('service-type-none@test.dev');

        $this->specialties($single, [7]);
        $this->specialties($multiple, [7, 8]);
        $this->specialties($realCover, [7]);
        $this->specialties($realGallery, [7, 8]);

        $oldFiles = [];
        foreach ([$single, $multiple, $realCover, $realGallery, $withoutSpecialty] as $provider) {
            $oldCover = 'test-provider-'.$provider->id.'-old-cover.webp';
            $oldGallery = 'test-provider-'.$provider->id.'-old-gallery.webp';
            File::put(public_path('img/uploads/'.$oldCover), 'old-cover-'.$provider->id);
            File::put(public_path('img/uploads/'.$oldGallery), 'old-gallery-'.$provider->id);
            $oldFiles[] = public_path('img/uploads/'.$oldCover);
            $oldFiles[] = public_path('img/uploads/'.$oldGallery);

            CoverImage::query()->create([
                'provider_user_id' => (int) $provider->id,
                'url' => $oldCover,
                'is_provider_default' => true,
            ]);
            MoreImage::query()->create([
                'provider_user_id' => (int) $provider->id,
                'url' => $oldGallery,
                'is_provider_default' => true,
            ]);
        }

        CoverImage::query()->where('provider_user_id', $realCover->id)->update([
            'url' => 'real-cover.webp',
            'is_provider_default' => false,
        ]);
        MoreImage::query()->where('provider_user_id', $realGallery->id)->update([
            'url' => 'real-gallery.webp',
            'is_provider_default' => false,
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $realGallery->id,
            'url' => 'test-extra-default.webp',
            'is_provider_default' => true,
        ]);
        File::put(public_path('img/uploads/test-extra-default.webp'), 'extra-default');
        $oldFiles[] = public_path('img/uploads/test-extra-default.webp');

        $providerDirectories = collect([$single, $multiple, $realCover, $realGallery, $withoutSpecialty])
            ->map(fn (User $provider) => public_path('img/uploads/providers/'.$provider->id))
            ->all();

        try {
            $this->artisan('providers:backfill-service-type-media', [
                '--source-dir' => $sourceDirectory,
                '--fallback-image' => $fallbackPath,
            ])
                ->expectsOutputToContain('Simulacion completada')
                ->assertSuccessful();
            $this->assertStringContainsString('old-cover', (string) CoverImage::query()->where('provider_user_id', $single->id)->value('url'));

            $this->artisan('providers:backfill-service-type-media', [
                '--source-dir' => $sourceDirectory,
                '--fallback-image' => $fallbackPath,
                '--apply' => true,
            ])->expectsOutputToContain('Proceso completado')->assertSuccessful();

            $singleCover = CoverImage::query()->where('provider_user_id', $single->id)->firstOrFail();
            $this->assertTrue((bool) $singleCover->is_provider_default);
            $this->assertStringStartsWith('providers/'.$single->id.'/default-cover-7-', $singleCover->url);
            $this->assertSame(0, MoreImage::query()->where('provider_user_id', $single->id)->count());

            $multipleCover = CoverImage::query()->where('provider_user_id', $multiple->id)->firstOrFail();
            $multipleGallery = MoreImage::query()->where('provider_user_id', $multiple->id)->firstOrFail();
            $this->assertStringStartsWith('providers/'.$multiple->id.'/default-cover-7-', $multipleCover->url);
            $this->assertStringStartsWith('providers/'.$multiple->id.'/default-gallery-8-', $multipleGallery->url);

            $this->assertSame('real-cover.webp', CoverImage::query()->where('provider_user_id', $realCover->id)->value('url'));
            $this->assertStringStartsWith(
                'providers/'.$realCover->id.'/default-gallery-7-',
                (string) MoreImage::query()->where('provider_user_id', $realCover->id)->value('url')
            );
            $this->assertSame('real-gallery.webp', MoreImage::query()->where('provider_user_id', $realGallery->id)->value('url'));
            $this->assertSame(1, MoreImage::query()->where('provider_user_id', $realGallery->id)->count());

            $withoutSpecialtyCover = (string) CoverImage::query()
                ->where('provider_user_id', $withoutSpecialty->id)
                ->value('url');
            $this->assertStringStartsWith(
                'providers/'.$withoutSpecialty->id.'/default-cover-general-',
                $withoutSpecialtyCover
            );
            $this->assertFileExists(public_path('img/uploads/'.$withoutSpecialtyCover));
            $this->assertStringContainsString(
                'old-gallery',
                (string) MoreImage::query()->where('provider_user_id', $withoutSpecialty->id)->value('url')
            );

            $this->assertFileExists(public_path('img/uploads/'.$multipleCover->url));
            $this->assertSame('carpentry-image', File::get(public_path('img/uploads/'.$multipleCover->url)));
            $this->assertSame('locksmith-image', File::get(public_path('img/uploads/'.$multipleGallery->url)));

            $this->artisan('providers:backfill-service-type-media', [
                '--source-dir' => $sourceDirectory,
                '--fallback-image' => $fallbackPath,
                '--apply' => true,
            ])->expectsOutputToContain('0 portadas, 0 imagenes de galeria, 0 archivos nuevos')->assertSuccessful();
        } finally {
            File::deleteDirectory($sourceDirectory);
            foreach ($providerDirectories as $directory) {
                File::deleteDirectory($directory);
            }
            File::delete($oldFiles);
        }
    }

    public function test_covers_only_populates_fallbacks_and_does_not_modify_gallery(): void
    {
        $sourceDirectory = storage_path('framework/testing/service-type-cover-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($sourceDirectory);
        File::put($sourceDirectory.'/7-carpinteria.webp', 'carpentry-cover');
        $fallbackPath = $sourceDirectory.'/general.webp';
        File::put($fallbackPath, 'general-cover');
        DB::table('service_type')->updateOrInsert(['id' => 7], ['name' => 'Carpinteria']);

        $typed = $this->provider('cover-only-typed@test.dev');
        $untyped = $this->provider('cover-only-untyped@test.dev');
        $this->specialties($typed, [7]);
        $galleryFile = 'test-cover-only-gallery.webp';
        File::put(public_path('img/uploads/'.$galleryFile), 'gallery-must-remain');
        MoreImage::query()->create([
            'provider_user_id' => (int) $typed->id,
            'url' => $galleryFile,
            'is_provider_default' => true,
        ]);

        try {
            $this->artisan('providers:backfill-service-type-media', [
                '--source-dir' => $sourceDirectory,
                '--fallback-image' => $fallbackPath,
                '--covers-only' => true,
                '--apply' => true,
            ])->expectsOutputToContain('2 portadas, 0 imagenes de galeria')->assertSuccessful();

            $this->assertStringStartsWith(
                'providers/'.$typed->id.'/default-cover-7-',
                (string) CoverImage::query()->where('provider_user_id', $typed->id)->value('url')
            );
            $this->assertStringStartsWith(
                'providers/'.$untyped->id.'/default-cover-general-',
                (string) CoverImage::query()->where('provider_user_id', $untyped->id)->value('url')
            );
            $this->assertSame($galleryFile, MoreImage::query()->where('provider_user_id', $typed->id)->value('url'));
            $this->assertFileExists(public_path('img/uploads/'.$galleryFile));
        } finally {
            File::deleteDirectory($sourceDirectory);
            File::deleteDirectory(public_path('img/uploads/providers/'.$typed->id));
            File::deleteDirectory(public_path('img/uploads/providers/'.$untyped->id));
            File::delete(public_path('img/uploads/'.$galleryFile));
        }
    }

    private function provider(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Proveedor',
            'last_name' => 'Prueba',
            'user_name' => 'provider-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);
    }

    private function specialties(User $provider, array $typeIds): void
    {
        foreach ($typeIds as $typeId) {
            DB::table('provider_services')->insert([
                'provider_id' => (int) $provider->id,
                'service_type_id' => (int) $typeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
