<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\EmailTemplate::class;

    public function definition(): array
    {
        return [
            'slug'      => $this->faker->unique()->slug(2),
            'subject'   => $this->faker->sentence(4),
            'body_html' => '<p>Dobrý deň {{ $name }},</p><p>' . $this->faker->paragraph() . '</p>',
            'is_active' => true,
        ];
    }
}

