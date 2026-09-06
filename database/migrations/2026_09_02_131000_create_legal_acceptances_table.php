<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->integer('user_id')->index();
            $table->string('document_type', 40);
            $table->string('document_version', 80);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'document_type', 'document_version'], 'legal_acceptance_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_acceptances');
    }
};
