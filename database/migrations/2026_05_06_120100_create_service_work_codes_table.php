<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_work_codes')) {
            return;
        }

        Schema::create('service_work_codes', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_user_id');
            $table->string('code', 64);
            $table->boolean('is_used')->default(false);
            $table->integer('used_by_user_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('provider_user_id');
            $table->index('used_by_user_id');
            $table->unique('code', 'swc_code_unique');
            $table->unique(['provider_user_id', 'code'], 'swc_provider_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_work_codes');
    }
};
