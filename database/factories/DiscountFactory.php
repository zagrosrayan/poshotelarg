<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'discount_value' => $this->faker->numberBetween(5, 50),
            'minimum_price' => $this->faker->optional()->numberBetween(500, 5000),
            'is_active' => $this->faker->boolean,
            'usage_limit' => $this->faker->optional()->numberBetween(1, 100),
            'starts_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week')?->format('Y-m-d'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month')?->format('Y-m-d'),
            'code' => 'DISCOUNT-' . Str::upper(Str::random(10)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
