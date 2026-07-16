<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Fractional boosts (e.g. 1.5×) need a decimal column; integer truncated them to 1×.
            $table->decimal('xp_boost_multiplier', 4, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('xp_boost_multiplier')->default(1)->change();
        });
    }
};
