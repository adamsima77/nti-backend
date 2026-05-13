<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Programs\Models\Criterion;

class CallCriterionSeeder extends Seeder
{
    public function run(): void
    {
        // Create criteria
        $c1 = Criterion::create([]);
        $c2 = Criterion::create([]);
        $c3 = Criterion::create([]);
        $c4 = Criterion::create([]);

        // -------------------------
        // Translations
        // -------------------------

        $c1->criterionTranslations()->createMany([
            [
                'name' => 'Innovativeness',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Inovatívnosť',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $c2->criterionTranslations()->createMany([
            [
                'name' => 'Technical feasibility',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Technická realizovateľnosť',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $c3->criterionTranslations()->createMany([
            [
                'name' => 'Business potential',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Biznis potenciál',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $c4->criterionTranslations()->createMany([
            [
                'name' => 'Team readiness',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Tímová pripravenosť',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);
    }
}
