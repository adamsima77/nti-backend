<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerReferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Content\Models\PartnerReference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

