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
        return [];
    }

    public function configure()
    {
        return $this->afterCreating(function (PartnerReference $partner) {

            $name = $this->faker->company();
            $job = $this->faker->jobTitle();
            $description = $this->faker->realText(150);

            $partner->partnerReferenceTranslations()->createMany([
                [
                    'name' => $name,
                    'job_position' => $job,
                    'description' => $description,
                    'language_id' => LanguageType::ENGLISH->value,
                ],
                [
                    'name' => $name,
                    'job_position' => $job,
                    'description' => $description,
                    'language_id' => LanguageType::SLOVAK->value,
                ],
            ]);
        });
    }
}

