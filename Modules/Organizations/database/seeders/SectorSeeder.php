<?php

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Organizations\Models\Sector;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sector1 = Sector::create([]);
        $sector2 = Sector::create([]);
        $sector3 = Sector::create([]);
        $sector4 = Sector::create([]);
        $sector5 = Sector::create([]);
        $sector6 = Sector::create([]);
        $sector7 = Sector::create([]);
        $sector8 = Sector::create([]);
        $sector9 = Sector::create([]);
        $sector10 = Sector::create([]);

        $sector1->sectorTranslations()->createMany([
            [
                'name' => 'Information Technology',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Informačné technológie',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector2->sectorTranslations()->createMany([
            [
                'name' => 'Mechanical Engineering',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Strojárstvo',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector3->sectorTranslations()->createMany([
            [
                'name' => 'Electrical Engineering',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Elektrotechnika',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector4->sectorTranslations()->createMany([
            [
                'name' => 'Construction',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Stavebníctvo',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector5->sectorTranslations()->createMany([
            [
                'name' => 'Finance and Banking',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Financie a bankovníctvo',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector6->sectorTranslations()->createMany([
            [
                'name' => 'Healthcare',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Zdravotníctvo',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector7->sectorTranslations()->createMany([
            [
                'name' => 'Education',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Vzdelávanie',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector8->sectorTranslations()->createMany([
            [
                'name' => 'Agriculture',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Poľnohospodárstvo',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector9->sectorTranslations()->createMany([
            [
                'name' => 'Business and Marketing',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Obchod a marketing',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);

        $sector10->sectorTranslations()->createMany([
            [
                'name' => 'Transport and Logistics',
                'language_id' => LanguageType::ENGLISH->value,
            ],
            [
                'name' => 'Doprava a logistika',
                'language_id' => LanguageType::SLOVAK->value,
            ],
        ]);
    }
}
