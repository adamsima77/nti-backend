<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Students\Models\StudyYear;

class StudyYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years_sk = [
            '1. ročník (Bc.)',
            '2. ročník (Bc.)',
            '3. ročník (Bc.)',
            '1. ročník (Mgr./Ing.)',
            '2. ročník (Mgr./Ing.)',
        ];

        $years_en = [
            '1st year (Bc.)',
            '2nd year (Bc.)',
            '3rd year (Bc.)',
            '1st year (Mgr./Ing.)',
            '2nd year (Mgr./Ing.)',
        ];

        foreach ($years_sk as $index => $yearSk) {

            $studyYear = StudyYear::create();

            $studyYear->studyYearTranslations()->create([
                'language_id' => LanguageType::SLOVAK,
                'name' => $yearSk,
            ]);

            $studyYear->studyYearTranslations()->create([
                'language_id' => LanguageType::ENGLISH,
                'name' => $years_en[$index],
            ]);
        }
    }
}
