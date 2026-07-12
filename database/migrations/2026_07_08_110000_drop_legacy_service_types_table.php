<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('service_types');
    }

    public function down(): void
    {
        if (Schema::hasTable('service_types')) {
            return;
        }

        Schema::create('service_types', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('service_id');
            $table->integer('user_id')->nullable();
            $table->index('user_id');
            $table->integer('service_type_id');
        });
    }
};
