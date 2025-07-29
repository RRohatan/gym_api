<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MembershipType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
       'membership_type_id' => MembershipType::factory(),
        'frequency' => $this->faker->randomElement(['weekly', 'biweekly', 'monthly']),
        'price' => $this->faker->randomFloat(2, 20, 100),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
