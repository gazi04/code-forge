<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_world_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->integer('xp_bonus_awarded')->default(0);
            $table->integer('coins_bonus_awarded')->default(0);
            $table->timestamp('completed_at');
            $table->unique(['user_id', 'world_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_world_completions');
    }
};
