<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Students\Models\StudyField;

class StudyFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields_sk = [
            'Informatika',
            'Kybernetika',
            'Elektrotechnika',
            'Ekonómia a manažment',
            'Obchod a marketing',
            'Dizajn',
        ];

        $fields_en = [
            'Computer Science',
            'Cybernetics',
            'Electrical Engineering',
            'Economics and Management',
            'Business and Marketing',
            'Design',
        ];

        foreach ($fields_sk as $index => $fieldSk) {

            $studyField = StudyField::create();

            $studyField->studyFieldTranslations()->create([
                'language_id' => LanguageType::SLOVAK,
                'name' => $fieldSk,
            ]);

            $studyField->studyFieldTranslations()->create([
                'language_id' => LanguageType::ENGLISH,
                'name' => $fields_en[$index],
            ]);
        }
    }
}
