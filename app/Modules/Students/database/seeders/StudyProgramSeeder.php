<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Students\Models\StudyProgram;

class StudyProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs_sk = [
            'Informatika',
            'Aplikovaná informatika',
            'Softvérové inžinierstvo',
            'Kybernetická bezpečnosť',
            'Umelá inteligencia',
            'Počítačové siete a komunikácie',
            'Informačné systémy',
            'Manažment v informatike',
        ];

        $programs_en = [
            'Computer Science',
            'Applied Computer Science',
            'Software Engineering',
            'Cybersecurity',
            'Artificial Intelligence',
            'Computer Networks and Communications',
            'Information Systems',
            'IT Management',
        ];

        foreach ($programs_sk as $index => $programSk) {

            $studyProgram = StudyProgram::create();

            $studyProgram->studyProgramTranslations()->create([
                'language_id' => LanguageType::SLOVAK,
                'name' => $programSk,
            ]);

            $studyProgram->studyProgramTranslations()->create([
                'language_id' => LanguageType::ENGLISH,
                'name' => $programs_en[$index],
            ]);
        }
    }
}
