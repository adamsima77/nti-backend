<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicFlagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Students\Models\AcademicFlag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}

