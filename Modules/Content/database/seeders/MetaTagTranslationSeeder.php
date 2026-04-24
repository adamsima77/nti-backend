<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Enums\PageType;
use Modules\Content\Models\MetaTag;

class MetaTagTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $home = MetaTag::create(['page_id' => PageType::HOME->value]);
        $about = MetaTag::create(['page_id' => PageType::ABOUT->value]);
        $calls = MetaTag::create(['page_id' => PageType::CALLS_AND_DEADLINES->value]);
        $programA = MetaTag::create(['page_id' => PageType::PROGRAM_A->value]);
        $programB = MetaTag::create(['page_id' => PageType::PROGRAM_B->value]);
        $contact = MetaTag::create(['page_id' => PageType::CONTACT->value]);

        /*
        | HOME
        */
        $home->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Nitriansky Technický Inkubátor',
            'description' => 'Podpora inovácií, startupov a mladých talentov v technologickom svete.',
            'og_title' => 'Nitriansky Technický Inkubátor',
            'og_description' => 'Objav programy, mentoring a komunitu, ktorá ťa posunie vpred.',
            'og_type' => 'website',
            'og_url' => '/sk',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Nitriansky Technický Inkubátor',
            'twitter_description' => 'Objav programy, mentoring a komunitu.',
        ]);

        $home->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'Nitra Technology Incubator',
            'description' => 'Supporting innovation, startups, and young tech talent.',
            'og_title' => 'Nitra Technology Incubator',
            'og_description' => 'Discover programs, mentorship, and a thriving tech community.',
            'og_type' => 'website',
            'og_url' => '/en',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Nitra Technology Incubator',
            'twitter_description' => 'Discover programs, mentorship, and community.',
        ]);

        /*
        | ABOUT
        */
        $about->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'O nás',
            'description' => 'Zistite viac o našej misii, vízii a tíme.',
            'og_title' => 'O nás | Inkubátor',
            'og_description' => 'Spoznajte náš tím a našu víziu podpory inovácií.',
            'og_type' => 'article',
            'og_url' => '/sk/o-nas',
            'twitter_card' => 'summary',
            'twitter_title' => 'O nás',
            'twitter_description' => 'Spoznajte náš tím a víziu.',
        ]);

        $about->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'About Us',
            'description' => 'Learn more about our mission, vision, and team.',
            'og_title' => 'About Us | Incubator',
            'og_description' => 'Meet our team and discover our mission.',
            'og_type' => 'article',
            'og_url' => '/en/about',
            'twitter_card' => 'summary',
            'twitter_title' => 'About Us',
            'twitter_description' => 'Meet our team and mission.',
        ]);

        /*
        | CALLS & DEADLINES
        */
        $calls->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Výzvy a termíny',
            'description' => 'Aktuálne výzvy a dôležité termíny pre zapojenie.',
            'og_title' => 'Výzvy a termíny',
            'og_description' => 'Zistite, do ktorých výziev sa môžete zapojiť.',
            'og_type' => 'website',
            'og_url' => '/sk/vyzvy',
            'twitter_card' => 'summary',
            'twitter_title' => 'Výzvy a termíny',
            'twitter_description' => 'Pozrite si aktuálne výzvy.',
        ]);

        $calls->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'Calls and Deadlines',
            'description' => 'Current opportunities and important deadlines.',
            'og_title' => 'Calls and Deadlines',
            'og_description' => 'Explore available opportunities and deadlines.',
            'og_type' => 'website',
            'og_url' => '/en/calls',
            'twitter_card' => 'summary',
            'twitter_title' => 'Calls and Deadlines',
            'twitter_description' => 'Explore current opportunities.',
        ]);

        /*
        | PROGRAM A
        */
        $programA->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Program A',
            'description' => 'Intenzívny program pre rozvoj zručností a kariéry.',
            'og_title' => 'Program A',
            'og_description' => 'Získaj skúsenosti a mentoring v intenzívnom programe.',
            'og_type' => 'article',
            'og_url' => '/sk/program-a',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Program A',
            'twitter_description' => 'Rozvíjaj svoje schopnosti.',
        ]);

        $programA->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'Program A',
            'description' => 'An intensive program for skill and career development.',
            'og_title' => 'Program A',
            'og_description' => 'Gain experience and mentorship in an intensive program.',
            'og_type' => 'article',
            'og_url' => '/en/program-a',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Program A',
            'twitter_description' => 'Boost your career.',
        ]);

        /*
        | PROGRAM B
        */
        $programB->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Program B',
            'description' => 'Riešenia firemných výziev pomocou talentovaných študentov.',
            'og_title' => 'Program B',
            'og_description' => 'Spojte sa s talentom a riešte reálne problémy.',
            'og_type' => 'article',
            'og_url' => '/sk/program-b',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Program B',
            'twitter_description' => 'Inovácie pre firmy.',
        ]);

        $programB->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'Program B',
            'description' => 'Solve company challenges with talented students.',
            'og_title' => 'Program B',
            'og_description' => 'Collaborate with talent to solve real problems.',
            'og_type' => 'article',
            'og_url' => '/en/program-b',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Program B',
            'twitter_description' => 'Innovate with talent.',
        ]);

        /*
        | CONTACT
        */
        $contact->metaTagTranslations()->create([
            'language_id' => LanguageType::SLOVAK->value,
            'title' => 'Kontakt',
            'description' => 'Spojte sa s nami a zistite viac.',
            'og_title' => 'Kontakt',
            'og_description' => 'Máte otázky? Kontaktujte nás.',
            'og_type' => 'website',
            'og_url' => '/sk/kontakt',
            'twitter_card' => 'summary',
            'twitter_title' => 'Kontakt',
            'twitter_description' => 'Ozvite sa nám.',
        ]);

        $contact->metaTagTranslations()->create([
            'language_id' => LanguageType::ENGLISH->value,
            'title' => 'Contact',
            'description' => 'Get in touch with us and learn more.',
            'og_title' => 'Contact',
            'og_description' => 'Have questions? Reach out to us.',
            'og_type' => 'website',
            'og_url' => '/en/contact',
            'twitter_card' => 'summary',
            'twitter_title' => 'Contact',
            'twitter_description' => 'Reach out to us.',
        ]);
    }
}
