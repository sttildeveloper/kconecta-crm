<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentSafetyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_and_block_but_not_self_or_repeat_active_report(): void
    {
        $reporter = User::factory()->create(['user_level_id' => User::LEVEL_FINAL_CLIENT]);
        $provider = User::factory()->create(['user_level_id' => User::LEVEL_SERVICE_PROVIDER]);
        Sanctum::actingAs($reporter);

        $payload = ['reported_user_id' => $provider->id, 'content_type' => 'provider_profile', 'content_id' => (string) $provider->id, 'reason' => 'fraud'];
        $this->postJson('/api/reports', $payload)->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->postJson('/api/reports', $payload)->assertStatus(409);
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.2'])
            ->postJson('/api/reports', array_merge($payload, ['reported_user_id' => $reporter->id, 'content_type' => 'user', 'content_id' => null]))
            ->assertStatus(422);

        $this->postJson('/api/users/'.$provider->id.'/block')->assertCreated();
        $this->postJson('/api/users/'.$provider->id.'/block')->assertOk();
        $this->postJson('/api/users/'.$reporter->id.'/block')->assertStatus(422);
        $this->getJson('/api/providers/'.$provider->id)->assertOk()->assertJsonPath('data.is_blocked', true);
        $this->deleteJson('/api/users/'.$provider->id.'/block')->assertOk();
    }

    public function test_only_admin_can_access_moderation_panel(): void
    {
        $user = User::factory()->create(['user_level_id' => User::LEVEL_FINAL_CLIENT]);
        $this->actingAs($user)->get('/admin/content-reports')->assertForbidden();
        $admin = User::factory()->create(['user_level_id' => User::LEVEL_ADMIN]);
        $this->actingAs($admin)->get('/admin/content-reports')->assertOk();
    }
}
