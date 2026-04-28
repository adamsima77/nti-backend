<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Models\StudyField;
use Modules\Students\Models\StudyProgram;
use Modules\Students\Models\University;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Students\Models\Student::class;

    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'study_field_id'   => StudyField::factory(),
            'university_id'    => University::factory(),
            'year_of_study'    => $this->faker->numberBetween(1, 6),
            'portfolio_url'    => $this->faker->optional()->url(),
        ];
    }
}

