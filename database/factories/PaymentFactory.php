<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
        'amount' => $this->faker->randomFloat(2, 10, 100),
        // Estos se setean en el seeder según el modelo asociado
        'paymentable_id' => null,
        'paymentable_type' => null,
        'payment_method' => $this->faker->randomElement(['efectivo', 'tarjeta', 'transferencia']),
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ];
    }
}
