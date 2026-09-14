<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOffer>
 */
class ProductOfferFactory extends Factory
{
    protected $model = ProductOffer::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'store_id' => Store::factory(),
            'price' => $this->faker->randomFloat(2, 50, 800),
            'stock_quantity' => 100,
            'status' => 'active',
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function price(float $price): static
    {
        return $this->state(['price' => $price]);
    }
}
