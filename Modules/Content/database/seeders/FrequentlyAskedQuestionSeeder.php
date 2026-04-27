<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Enums\PageType;
use Modules\Content\Models\FrequentlyAskedQuestion;

class FrequentlyAskedQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Program A FAQs
        $faqA1 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_A]);
        $faqA2 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_A]);
        $faqA3 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_A]);
        $faqA4 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_A]);
        $faqA5 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_A]);

        // Program B FAQs
        $faqB1 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_B]);
        $faqB2 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_B]);
        $faqB3 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_B]);
        $faqB4 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_B]);
        $faqB5 = FrequentlyAskedQuestion::create(['page_id' => PageType::PROGRAM_B]);

        // -------------------------
        // Program A Translations
        // -------------------------

        $faqA1->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'What is Lorem Ipsum?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Čo je Lorem Ipsum?',
                'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqA2->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'Who can participate in Program A?',
                'answer' => 'Program A is open to all registered users who meet the eligibility requirements outlined in the terms and conditions.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Kto sa môže zúčastniť Programu A?',
                'answer' => 'Program A je otvorený všetkým registrovaným používateľom, ktorí spĺňajú podmienky oprávnenosti uvedené v podmienkach.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqA3->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'How do I register for Program A?',
                'answer' => 'You can register by completing the online registration form available on the Program A page and submitting the required documents.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Ako sa zaregistrujem do Programu A?',
                'answer' => 'Môžete sa zaregistrovať vyplnením online registračného formulára dostupného na stránke Programu A a odoslaním požadovaných dokumentov.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqA4->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'What are the benefits of Program A?',
                'answer' => 'Program A offers exclusive access to resources, personalized support, and various rewards based on your level of participation.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Aké sú výhody Programu A?',
                'answer' => 'Program A ponúka exkluzívny prístup k zdrojom, personalizovanú podporu a rôzne odmeny podľa úrovne vašej účasti.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqA5->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'How can I contact support for Program A?',
                'answer' => 'You can reach our support team via the contact form on our website or by emailing support@programa.com.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Ako môžem kontaktovať podporu pre Program A?',
                'answer' => 'Náš tím podpory môžete kontaktovať prostredníctvom kontaktného formulára na našej webovej stránke alebo e-mailom na support@programa.com.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        // -------------------------
        // Program B Translations
        // -------------------------

        $faqB1->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'How does this service work?',
                'answer' => 'It works by processing requests and returning structured responses.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Ako funguje táto služba?',
                'answer' => 'Funguje tak, že spracúva požiadavky a vracia štruktúrované odpovede.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqB2->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'Who is Program B designed for?',
                'answer' => 'Program B is designed for businesses and individuals looking for advanced features and higher usage limits.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Pre koho je Program B určený?',
                'answer' => 'Program B je určený pre firmy a jednotlivcov, ktorí hľadajú pokročilé funkcie a vyššie limity využívania.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqB3->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'Is there a trial period for Program B?',
                'answer' => 'Yes, Program B offers a 30-day free trial so you can explore all features before committing to a subscription.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Je k dispozícii skúšobné obdobie pre Program B?',
                'answer' => 'Áno, Program B ponúka 30-dňovú bezplatnú skúšobnú verziu, aby ste si mohli vyskúšať všetky funkcie pred uzavretím predplatného.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqB4->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'What payment methods are accepted for Program B?',
                'answer' => 'We accept all major credit cards, bank transfers, and select digital payment platforms for Program B subscriptions.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Aké spôsoby platby sú akceptované pre Program B?',
                'answer' => 'Pre predplatné Programu B prijímame všetky hlavné kreditné karty, bankové prevody a vybrané digitálne platobné platformy.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $faqB5->frequentlyAskedQuestionTranslations()->createMany([
            [
                'question' => 'Can I upgrade from Program B to a higher tier?',
                'answer' => 'Yes, you can upgrade your plan at any time from your account settings. The price difference will be prorated automatically.',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'question' => 'Môžem prejsť z Programu B na vyššiu úroveň?',
                'answer' => 'Áno, svoj plán môžete kedykoľvek upgradovať v nastaveniach účtu. Cenový rozdiel bude automaticky prepočítaný.',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);
    }
}
