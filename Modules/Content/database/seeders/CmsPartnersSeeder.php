<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Partner;

class CmsPartnersSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name' => 'Microsoft Slovensko s.r.o.',
                'description_sk' => 'Vedúca technologická korporácia s vývojom cloud riešení a AI produktov.',
                'description_en' => 'A leading technology corporation building cloud solutions and AI products.',
                'image' => 'https://ui-avatars.com/api/?name=Microsoft+Slovensko&background=ffffff&color=1f2937&size=256&rounded=true',
            ],
            [
                'name' => 'JetBrains Czech s.r.o.',
                'description_sk' => 'Vývojár profesionálnych IDE nástrojov pre vývojárov.',
                'description_en' => 'Developer of professional IDE tools for developers.',
                'image' => 'https://ui-avatars.com/api/?name=JetBrains+Czech&background=ffffff&color=111827&size=256&rounded=true',
            ],
            [
                'name' => 'ESET s.r.o.',
                'description_sk' => 'Globálna spoločnosť špecializujúca sa na bezpečnosť softvéru.',
                'description_en' => 'Global company specializing in software security.',
                'image' => 'https://ui-avatars.com/api/?name=ESET&background=ffffff&color=111827&size=256&rounded=true',
            ],
            [
                'name' => 'Slovanet a.s.',
                'description_sk' => 'Poskytovateľ internetových služieb a web hostingu.',
                'description_en' => 'Provider of internet services and web hosting.',
                'image' => 'https://ui-avatars.com/api/?name=Slovanet&background=ffffff&color=111827&size=256&rounded=true',
            ],
            [
                'name' => 'Quantum Systems Slovakia',
                'description_sk' => 'Startup orientovaný na aplikácie umelej inteligencie.',
                'description_en' => 'Startup focused on artificial intelligence applications.',
                'image' => 'https://ui-avatars.com/api/?name=Quantum+Systems&background=ffffff&color=111827&size=256&rounded=true',
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = Partner::query()->updateOrCreate(
                ['name' => $partnerData['name']],
                [
                    'image' => $partnerData['image'],
                    'status_id' => 1,
                ]
            );

            $partner->partnerTranslations()->updateOrCreate(
                ['language_id' => LanguageType::SLOVAK->value],
                ['description' => $partnerData['description_sk']]
            );

            $partner->partnerTranslations()->updateOrCreate(
                ['language_id' => LanguageType::ENGLISH->value],
                ['description' => $partnerData['description_en']]
            );
        }

        $this->command?->newLine();
        $this->command?->info('✅ CMS Partners seeded: ' . count($partners) . ' partners');
    }
}
