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

    public function test_provider_with_existing_service_profile_is_redirected_from_create_form(): void
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
            ->assertRedirect('/post/services/update_form/' . $service->id);
    }

    public function test_provider_cannot_create_a_second_service_profile(): void
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

        $response->assertRedirect('/post/services/update_form/' . $existingService->id);

        $this->assertSame(1, Service::query()->where('user_id', (int) $provider->id)->count());
        $this->assertDatabaseMissing('service_types', [
            'service_id' => (int) $existingService->id,
            'service_type_id' => (int) $type->id,
        ]);
        $this->assertSame(0, CoverImage::query()->count());
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
