<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\EmailService;
use Mockery;
use Tests\TestCase;

class PublicMailSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_resolves_recipient_from_property_and_ignores_client_recipient_fields(): void
    {
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('send')->once()->with('real-owner@test.dev', Mockery::type('string'), Mockery::type('string'))->andReturnTrue();
        $this->app->instance(EmailService::class, $email);
        $owner = User::factory()->create(['email' => 'real-owner@test.dev']);
        $property = Property::query()->create(['reference' => 'safe-mail-property', 'user_id' => $owner->id]);

        $this->postJson('/api/send/message/email_to_provider', [
            'user_email' => 'sender@test.dev', 'user_name' => 'Sender', 'message' => 'Mensaje legítimo de prueba',
            'property_id' => $property->id, 'provider_email' => 'attacker@test.dev',
        ])->assertOk();

    }

    public function test_share_requires_post_internal_property_and_limits_recipients(): void
    {
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('send')->twice()->with(Mockery::on(fn ($to) => in_array($to, ['one@test.dev', 'two@test.dev'], true)), Mockery::type('string'), Mockery::type('string'))->andReturnTrue();
        $this->app->instance(EmailService::class, $email);
        $property = Property::query()->create(['reference' => 'share-mail-property']);
        $this->getJson('/api/send/message/email_share')->assertStatus(405);
        $this->postJson('/api/send/message/email_share', [
            'property_id' => $property->id,
            'user_emails' => ['one@test.dev', 'two@test.dev'],
        ])->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.3'])->postJson('/api/send/message/email_share', [
            'property_id' => $property->id,
            'user_emails' => ['1@test.dev', '2@test.dev', '3@test.dev', '4@test.dev'],
        ])->assertUnprocessable();
    }
}
