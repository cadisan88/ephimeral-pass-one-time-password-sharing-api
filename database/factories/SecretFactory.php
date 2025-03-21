<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Secret>
 */
class SecretFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'secret' => $this->faker->text, // Have to encrypt this
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 day'),
        ];
    }
}
