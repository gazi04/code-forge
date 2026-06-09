<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE store_items MODIFY COLUMN type ENUM('title', 'avatar', 'streak_freeze', 'xp_boost') NOT NULL");
        } else {
            Schema::table('store_items', function (Blueprint $table) {
                $table->enum('type', ['title', 'avatar', 'streak_freeze', 'xp_boost'])->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('store_items')->where('type', 'avatar')->delete();

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE store_items MODIFY COLUMN type ENUM('title', 'streak_freeze', 'xp_boost') NOT NULL");
        } else {
            Schema::table('store_items', function (Blueprint $table) {
                $table->enum('type', ['title', 'streak_freeze', 'xp_boost'])->change();
            });
        }
    }
};
