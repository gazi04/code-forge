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
        // Library of visual configurations for worlds
        Schema::create('theme_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Cyberpunk", "Dungeon"
            $table->string('identifier')->unique(); // e.g., "theme_cyberpunk"
            $table->json('config'); // Holds palettes, character sprites, etc.
            $table->timestamps();
        });

        Schema::create('worlds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('theme_pack_id')->constrained();
            $table->json('accent_colors'); // { primary: '#hex', secondary: '#hex', accent: '#hex' }
            $table->string('layout_template'); // linear, branching, hub-and-spoke
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('age_tier'); // explorer, builder, coder
            $table->integer('difficulty'); // 1-5
            $table->integer('estimated_duration'); // in minutes
            $table->foreignId('prerequisite_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('xp_reward');
            $table->integer('coin_reward');
            $table->integer('estimated_duration');
            $table->boolean('is_boss')->default(false);
            $table->integer('order')->default(0);
            $table->json('blocks'); // The core architectural decision: Ordered array of block objects
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_packs');
        Schema::dropIfExists('worlds');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('lessons');
    }
};
