<?php

namespace Tests\Feature;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackfillProviderDefaultMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_is_read_only_and_apply_copies_only_missing_provider_media(): void
    {
        $source = $this->provider('default-media-source@test.dev');
        $target = $this->provider('default-media-target@test.dev');
        $existing = $this->provider('default-media-existing@test.dev');
        $sourceFiles = [
            'test-source-cover.webp' => 'source-cover-content',
            'test-source-gallery-1.webp' => 'source-gallery-one',
            'test-source-gallery-2.webp' => 'source-gallery-two',
        ];
        foreach ($sourceFiles as $filename => $content) {
            File::put(public_path('img/uploads/'.$filename), $content);
        }

        CoverImage::query()->create([
            'provider_user_id' => (int) $source->id,
            'url' => 'test-source-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $source->id,
            'url' => 'test-source-gallery-1.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $source->id,
            'url' => 'test-source-gallery-2.webp',
        ]);
        CoverImage::query()->create([
            'provider_user_id' => (int) $existing->id,
            'url' => 'existing-cover.webp',
        ]);
        MoreImage::query()->create([
            'provider_user_id' => (int) $existing->id,
            'url' => 'existing-gallery.webp',
        ]);

        $targetPattern = public_path('img/uploads/provider_'.$target->id.'_default_*');

        try {
            $this->artisan('providers:backfill-default-media', ['--source-provider' => (int) $source->id])
                ->expectsOutputToContain('Sin portada')
                ->expectsOutputToContain('Simulacion completada')
                ->assertSuccessful();

            $this->assertSame(0, CoverImage::query()->where('provider_user_id', $target->id)->count());
            $this->assertSame([], File::glob($targetPattern));

            $this->artisan('providers:backfill-default-media', [
                '--source-provider' => (int) $source->id,
                '--apply' => true,
            ])
                ->expectsOutputToContain('1 portadas, 2 imagenes de galeria y 3 archivos creados')
                ->assertSuccessful();

            $targetCover = CoverImage::query()->where('provider_user_id', $target->id)->firstOrFail();
            $targetGallery = MoreImage::query()->where('provider_user_id', $target->id)->orderBy('id')->get();
            $this->assertTrue((bool) $targetCover->is_provider_default);
            $this->assertSame((int) $source->id, (int) $targetCover->source_provider_user_id);
            $this->assertNull($targetCover->service_id);
            $this->assertCount(2, $targetGallery);
            $this->assertTrue($targetGallery->every(fn ($image) => (bool) $image->is_provider_default));
            $this->assertSame('existing-cover.webp', CoverImage::query()->where('provider_user_id', $existing->id)->value('url'));
            $this->assertCount(3, File::glob($targetPattern));

            $this->artisan('providers:backfill-default-media', [
                '--source-provider' => (int) $source->id,
                '--apply' => true,
            ])
                ->expectsOutputToContain('0 portadas, 0 imagenes de galeria y 0 archivos creados')
                ->assertSuccessful();
        } finally {
            foreach (array_keys($sourceFiles) as $filename) {
                File::delete(public_path('img/uploads/'.$filename));
            }
            File::delete(File::glob($targetPattern));
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
}
