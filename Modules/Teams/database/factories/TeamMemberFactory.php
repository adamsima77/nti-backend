<?php

namespace Modules\Teams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Teams\Models\TeamMember::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

