<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $level = UserLevel::create([
            'id' => User::LEVEL_SERVICE_PROVIDER,
            'name' => 'Proveedor de servicio',
        ]);

        $this->get('/register')->assertOk();
        $registrationFormStartedAt = (int) session('registration_form_started_at');
        $this->travel(4)->seconds();

        $response = $this->post('/register', [
            'user_level_id' => $level->id,
            'registration_form_started_at' => $registrationFormStartedAt,
            'website' => '',
            'document_type' => '',
            'document_number' => '',
            'first_name' => 'Test',
            'last_name' => '',
            'company_name' => '',
            'phone' => '600000000',
            'landline_phone' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertDatabaseHas('user_address', [
            'user_id' => auth()->id(),
            'address' => null,
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}
