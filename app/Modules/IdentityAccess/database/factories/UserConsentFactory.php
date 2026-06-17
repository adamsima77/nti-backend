<?php

namespace Modules\IdentityAccess\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\IdentityAccess\Models\ConsentType;
use Modules\IdentityAccess\Models\User;

class UserConsentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\IdentityAccess\Models\UserConsent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'granted' => $this->faker->boolean(),
            'granted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'revoked_at' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'user_id' => User::factory(),
            'consent_id' => ConsentType::inRandomOrder()->value('id'),
        ];
    }
}

