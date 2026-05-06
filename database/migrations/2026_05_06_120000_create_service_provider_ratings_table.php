<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_provider_ratings')) {
            return;
        }

        Schema::create('service_provider_ratings', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_user_id');
            $table->integer('client_user_id');
            $table->unsignedTinyInteger('stars');
            $table->timestamps();

            $table->index('provider_user_id');
            $table->index('client_user_id');
            $table->unique(['provider_user_id', 'client_user_id'], 'spr_provider_client_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_ratings');
    }
};
