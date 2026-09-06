<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAcceptanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_mobile_registration_remains_compatible_until_flag_is_enabled(): void
    {
        config()->set('compliance.legal_acceptance.required_on_registration', false);
        $this->postJson('/api/mobile/register-client', [
            'first_name' => 'Cliente', 'email' => 'legal-client@test.dev',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();
    }

    public function test_current_acceptance_can_be_recorded_and_requirement_can_be_enabled(): void
    {
        config()->set('compliance.legal_acceptance.documents', ['terms' => '2026-09', 'privacy' => '2026-09']);
        config()->set('compliance.legal_acceptance.required_on_registration', true);
        $base = ['first_name' => 'Cliente', 'email' => 'required-legal@test.dev', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];
        $this->postJson('/api/mobile/register-client', $base)->assertUnprocessable();
        $this->postJson('/api/mobile/register-client', $base + ['legal_acceptances' => [
            ['type' => 'terms', 'version' => '2026-09'], ['type' => 'privacy', 'version' => '2026-09'],
        ]])->assertCreated();
        $this->assertDatabaseCount('legal_acceptances', 2);
    }
}
