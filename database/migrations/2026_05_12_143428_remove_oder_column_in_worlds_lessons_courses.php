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
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
