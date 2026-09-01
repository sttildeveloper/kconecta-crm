<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonalProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_personal_profile_with_canonical_contract(): void
    {
        $user = $this->makeUser('profile-update@test.dev');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'first_name' => 'Nombre actualizado',
            'last_name' => 'Apellidos nuevos',
            'email' => $user->email,
            'phone' => '611111111',
            'landline_phone' => '931111111',
            'document_type' => 'DNI',
            'document_number' => '12345678A',
            'address' => 'Carrer de Mallorca, 120',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.first_name', 'Nombre actualizado')
            ->assertJsonPath('data.user.phone', '611111111')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure(['success', 'data', 'meta', 'message', 'errors', 'status']);
    }

    public function test_anonymous_user_cannot_update_personal_profile(): void
    {
        $this->patchJson('/api/me', ['first_name' => 'Anonimo'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 401);
    }

    public function test_personal_profile_supports_partial_updates(): void
    {
        $user = $this->makeUser('profile-partial@test.dev');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['phone' => '622222222'])
            ->assertOk()
            ->assertJsonPath('data.user.phone', '622222222')
            ->assertJsonPath('data.user.email', 'profile-partial@test.dev');

        $this->assertSame('Test', $user->fresh()->first_name);
    }

    public function test_personal_profile_returns_structured_validation_errors(): void
    {
        $user = $this->makeUser('profile-validation@test.dev');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'first_name' => '',
            'email' => 'correo-invalido',
            'password' => '123',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'message',
                'errors' => ['first_name', 'email', 'password'],
                'status',
            ]);
    }

    public function test_personal_profile_rejects_duplicate_email(): void
    {
        $user = $this->makeUser('profile-email@test.dev');
        $other = $this->makeUser('profile-email-used@test.dev');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['email' => $other->email])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);

        $this->assertSame('profile-email@test.dev', $user->fresh()->email);
    }

    public function test_changing_email_clears_verification_and_get_me_exposes_verification_state(): void
    {
        $user = $this->makeUser('profile-verified@test.dev');
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.user.email_verified_at', fn ($value) => is_string($value) && $value !== '');

        $this->patchJson('/api/me', ['email' => 'profile-changed@test.dev'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'profile-changed@test.dev')
            ->assertJsonPath('data.user.email_verified_at', null)
            ->assertJsonPath('data.email_verified', false);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_personal_profile_hashes_new_password_without_requiring_confirmation(): void
    {
        $user = $this->makeUser('profile-password@test.dev');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['password' => 'new-password-123'])
            ->assertOk()
            ->assertJsonMissingPath('data.user.password');

        $this->assertTrue(Hash::check('new-password-123', (string) $user->fresh()->password));
    }

    public function test_personal_profile_updates_photo_using_canonical_photo_field(): void
    {
        $user = $this->makeUser('profile-photo@test.dev');
        Sanctum::actingAs($user);

        // PHP 8.2 only parses multipart files for POST requests. Laravel's
        // method override still dispatches this request through PATCH /api/me.
        $response = $this->post('/api/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->image('avatar.png', 600, 400),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200);

        $photoPath = (string) $response->json('data.photo_path');
        $this->assertStringStartsWith('img/photo_profile/', $photoPath);
        $filename = basename($photoPath);
        $this->assertMatchesRegularExpression('/^user_'.$user->id.'_[A-Za-z0-9]{12}\.webp$/', $filename);
        $this->assertSame($filename, $user->fresh()->photo);
        $this->assertFileExists(public_path('img/photo_profile/'.$filename));
        $this->assertSame([350, 350], array_slice(getimagesize(public_path('img/photo_profile/'.$filename)), 0, 2));

        $replacement = $this->post('/api/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->image('replacement.jpg', 400, 700),
        ], ['Accept' => 'application/json'])->assertOk();
        $replacementFilename = basename((string) $replacement->json('data.photo_path'));
        $this->assertNotSame($filename, $replacementFilename);
        $this->assertFileDoesNotExist(public_path('img/photo_profile/'.$filename));
        $this->assertFileExists(public_path('img/photo_profile/'.$replacementFilename));

        @unlink(public_path('img/photo_profile/'.$replacementFilename));
    }

    public function test_omitting_password_keeps_existing_hash_and_invalid_photo_is_rejected(): void
    {
        $user = $this->makeUser('profile-password-omitted@test.dev');
        $oldHash = $user->password;
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['first_name' => 'Sin cambiar clave'])
            ->assertOk();
        $this->assertSame($oldHash, $user->fresh()->password);

        $this->post('/api/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['photo']]);

        $this->post('/api/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->create('too-large.png', 2049, 'image/png'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_personal_profile_does_not_modify_commercial_fields(): void
    {
        $user = $this->makeUser('profile-commercial@test.dev');
        $user->forceFill([
            'provider_title' => 'Titulo original',
            'provider_description' => 'Descripcion original',
            'provider_availability' => 'Disponibilidad original',
            'provider_page_url' => 'https://original.test',
        ])->save();
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'first_name' => 'Personal actualizado',
            'provider_title' => 'Titulo manipulado',
            'provider_description' => 'Descripcion manipulada',
            'provider_availability' => 'Disponibilidad manipulada',
            'provider_page_url' => 'https://manipulado.test',
            'specialty_ids' => [999],
            'cover_image' => 'cover.webp',
            'more_images' => ['gallery.webp'],
            'video' => 'video.mp4',
        ])->assertOk();

        $user->refresh();
        $this->assertSame('Personal actualizado', $user->first_name);
        $this->assertSame('Titulo original', $user->provider_title);
        $this->assertSame('Descripcion original', $user->provider_description);
        $this->assertSame('Disponibilidad original', $user->provider_availability);
        $this->assertSame('https://original.test', $user->provider_page_url);
    }

    private function makeUser(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);
    }
}
