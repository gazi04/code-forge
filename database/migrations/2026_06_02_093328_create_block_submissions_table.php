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
        Schema::create('block_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->integer('block_index'); // Tracks which specific block in the JSON array they beat
            $table->integer('xp_rewarded')->default(0);
            $table->integer('coins_rewarded')->default(0);
            $table->timestamps();

            // Ensure a user can only claim a specific block in a specific lesson ONCE
            $table->unique(['user_id', 'lesson_id', 'block_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_submissions');
    }
};
