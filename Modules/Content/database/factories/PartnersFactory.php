<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Partner;

class PartnersFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Content\Models\Partner::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'image' => 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=edf2f7&color=1d4ed8&size=128&rounded=true',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Partner $partner) {
            $description = $this->faker->realText(150);

            $partner->partnerTranslations()->createMany([
                [
                    'description' => $description,
                    'language_id' => LanguageType::ENGLISH->value,
                ],
                [
                    'description' => $description,
                    'language_id' => LanguageType::SLOVAK->value,
                ],
            ]);
        });
    }
}

