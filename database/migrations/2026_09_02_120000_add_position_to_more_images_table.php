<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('more_images', 'position')) {
            Schema::table('more_images', function (Blueprint $table): void {
                $table->unsignedInteger('position')->nullable()->after('url');
                $table->index(['provider_user_id', 'position'], 'more_images_provider_position_index');
            });
        }

        $providerIds = DB::table('more_images')
            ->whereNotNull('provider_user_id')
            ->distinct()
            ->orderBy('provider_user_id')
            ->pluck('provider_user_id');

        foreach ($providerIds as $providerId) {
            $ids = DB::table('more_images')
                ->where('provider_user_id', $providerId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $position => $id) {
                DB::table('more_images')->where('id', $id)->update(['position' => $position]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('more_images', 'position')) {
            Schema::table('more_images', function (Blueprint $table): void {
                $table->dropIndex('more_images_provider_position_index');
                $table->dropColumn('position');
            });
        }
    }
};
