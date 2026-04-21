<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\HeroBanner;
use Modules\Content\Models\HeroBannerTranslation;
use Modules\Content\Models\Language;
use Modules\Content\Enums\PageType;



class HeroBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hero = HeroBanner::create(['page_id' => PageType::HOME->value]);
        $hero_1 = HeroBanner::create(['page_id' => PageType::CALLS_AND_DEADLINES->value]);
        $hero_2 = HeroBanner::create(['page_id' => PageType::PROGRAM_A->value]);
        $hero_3 = HeroBanner::create(['page_id' => PageType::PROGRAM_B->value]);


       $hero->heroBannerTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Objav Nitriansky Technický Inkubátor!',
            'description' => 'Interaktívne programy, mentorstvo a komunita, ktorá ťa posunie vpred.'
        ]);

        $hero->heroBannerTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'title' => 'Discover the Nitra Technology Incubator!',
                'description' => 'Interactive programs, mentorship, and a community that will move you forward.'
            ]
        );

        $hero_1->heroBannerTranslations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'title' => 'Výzvy a termíny',
                'description' => 'Pozrite si dostupné výzvy a zúčastnite sa tých, ktoré vás inšpirujú'
            ]
        );

        $hero_1->heroBannerTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'title' => 'Challenges and deadlines',
                'description' => 'Check out available challenges and take part in the ones that inspire you.'
            ]
        );

        $hero_2->heroBannerTranslations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'title' => 'Program A: Intenzívne zrýchlenie',
                'description' => 'Dynamický 3-mesačný program pre študentov a začínajúcich profesionálov. Získaj skúsenosti, mentoring a sieť kontaktov.'
            ]
        );

        $hero_2->heroBannerTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'title' => 'Program A: Intensive Acceleration',
                'description' => 'A dynamic 3-month program for students and early-career professionals. Gain experience, mentoring, and a network of contacts.'
            ]
        );

       $hero_3->heroBannerTranslations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'title' => 'Program B: Inovácia za firemných výziev',
                'description' => 'Najmite talentovaných študentov a juniorov vývojárov na riešenie svojich technických problémov. Pozrite si inovatívne riešenia, ktoré vám pomôžu rásť.'
            ]
        );


        $hero_3->heroBannerTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'title' => 'Program B: Innovation through corporate challenges',
                'description' => 'Hire talented students and junior developers to solve your technical problems. Discover innovative solutions that help your business grow.'
            ]
        );

    }
}
