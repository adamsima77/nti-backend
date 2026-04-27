<?php

namespace Modules\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;

class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->company(),
            'phone'      => $this->faker->phoneNumber(),
            'ico'        => $this->faker->unique()->numerify('########'),
            'web_url'    => $this->faker->url(),
            'address_id' => Address::factory(),
        ];
    }
}

