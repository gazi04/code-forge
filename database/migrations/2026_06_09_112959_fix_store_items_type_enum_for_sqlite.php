<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            $table->enum('type', ['title', 'avatar', 'streak_freeze', 'xp_boost'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            $table->enum('type', ['title', 'streak_freeze', 'xp_boost'])->change();
        });
    }
};
