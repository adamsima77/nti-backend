<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\Notifications::class;

    public function definition(): array
    {
        return [
            'title'   => $this->faker->sentence(4),
            'body'    => $this->faker->paragraph(),
            'is_read' => false,
            'read_at' => null,
        ];
    }
}

