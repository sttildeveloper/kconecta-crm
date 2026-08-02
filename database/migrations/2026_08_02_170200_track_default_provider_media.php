<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MEDIA_TABLES = ['cover_image', 'more_images', 'video'];

    public function up(): void
    {
        foreach (self::MEDIA_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $addDefaultFlag = ! Schema::hasColumn($table, 'is_provider_default');
            $addSourceProvider = ! Schema::hasColumn($table, 'source_provider_user_id');
            if (! $addDefaultFlag && ! $addSourceProvider) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $addDefaultFlag, $addSourceProvider): void {
                if ($addDefaultFlag) {
                    $blueprint->boolean('is_provider_default')->default(false)->after('provider_user_id');
                    $blueprint->index('is_provider_default', $table.'_provider_default_idx');
                }
                if ($addSourceProvider) {
                    $blueprint->integer('source_provider_user_id')->nullable()->after('is_provider_default');
                    $blueprint->index('source_provider_user_id', $table.'_source_provider_idx');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::MEDIA_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'source_provider_user_id')) {
                    $blueprint->dropIndex($table.'_source_provider_idx');
                    $blueprint->dropColumn('source_provider_user_id');
                }
                if (Schema::hasColumn($table, 'is_provider_default')) {
                    $blueprint->dropIndex($table.'_provider_default_idx');
                    $blueprint->dropColumn('is_provider_default');
                }
            });
        }
    }
};
