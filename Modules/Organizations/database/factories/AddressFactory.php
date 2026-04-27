<?php

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Address;

class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'city'        => $this->faker->city(),
            'street'      => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'country'     => $this->faker->country(),
        ];
    }
}

