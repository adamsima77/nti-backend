<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Category;
use Modules\Content\Models\News;
use Modules\IdentityAccess\Models\User;

class CmsNewsContentSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'cms_editor');
        })->first()?->id ?? User::query()->first()?->id ?? 1;

        $categoryId = Category::query()->first()?->id ?? 1;

        $newsData = [
            [
                'title_sk' => 'NTI otvára nový grantový program A na jeseň',
                'title_en' => 'NTI opens new grant program A in autumn',
                'main_description_sk' => 'Máte inovatívny nápad? Získajte mentoring, praktické workshopy a podiel z celkového fondu 50 000 EUR.',
                'main_description_en' => 'Got an innovative idea? Get mentoring, hands-on workshops, and a share of the total EUR 50,000 fund.',
                'description_sk' => '<p>Nitriansky technologický inkubátor otvára nový ročník svojho grantového programu. Študenti a juniorní vývojári sa môžu prihlásiť s vlastnými inovatívnymi nápadmi.</p><p>Projekt ponúka mentoring, workshopové stretnutia a možnosť pilotného nasadenia víťazných riešení. Celkový fond grantov: 50 000 EUR.</p>',
                'description_en' => '<p>The Nitra Technology Incubator is opening a new round of its grant program. Students and junior developers can apply with their innovative ideas.</p><p>The program offers mentorship, workshop sessions, and the chance to pilot winning solutions. Total grant fund: EUR 50,000.</p>',
                'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 3,
            ],
            [
                'title_sk' => 'Úspešný hackathon s 200 účastníkmi',
                'title_en' => 'Successful hackathon with 200 participants',
                'main_description_sk' => 'Nitra ožila technológiami. Pozrite sa, ako dopadol súboj 50 inovatívnych riešení pred odbornou porotou.',
                'main_description_en' => 'Nitra came alive with technology. See how the battle of 50 innovative solutions turned out before an expert jury.',
                'description_sk' => '<p>Nedávno ukončený hackathon v Nitre zomkol 200 zapálených vývojárov, dizajnérov a projektantov. Viac ako 50 riešení prezentovalo svoju hodnotu pred odbornou porotou.</p><p>Víťazný tím EcoTrack získal finančnú dotáciu a mentorské vedenie na 6 mesiacov, pričom všetky tímy dostali spätnú väzbu a príležitosť ďalej rozvíjať svoj prototyp.</p>',
                'description_en' => '<p>The recently completed hackathon in Nitra brought together 200 passionate developers, designers, and project managers. More than 50 solutions were presented to an expert jury.</p><p>The winning team EcoTrack received a grant and 6 months of mentoring, while all teams gained feedback and the opportunity to continue developing their prototype.</p>',
                'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 7,
            ],
            [
                'title_sk' => 'Nový mentor v komunite: Prof. Dr. Ján Kováčik',
                'title_en' => 'New mentor in the community: Prof. Dr. Ján Kováčik',
                'main_description_sk' => 'Svetovo uznávaný odborník na umelú inteligenciu a strojové učenie posilňuje náš tím mentorov.',
                'main_description_en' => 'A globally recognized expert in artificial intelligence and machine learning joins our mentoring team.',
                'description_sk' => '<p>Radi predstavujeme nového mentora v NTI komunite. Prof. Dr. Ján Kováčik prináša 20 rokov skúseností v oblasti umelej inteligencie a strojového učenia.</p><p>Bude viesť pravidelné workshopy, konzultácie a pomáhať študentom premieňať koncepty na reálne aplikácie.</p>',
                'description_en' => '<p>We are pleased to introduce a new mentor to the NTI community. Prof. Dr. Ján Kováčik brings 20 years of experience in artificial intelligence and machine learning.</p><p>He will lead workshops, provide consultations, and help students turn concepts into real applications.</p>',
                'image' => 'https://images.unsplash.com/photo-1542744095-fcf48d80b0fd?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 14,
            ],
            [
                'title_sk' => 'Startupy inkubované cez NTI: Úspešné prvé kroky',
                'title_en' => 'Startups incubated through NTI: Successful first steps',
                'main_description_sk' => 'Tri projekty z ročníka 2024 uzavreli investičné kolo v celkovej hodnote 2,5 milióna EUR.',
                'main_description_en' => 'Three projects from the 2024 cohort closed an investment round worth a total of EUR 2.5 million.',
                'description_sk' => '<p>Tri startupy z ročníka 2024 už úspešne uzavreli investičné kolo. Ich spoločná hodnota dosahuje 2,5 milióna EUR.</p><p>Projekty sa zaoberajú AI riešeniami pre smart agriculture, finančné technológie a udržateľnú výrobu. NTI pomáha aj pri expanzii na zahraničné trhy.</p>',
                'description_en' => '<p>Three startups from the 2024 cohort have already successfully completed an investment round. Their combined valuation reaches EUR 2.5 million.</p><p>The projects focus on AI solutions for smart agriculture, fintech, and sustainable manufacturing. NTI is also supporting expansion to international markets.</p>',
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 30,
            ],
            [
                'title_sk' => 'Program B: Príležitosť pre firmy spolupracovať s talentami',
                'title_en' => 'Program B: Opportunity for companies to collaborate with talent',
                'main_description_sk' => 'Prepojte vašu firmu so šikovnými študentmi a získajte čerstvý pohľad na vaše technologické výzvy.',
                'main_description_en' => 'Connect your company with talented students and gain a fresh perspective on your technological challenges.',
                'description_sk' => '<p>Program B otvára brány pre IT firmy, ktoré majú reálne zadania pre študentské tímy. Firmy získavajú nové čeľuste a študenti praktickú skúsenosť.</p><p>Program prináša mentoring od skúsených odborníkov, podporu pri testovaní prototypov a možnosť využiť výsledky projektu v praxi. Počet projektov sa zvýšil o 40 % a mnoho firiem už našlo dlhodobých partnerov medzi účastníkmi.</p>',
                'description_en' => '<p>Program B opens doors for IT companies that have real tasks for student teams. Companies get fresh talent and students gain practical experience.</p><p>The program provides mentorship from experienced professionals, support for prototype testing, and the opportunity to implement project outcomes in practice. The number of projects increased by 40%, and many companies have already found long-term partners among participants.</p>',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 45,
            ],
            [
                'title_sk' => 'Partnership s Univerzitou Konštantína Filozofa',
                'title_en' => 'Partnership with Constantine the Philosopher University',
                'main_description_sk' => 'NTI spája sily s UKF v Nitre. Čakajú nás spoločné výskumné projekty, stáže a odborné prednášky.',
                'main_description_en' => 'NTI joins forces with UKF in Nitra. Joint research projects, internships, and expert lectures await us.',
                'description_sk' => '<p>NTI uzavrela partnerskú dohodu s UKF v Nitre. Budú sa vzájomne podporovať v oblasti vzdelávania v technologických oblastiach.</p><p>V rámci spolupráce vznikne séria prednášok, študentských stáží a spoločných pilotných výskumných projektov zameraných na prax.</p>',
                'description_en' => '<p>NTI has signed a partnership agreement with UKF in Nitra. They will support each other in technology education.</p><p>The collaboration will include lectures, student internships and joint pilot research projects focused on practical outcomes.</p>',
                'image' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 60,
            ],
            [
                'title_sk' => 'Mentorský program je otvorený!',
                'title_en' => 'Mentorship program is open!',
                'main_description_sk' => 'Získaj osobné vedenie od špičkových IT profesionálov a posuň svoj projekt na nový level.',
                'main_description_en' => 'Get 1-on-1 guidance from top IT professionals and take your project to the next level.',
                'description_sk' => '<p>Skúsení profesionáli z IT otvárajú svoje dvere pre študentov a začínajúcich vývojárov.</p><p>Mentorský program NTI prináša personalizované vedenie, networking, kariérne poradenstvo a pravidelný feedback pri budovaní reálnych projektov.</p>',
                'description_en' => '<p>Experienced IT professionals are opening their doors to students and junior developers.</p><p>NTI\'s mentorship program provides personalized guidance, networking, career counseling, and regular feedback while building real-world projects.</p>',
                'image' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 90,
            ],
            [
                'title_sk' => 'NTI získal uznanie v kategórii Inovácia roka',
                'title_en' => 'NTI receives recognition in Innovation of the Year category',
                'main_description_sk' => 'Naša práca na podporu startupov a prepojenie akademického sveta s praxou bola ocenená.',
                'main_description_en' => 'Our work in supporting startups and bridging academia with industry has been officially recognized.',
                'description_sk' => '<p>Nitriansky technologický inkubátor bol ocenený na celoštátnej konferencii za svoj prínos k ekosystému inovácií.</p><p>Ocenenie potvrdzuje úspešnú podporu startupov a kvalitnú spoluprácu medzi akademickou sférou a priemyslom.</p>',
                'description_en' => '<p>The Nitra Technology Incubator was honored at a national conference for its contribution to the innovation ecosystem.</p><p>The award confirms successful startup support and strong collaboration between academia and industry.</p>',
                'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80',
                'days_ago' => 120,
            ],
        ];

        foreach ($newsData as $index => $data) {
            $news = News::query()->updateOrCreate(
                ['slug' => Str::slug($data['title_en'] . '-' . ($index + 1))],
                [
                    'category_id' => $categoryId,
                    'user_id' => $userId,
                    'image' => $data['image'],
                    'status_id' => 1,
                    'created_at' => now()->subDays($data['days_ago']),
                    'updated_at' => now()->subDays($data['days_ago']),
                ]
            );

            $news->newsTranslations()->updateOrCreate(
                ['language_id' => LanguageType::SLOVAK->value],
                [
                    'title' => $data['title_sk'],
                    'main_description' => $data['main_description_sk'],
                    'description' => $data['description_sk'],
                ]
            );

            $news->newsTranslations()->updateOrCreate(
                ['language_id' => LanguageType::ENGLISH->value],
                [
                    'title' => $data['title_en'],
                    'main_description' => $data['main_description_en'],
                    'description' => $data['description_en'],
                ]
            );
        }

        $this->command?->newLine();
        $this->command?->info('✅ CMS News content seeded: ' . count($newsData) . ' articles');
    }
}
