<?php

namespace Modules\Applications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Applications\Models\Applications;

class ApplicationsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Applications::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'user_id' => \App\Models\User::factory(),
            'metadata' => [
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'category' => $this->faker->word(),
            ],
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the application is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}

