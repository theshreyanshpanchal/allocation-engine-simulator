<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_run_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('winner_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->unsignedInteger('purchase_index')->nullable();
            $table->string('buyer_postcode')->nullable();
            $table->decimal('tolerance_percent', 5, 2);
            $table->decimal('lowest_price', 10, 2)->nullable();
            $table->decimal('price_ceiling', 10, 2)->nullable();
            $table->string('eligible_context')->nullable();
            $table->text('decision_reason')->nullable();
            $table->text('human_explanation')->nullable();
            $table->text('technical_explanation')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_decisions');
    }
};
