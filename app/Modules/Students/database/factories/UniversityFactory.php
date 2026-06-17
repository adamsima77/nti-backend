<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UniversityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Students\Models\University::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
        ];
    }
}

