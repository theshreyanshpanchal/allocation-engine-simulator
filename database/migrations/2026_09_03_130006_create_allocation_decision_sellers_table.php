<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_decision_sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_decision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('score', 3, 1);
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->boolean('stock_available')->default(true);
            $table->boolean('serviceable')->default(true);
            $table->boolean('price_guard_eligible')->default(false);
            $table->string('exclusion_reason')->nullable();
            $table->decimal('target_share', 8, 6)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->unique(['allocation_decision_id', 'store_id'], 'ads_decision_store_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_decision_sellers');
    }
};
