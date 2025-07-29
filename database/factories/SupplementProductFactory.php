<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Gimnasio;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplementProduct>
 */
class SupplementProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'gimnasio_id' => Gimnasio::factory(),
        'name' => $this->faker->word . ' Protein',
        'description' => $this->faker->sentence,
        'price' => $this->faker->randomFloat(2, 10, 150),
        'stock' => $this->faker->numberBetween(5, 50),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
