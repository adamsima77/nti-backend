<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Enums\PageType;
use Modules\Content\Models\FrequentlyAskedQuestion;

class CmsFaqSeeder extends Seeder
{
    public function run(): void
    {
        $pageMap = [
            'Program A' => PageType::PROGRAM_A->value,
            'Program B' => PageType::PROGRAM_B->value,
            'General' => PageType::CONTACT->value,
        ];

        $faqs = [
            [
                'question_sk' => 'Aká je minimálna veľkosť tímu?',
                'question_en' => 'What is the minimum team size?',
                'answer_sk' => 'Minimálny počet členov je 3. Tím môže byť aj väčší.',
                'answer_en' => 'The minimum team size is 3 members. The team can be larger.',
                'category' => 'Program A',
            ],
            [
                'question_sk' => 'Ako sa hodnotia prihlášky?',
                'question_en' => 'How are applications evaluated?',
                'answer_sk' => 'Prihlášky sú hodnotené komisiou podľa inovatívnosti a technickej realizovateľnosti.',
                'answer_en' => 'Applications are evaluated by a committee based on innovation and technical feasibility.',
                'category' => 'Program A',
            ],
            [
                'question_sk' => 'Aký je maximálny grant?',
                'question_en' => 'What is the maximum grant?',
                'answer_sk' => 'Maximálny grant pre Program A je 10,000 EUR na projekt.',
                'answer_en' => 'The maximum grant for Program A is EUR 10,000 per project.',
                'category' => 'Program A',
            ],
            [
                'question_sk' => 'Ako si firma zadá technickú špecifikáciu?',
                'question_en' => 'How does a company submit a technical specification?',
                'answer_sk' => 'Firmy sa zaregistrujú, doplnia profil a vyplnia formulár s technickou špecifikáciou.',
                'answer_en' => 'Companies register, fill in their profile, and submit a technical specification form.',
                'category' => 'Program B',
            ],
            [
                'question_sk' => 'Koľko stojí Program B?',
                'question_en' => 'How much does Program B cost?',
                'answer_sk' => 'Program B je bezplatný pre firmy. NTI si dohoduje účasť na výsledku projektu.',
                'answer_en' => 'Program B is free for companies. NTI negotiates participation in the project outcome.',
                'category' => 'Program B',
            ],
            [
                'question_sk' => 'Ako dlho trvá projekt v Program B?',
                'question_en' => 'How long does a Program B project take?',
                'answer_sk' => 'Typicky 3-4 mesiace podľa zložitosti zadania.',
                'answer_en' => 'Typically 3-4 months depending on the complexity of the task.',
                'category' => 'Program B',
            ],
            [
                'question_sk' => 'Ako sa zaregistrujem?',
                'question_en' => 'How do I register?',
                'answer_sk' => 'Kliknite na "Registrácia", vyberte typ účtu a vyplnte formulár.',
                'answer_en' => 'Click on "Register", select your account type and fill in the form.',
                'category' => 'General',
            ],
            [
                'question_sk' => 'Ako si resetujem heslo?',
                'question_en' => 'How do I reset my password?',
                'answer_sk' => 'Na prihlasovacej stránke kliknite na "Zabudli ste heslo?" a zadajte svoju e-mail.',
                'answer_en' => 'On the login page, click "Forgot your password?" and enter your email.',
                'category' => 'General',
            ],
            [
                'question_sk' => 'Ako sa stať mentorom?',
                'question_en' => 'How do I become a mentor?',
                'answer_sk' => 'Mentorom sa stáva skúsený profesionál na pozývanie NTI.',
                'answer_en' => 'A mentor is invited by NTI based on professional experience.',
                'category' => 'General',
            ],
            [
                'question_sk' => 'Ako kontaktovať NTI?',
                'question_en' => 'How do I contact NTI?',
                'answer_sk' => 'Použite kontaktný formulár na stránke alebo nás napíšte na info@nti.sk',
                'answer_en' => 'Use the contact form on the website or email us at info@nti.sk',
                'category' => 'General',
            ],
            [
                'question_sk' => 'Ako dlho je účet aktívny?',
                'question_en' => 'How long is an account active?',
                'answer_sk' => 'Účet je aktívny dovtedy, kým si ho nezrušíte alebo pokým sa projekt neukonči.',
                'answer_en' => 'The account is active until you deactivate it or until the project is completed.',
                'category' => 'General',
            ],
            [
                'question_sk' => 'Môžu sa prihlásiť aj nešúdenti?',
                'question_en' => 'Can non-students apply?',
                'answer_sk' => 'Program je primárne určený pre študentov vysokých škôl. Profesionáli nemôžu aplikovať.',
                'answer_en' => 'The program is primarily for university students. Professionals cannot apply.',
                'category' => 'Program A',
            ],
        ];

        foreach ($faqs as $faqData) {
            $pageId = $pageMap[$faqData['category']] ?? PageType::CONTACT->value;

            $faq = FrequentlyAskedQuestion::query()
                ->whereHas('frequentlyAskedQuestionTranslations', function ($query) use ($faqData) {
                    $query->where('question', $faqData['question_en']);
                })
                ->first();

            if (! $faq) {
                $faq = FrequentlyAskedQuestion::create([
                    'page_id' => $pageId,
                    'status_id' => 1,
                ]);
            }

            $faq->frequentlyAskedQuestionTranslations()->updateOrCreate(
                ['language_id' => LanguageType::SLOVAK->value],
                [
                    'question' => $faqData['question_sk'],
                    'answer' => $faqData['answer_sk'],
                ]
            );

            $faq->frequentlyAskedQuestionTranslations()->updateOrCreate(
                ['language_id' => LanguageType::ENGLISH->value],
                [
                    'question' => $faqData['question_en'],
                    'answer' => $faqData['answer_en'],
                ]
            );
        }

        $this->command?->newLine();
        $this->command?->info('✅ CMS FAQ content seeded: ' . count($faqs) . ' questions');
    }
}
