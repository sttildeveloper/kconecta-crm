<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_contact_clicks')) {
            return;
        }

        Schema::create('service_contact_clicks', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_user_id');
            $table->integer('service_id')->nullable();
            $table->string('channel', 40)->default('whatsapp');
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('provider_user_id');
            $table->index('service_id');
            $table->index('channel');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_contact_clicks');
    }
};

