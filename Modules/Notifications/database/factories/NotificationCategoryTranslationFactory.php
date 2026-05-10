<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationCategoryTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\NotificationCategoryTranslation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
        ];
    }
}

