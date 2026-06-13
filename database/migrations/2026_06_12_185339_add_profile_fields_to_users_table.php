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
        Schema::table('users', function (Blueprint $table) {
            $table->string('forename')->nullable()->after('name');
            $table->string('lastname')->nullable()->after('forename');
            $table->date('birthday')->nullable()->after('lastname');
            $table->string('gender')->nullable()->after('birthday');
            $table->unique('name'); // name acts as the username: public-profile route key + Redis leaderboard member key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['forename', 'lastname', 'birthday', 'gender']);
        });
    }
};
