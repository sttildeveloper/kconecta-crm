<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || ! Schema::hasTable('migrations')
            || ! Schema::hasColumn('migrations', 'version')
        ) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE `migrations`
                MODIFY `version` VARCHAR(255) NOT NULL DEFAULT '1',
                MODIFY `class` VARCHAR(255) NOT NULL DEFAULT '',
                MODIFY `group` VARCHAR(255) NOT NULL DEFAULT 'default',
                MODIFY `namespace` VARCHAR(255) NOT NULL DEFAULT '',
                MODIFY `time` INT NOT NULL DEFAULT 0
        SQL);
    }

    public function down(): void
    {
        // The defaults restore compatibility with Laravel's migration repository.
        // Reverting them would make future migrations fail to register again.
    }
};
