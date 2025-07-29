<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'gimnasio_id' => null, // Se asigna en el seeder
        'name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
        'phone' => $this->faker->phoneNumber,
        'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
