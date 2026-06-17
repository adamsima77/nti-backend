<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\EmailTemplateTranslation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'subject'   => $this->faker->sentence(4),
            'body_html' => '<p>' . $this->faker->paragraph() . '</p>',
        ];
    }
}

