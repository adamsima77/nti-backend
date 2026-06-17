<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyProgramFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Students\Models\StudyProgram::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
        ];
    }
}

