<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackfillProviderDefaultPhotosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_create_files_or_update_providers(): void
    {
        $provider = $this->provider('default-photo-dry-run@test.dev');
        $targetPath = $this->targetPath((int) $provider->id);
        File::delete($targetPath);

        try {
            $this->artisan('providers:backfill-default-photos')
                ->expectsOutputToContain('Sin foto')
                ->assertSuccessful();

            $this->assertNull($provider->fresh()->photo);
            $this->assertFileDoesNotExist($targetPath);
        } finally {
            File::delete($targetPath);
        }
    }

    public function test_apply_assigns_individual_logo_only_to_providers_without_photo(): void
    {
        $missingPhoto = $this->provider('default-photo-apply@test.dev');
        $existingPhoto = $this->provider('existing-photo@test.dev', 'existing-provider.webp');
        $targetPath = $this->targetPath((int) $missingPhoto->id);
        File::delete($targetPath);

        try {
            $this->artisan('providers:backfill-default-photos', ['--apply' => true])
                ->expectsOutputToContain('1 proveedores actualizados y 1 fotos creadas')
                ->assertSuccessful();

            $expectedFilename = basename($targetPath);
            $this->assertSame($expectedFilename, $missingPhoto->fresh()->photo);
            $this->assertSame('existing-provider.webp', $existingPhoto->fresh()->photo);
            $this->assertFileExists($targetPath);
            $this->assertSame(
                hash_file('sha256', public_path('img/kconecta_icon.webp')),
                hash_file('sha256', $targetPath)
            );
        } finally {
            File::delete($targetPath);
        }
    }

    private function provider(string $email, ?string $photo = null): User
    {
        return User::query()->create([
            'first_name' => 'Proveedor',
            'last_name' => 'Prueba',
            'user_name' => 'provider-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'photo' => $photo,
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);
    }

    private function targetPath(int $providerId): string
    {
        return public_path('img/photo_profile/provider_'.$providerId.'_kconecta_default.webp');
    }
}
