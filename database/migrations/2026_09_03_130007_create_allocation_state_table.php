<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('eligible_context');
            $table->unsignedInteger('allocated_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->decimal('realised_share', 8, 6)->default(0);
            $table->decimal('target_share', 8, 6)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'store_id', 'eligible_context'], 'allocation_state_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_state');
    }
};
