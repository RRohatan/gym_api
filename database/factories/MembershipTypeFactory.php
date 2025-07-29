<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipType>
 */
class MembershipTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
        'name' => $this->faker->randomElement(['Básica', 'Premium', 'VIP']),
        'description' => $this->faker->sentence,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
