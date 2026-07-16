<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('lesson_fk')->nullable()->after('course_id');
        });

        // Backfill: resolve slug → id
        DB::statement('UPDATE lesson_submissions SET lesson_fk = (SELECT id FROM lessons WHERE lessons.slug = lesson_submissions.lesson_id)');

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'lesson_id']);
            $table->dropColumn('lesson_id');
        });

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->renameColumn('lesson_fk', 'lesson_id');
        });

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('lesson_id')->nullable(false)->change();
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->dropForeign(['lesson_id']);
            $table->dropUnique(['user_id', 'lesson_id']);
        });

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->string('lesson_slug_tmp')->nullable()->after('lesson_id');
        });

        DB::statement('UPDATE lesson_submissions SET lesson_slug_tmp = (SELECT slug FROM lessons WHERE lessons.id = lesson_submissions.lesson_id)');

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->dropColumn('lesson_id');
        });

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->renameColumn('lesson_slug_tmp', 'lesson_id');
        });

        Schema::table('lesson_submissions', function (Blueprint $table): void {
            $table->unique(['user_id', 'lesson_id']);
        });
    }
};
