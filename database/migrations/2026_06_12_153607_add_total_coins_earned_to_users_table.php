<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('total_coins_earned')->default(0)->after('coins');
        });

        DB::statement('
            UPDATE users SET total_coins_earned = (
                COALESCE((SELECT SUM(coins_rewarded) FROM lesson_submissions WHERE user_id = users.id), 0) +
                COALESCE((SELECT SUM(coins_rewarded) FROM block_submissions WHERE user_id = users.id), 0) +
                COALESCE((SELECT SUM(coins_bonus_awarded) FROM user_world_completions WHERE user_id = users.id), 0) +
                ((users.level - 1) * 50)
            )
        ');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('total_coins_earned');
        });
    }
};
