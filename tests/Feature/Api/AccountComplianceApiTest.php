<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountComplianceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_generic_message_for_existing_and_unknown_email(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'first_name' => 'Reset',
            'last_name' => 'User',
            'user_name' => 'reset-user',
            'email' => 'reset-user@test.dev',
            'phone' => '600100100',
            'password' => Hash::make('Password123!'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        $known = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $known->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Si el correo existe, recibiras instrucciones para restablecer tu contrasena.');

        $unknown = $this->postJson('/api/forgot-password', [
            'email' => 'missing-user@test.dev',
        ]);

        $unknown->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Si el correo existe, recibiras instrucciones para restablecer tu contrasena.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_updates_credentials_with_valid_token(): void
    {
        $user = User::query()->create([
            'first_name' => 'Reset',
            'last_name' => 'Target',
            'user_name' => 'reset-target',
            'email' => 'reset-target@test.dev',
            'phone' => '600100101',
            'password' => Hash::make('OldPassword123!'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Contrasena actualizada correctamente.');

        $fresh = User::query()->findOrFail($user->id);
        $this->assertTrue(Hash::check('NewPassword123!', (string) $fresh->password));
    }

    public function test_reset_password_returns_error_with_invalid_token(): void
    {
        $user = User::query()->create([
            'first_name' => 'Reset',
            'last_name' => 'Invalid',
            'user_name' => 'reset-invalid',
            'email' => 'reset-invalid@test.dev',
            'phone' => '600100102',
            'password' => Hash::make('OldPassword123!'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_delete_account_and_tokens_are_revoked(): void
    {
        $user = User::query()->create([
            'first_name' => 'Delete',
            'last_name' => 'Me',
            'user_name' => 'delete-me',
            'email' => 'delete-me@test.dev',
            'phone' => '600100103',
            'password' => Hash::make('DeletePassword123!'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/me', [
            'password' => 'DeletePassword123!',
            'reason' => 'store-compliance-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cuenta eliminada correctamente.');

        $fresh = User::query()->findOrFail($user->id);
        $this->assertStringStartsWith('deleted+', (string) $fresh->email);
        $this->assertSame('Cuenta eliminada', (string) $fresh->first_name);

        if (Schema::hasColumn('user', 'is_active')) {
            $this->assertSame(0, (int) $fresh->is_active);
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_account_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/me', [
            'password' => 'any-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_account_delete_alias_endpoint_behaves_like_delete_me(): void
    {
        $user = User::query()->create([
            'first_name' => 'Delete',
            'last_name' => 'Alias',
            'user_name' => 'delete-alias',
            'email' => 'delete-alias@test.dev',
            'phone' => '600100104',
            'password' => Hash::make('DeletePassword123!'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/account/delete', [
            'password' => 'DeletePassword123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cuenta eliminada correctamente.');
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/forgot-password', [
                'email' => 'ratelimit@test.dev',
            ])->assertOk();
        }

        $this->postJson('/api/forgot-password', [
            'email' => 'ratelimit@test.dev',
        ])->assertStatus(429);
    }
}
