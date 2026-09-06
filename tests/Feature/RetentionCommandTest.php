<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetentionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_is_dry_run_without_apply(): void
    {
        config()->set('compliance.retention.metrics_days', 1);
        DB::table('service_profile_visits')->insert(['provider_user_id' => 123, 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)]);
        $this->artisan('compliance:retention-prune')->assertSuccessful()->expectsOutputToContain('DRY-RUN');
        $this->assertDatabaseHas('service_profile_visits', ['provider_user_id' => 123]);
    }
}
