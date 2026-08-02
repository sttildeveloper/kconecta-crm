<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missingColumns = collect([
            'provider_title',
            'provider_description',
            'provider_availability',
            'provider_page_url',
        ])->reject(fn (string $column) => Schema::hasColumn('user', $column));

        if ($missingColumns->isNotEmpty()) {
            Schema::table('user', function (Blueprint $table) use ($missingColumns): void {
                if ($missingColumns->contains('provider_title')) {
                    $table->string('provider_title')->nullable();
                }
                if ($missingColumns->contains('provider_description')) {
                    $table->text('provider_description')->nullable();
                }
                if ($missingColumns->contains('provider_availability')) {
                    $table->string('provider_availability', 100)->nullable();
                }
                if ($missingColumns->contains('provider_page_url')) {
                    $table->string('provider_page_url')->nullable();
                }
            });
        }

        if (! Schema::hasTable('service')) {
            return;
        }

        $legacyProfiles = DB::table('service as service')
            ->join('user as provider', 'provider.id', '=', 'service.user_id')
            ->where('provider.user_level_id', 4)
            ->orderByDesc('service.id')
            ->get([
                'service.user_id',
                'service.title',
                'service.description',
                'service.availability',
                'service.page_url',
            ])
            ->unique('user_id');

        foreach ($legacyProfiles as $legacyProfile) {
            $provider = DB::table('user')->where('id', $legacyProfile->user_id)->first();
            if (! $provider) {
                continue;
            }

            $updates = [];
            foreach ([
                'provider_title' => 'title',
                'provider_description' => 'description',
                'provider_availability' => 'availability',
                'provider_page_url' => 'page_url',
            ] as $providerField => $legacyField) {
                if (
                    trim((string) ($provider->{$providerField} ?? '')) === ''
                    && trim((string) ($legacyProfile->{$legacyField} ?? '')) !== ''
                ) {
                    $updates[$providerField] = $legacyProfile->{$legacyField};
                }
            }

            if ($updates !== []) {
                DB::table('user')->where('id', $legacyProfile->user_id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        $columns = collect([
            'provider_title',
            'provider_description',
            'provider_availability',
            'provider_page_url',
        ])->filter(fn (string $column) => Schema::hasColumn('user', $column));

        if ($columns->isNotEmpty()) {
            Schema::table('user', fn (Blueprint $table) => $table->dropColumn($columns->all()));
        }
    }
};
