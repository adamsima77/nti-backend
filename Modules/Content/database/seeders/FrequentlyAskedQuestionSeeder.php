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
        $faq1 = FrequentlyAskedQuestion::create([
            'page_id' => PageType::PROGRAM_A,
        ]);

        $faq2 = FrequentlyAskedQuestion::create([
            'page_id' => PageType::PROGRAM_B,
        ]);


        $faq1->frequentlyAskedQuestionTranslations()->createMany([
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

        $faq2->frequentlyAskedQuestionTranslations()->createMany([
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
    }
}
