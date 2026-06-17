<?php

namespace Modules\Notifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Notifications\Models\NotificationCategory::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->sentence(2),
            'icon'  => $this->faker->randomElement([
                'Users', 'Flag', 'MessageSquare', 'AlertTriangle', 'Bell', 'Star'
            ]),
            'color' => $this->faker->randomElement([
                'bg-blue-600', 'bg-yellow-500', 'bg-purple-600', 'bg-red-600', 'bg-green-600', 'bg-teal-600'
            ]),
        ];
    }
}

