<?php

namespace Modules\Teams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamRoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Teams\Models\TeamRole::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}

