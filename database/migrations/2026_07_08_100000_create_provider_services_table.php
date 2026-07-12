<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provider_services')) {
            Schema::create('provider_services', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('provider_id');
                $table->integer('service_type_id');
                $table->timestamps();

                $table->unique(['provider_id', 'service_type_id'], 'provider_services_provider_type_unique');
                $table->index('provider_id', 'provider_services_provider_idx');
                $table->index('service_type_id', 'provider_services_service_type_idx');
            });
        }

        if (
            ! Schema::hasTable('service_types')
            || ! Schema::hasColumn('service_types', 'user_id')
            || ! Schema::hasColumn('service_types', 'service_type_id')
        ) {
            return;
        }

        DB::table('service_types')
            ->select(['user_id', 'service_type_id'])
            ->whereNotNull('user_id')
            ->whereNotNull('service_type_id')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                $timestamp = now();
                $payload = collect($rows)
                    ->map(function ($row) use ($timestamp) {
                        return [
                            'provider_id' => (int) $row->user_id,
                            'service_type_id' => (int) $row->service_type_id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    })
                    ->unique(fn (array $row) => $row['provider_id'] . ':' . $row['service_type_id'])
                    ->values()
                    ->all();

                if (empty($payload)) {
                    return;
                }

                DB::table('provider_services')->upsert(
                    $payload,
                    ['provider_id', 'service_type_id'],
                    ['updated_at']
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
