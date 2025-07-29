<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Member;
use App\Models\SupplementProduct;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplementSale>
 */
class SupplementSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'member_id' => Member::factory(),
        'product_id' => SupplementProduct::factory(),
        'quantity' => $this->faker->numberBetween(1, 3),
        'total' => $this->faker->randomFloat(2, 20, 90),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
