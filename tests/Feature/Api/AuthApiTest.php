<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_token_can_access_protected_endpoint(): void
    {
        $user = User::query()->create([
            'first_name' => 'Api',
            'last_name' => 'User',
            'user_name' => 'api-user',
            'email' => 'api-user@test.dev',
            'phone' => '600000000',
            'password' => Hash::make('password123'),
            'user_level_id' => User::LEVEL_SERVICE_PROVIDER,
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('token_type', 'Bearer');

        $token = $loginResponse->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', $user->email);
    }
}

