<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_level')->updateOrInsert(
            ['id' => User::LEVEL_FINAL_CLIENT],
            ['name' => 'Cliente final']
        );
    }

    public function down(): void
    {
        DB::table('user_level')->where('id', User::LEVEL_FINAL_CLIENT)->delete();
    }
};
