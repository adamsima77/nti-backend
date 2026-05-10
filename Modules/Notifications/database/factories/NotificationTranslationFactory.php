<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\NotificationTranslationFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'body'  => $this->faker->paragraph(),
        ];
    }
}

