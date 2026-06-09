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
        $name = $this->faker->name();

        return [
            'name' => $name,
            'job_position' => $this->faker->jobTitle(),
            'image' => 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=edf2f7&color=3b82f6',
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

