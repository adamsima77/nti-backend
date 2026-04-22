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
        return [
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Partner $partner) {

            $name = $this->faker->company();
            $description = $this->faker->realText(150);

            $partner->partnerTranslations()->createMany([
                [
                    'name' => $name,
                    'description' => $description,
                    'language_id' => LanguageType::ENGLISH->value,
                ],
                [
                    'name' => $name,
                    'description' => $description,
                    'language_id' => LanguageType::SLOVAK->value,
                ],
            ]);
        });
    }
}

