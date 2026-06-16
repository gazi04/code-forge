<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lesson-side lookups (relation managers, LessonFunnelWidget correlated
        // subqueries) filter by lesson_id, which the (user_id, lesson_id, ...)
        // unique indexes can't serve as it isn't the leftmost column. SQLite also
        // doesn't auto-index FK columns, so add explicit indexes.
        Schema::table('block_submissions', function (Blueprint $table) {
            $table->index('lesson_id');
        });

        Schema::table('lesson_submissions', function (Blueprint $table) {
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::table('block_submissions', function (Blueprint $table) {
            $table->dropIndex(['lesson_id']);
        });

        Schema::table('lesson_submissions', function (Blueprint $table) {
            $table->dropIndex(['lesson_id']);
        });
    }
};
