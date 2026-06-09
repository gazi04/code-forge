<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_boost_lessons_remaining')->default(0)->after('rested_xp_balance');
            $table->unsignedInteger('xp_boost_multiplier')->default(1)->after('xp_boost_lessons_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['xp_boost_lessons_remaining', 'xp_boost_multiplier']);
        });
    }
};
