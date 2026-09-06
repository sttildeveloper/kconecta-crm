<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->integer('reporter_user_id')->index();
            $table->integer('reported_user_id')->index();
            $table->string('content_type', 40)->default('user');
            $table->string('content_id', 100)->nullable();
            $table->string('reason', 50)->index();
            $table->text('details')->nullable();
            $table->string('active_fingerprint', 64)->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->integer('moderator_user_id')->nullable()->index();
            $table->text('resolution_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['reporter_user_id', 'reported_user_id', 'status'], 'content_reports_repeat_idx');
        });

        Schema::create('user_blocks', function (Blueprint $table): void {
            $table->id();
            $table->integer('blocker_user_id')->index();
            $table->integer('blocked_user_id')->index();
            $table->timestamps();
            $table->unique(['blocker_user_id', 'blocked_user_id'], 'user_blocks_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('content_reports');
    }
};
