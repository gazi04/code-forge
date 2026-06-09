<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['title', 'avatar_frame', 'streak_freeze', 'xp_boost']);
            $table->enum('purchase_type', ['permanent', 'one_time', 'consumable']);
            $table->unsignedInteger('price_coins');
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('stock_limit')->nullable();
            $table->unsignedInteger('sold_count')->default(0);
            $table->json('effect_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
