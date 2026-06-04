<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // Referencia a la tabla user (id integer auto-increment)
            $table->integer('property_id')->nullable(); // Referencia a la tabla property (id integer auto-increment)
            $table->string('subject', 150);
            $table->text('description');
            $table->string('status', 30)->default('open'); // 'open', 'in_progress', 'resolved', 'closed'
            $table->string('priority', 30)->default('medium'); // 'low', 'medium', 'high'
            $table->timestamps();

            $table->index('user_id');
            $table->index('property_id');
            $table->index('status');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id'); // Referencia a tickets.id (bigint unsigned)
            $table->integer('user_id'); // Referencia a user.id (id integer)
            $table->text('message');
            $table->json('attachments_json')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
