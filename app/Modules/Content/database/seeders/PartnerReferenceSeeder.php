<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\PartnerReference;

class PartnerReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $references = [
           
            [
                'name' => 'Petra Kováčová',
                'job_position' => 'Senior Product Manager',
                'image' => 'https://ui-avatars.com/api/?name=Petra+Kov%C3%A1%C4%8Dov%C3%A1&background=ffffff&color=111827&size=256&rounded=true',
                'description_sk' => 'Petra zodpovedá za produktový dizajn a strategické partnerstvá v oblasti digitálnych služieb.',
                'description_en' => 'Petra is responsible for product design and strategic partnerships in digital services.',
            ],
            [
                'name' => 'Tomáš Nový',
                'job_position' => 'Head of Business Development',
                'image' => 'https://ui-avatars.com/api/?name=Tom%C3%A1%C5%A1+Nov%C3%BD&background=ffffff&color=111827&size=256&rounded=true',
                'description_sk' => 'Tomáš rozvíja obchodné príležitosti pre technologické projekty a externé partnerstvá.',
                'description_en' => 'Tomáš develops business opportunities for technology projects and external partnerships.',
            ],
            [
                'name' => 'Zuzana Krajčírová',
                'job_position' => 'Customer Success Lead',
                'image' => 'https://ui-avatars.com/api/?name=Zuzana+Kraj%C4%8Dirov%C3%A1&background=ffffff&color=111827&size=256&rounded=true',
                'description_sk' => 'Zuzana vedie tím starostlivosti o klientov a zabezpečuje hladkú komunikáciu s partnermi.',
                'description_en' => 'Zuzana leads the customer success team and ensures smooth partner communication.',
            ],
            [
                'name' => 'Lukáš Král',
                'job_position' => 'Strategic Partnerships Manager',
                'image' => 'https://ui-avatars.com/api/?name=Luk%C3%A1%C5%A1+Kr%C3%A1l&background=ffffff&color=111827&size=256&rounded=true',
                'description_sk' => 'Lukáš sa stará o strategické partnerstvá a ich rast v nových segmentoch.',
                'description_en' => 'Lukáš manages strategic partnerships and growth in new segments.',
            ],
        ];

        foreach ($references as $referenceData) {
            $reference = PartnerReference::query()->updateOrCreate(
                [
                    'name' => $referenceData['name'],
                    'job_position' => $referenceData['job_position'],
                ],
                [
                    'image' => $referenceData['image'],
                    'status_id' => 1,
                ]
            );

            $reference->partnerReferenceTranslations()->updateOrCreate(
                ['language_id' => LanguageType::SLOVAK->value],
                ['description' => $referenceData['description_sk']]
            );

            $reference->partnerReferenceTranslations()->updateOrCreate(
                ['language_id' => LanguageType::ENGLISH->value],
                ['description' => $referenceData['description_en']]
            );
        }

        $this->command?->newLine();
        $this->command?->info('✅ CMS Partner References seeded: ' . count($references) . ' references');
    }
}
