<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('STORE-##??')),
            'name' => $this->faker->company(),
            'operational_score' => $this->faker->randomFloat(1, 1, 5),
            'distance_km' => $this->faker->randomFloat(1, 1, 30),
            'status' => 'active',
            'serviceable' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function notServiceable(): static
    {
        return $this->state(['serviceable' => false]);
    }

    public function score(float $score): static
    {
        return $this->state(['operational_score' => $score]);
    }
}
