<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addPhone = ! Schema::hasColumn('user', 'provider_phone');
        $addLandline = ! Schema::hasColumn('user', 'provider_landline_phone');

        if ($addPhone || $addLandline) {
            Schema::table('user', function (Blueprint $table) use ($addPhone, $addLandline): void {
                if ($addPhone) {
                    $table->string('provider_phone', 40)->nullable()->after('provider_page_url');
                }
                if ($addLandline) {
                    $table->string('provider_landline_phone', 40)->nullable()->after('provider_phone');
                }
            });
        }

        DB::table('user')
            ->where('user_level_id', 4)
            ->whereNull('provider_phone')
            ->update(['provider_phone' => DB::raw('phone')]);
        DB::table('user')
            ->where('user_level_id', 4)
            ->whereNull('provider_landline_phone')
            ->update(['provider_landline_phone' => DB::raw('landline_phone')]);
    }

    public function down(): void
    {
        $columns = collect(['provider_phone', 'provider_landline_phone'])
            ->filter(fn (string $column) => Schema::hasColumn('user', $column))
            ->all();

        if ($columns !== []) {
            Schema::table('user', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
