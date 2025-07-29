<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{

    public function definition(): array
    {
         $inicio = now();
    $duracion = $this->faker->numberBetween(7, 90); // días de duración del plan

    return [
        'member_id' => null, // se asigna en el seeder
        'plan_id' => null,   // se asigna en el seeder
        'start_date' => $inicio,
        'end_date' => (clone $inicio)->modify("+{$duracion} days"),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    }
}
