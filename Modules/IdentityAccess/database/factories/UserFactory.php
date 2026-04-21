<?php

namespace Modules\IdentityAccess\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\Status;
use Modules\IdentityAccess\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\IdentityAccess\Models\User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status_id' => $this->faker->numberBetween(1, 3),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {

            $role = Role::inRandomOrder()->first();

            if ($role) {
                $user->roles()->attach($role->id);
            }
        });
    }
}

