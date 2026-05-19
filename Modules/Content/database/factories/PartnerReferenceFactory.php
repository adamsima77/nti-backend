<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\PartnerReference;

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
        return [
            'name' => $this->faker->name(),
            'job_position' => $this->faker->jobTitle()
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (PartnerReference $partner) {
            $description = $this->faker->realText(150);

            $partner->partnerReferenceTranslations()->createMany([
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

