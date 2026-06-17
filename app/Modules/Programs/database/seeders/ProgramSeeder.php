<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\TypeOfProgram;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programAType = TypeOfProgram::query()->where('name', 'Program A')->first();
        $programBType = TypeOfProgram::query()->where('name', 'Program B')->first();

        /*
        | PROGRAM A
        */
        if ($programAType) {
            $programA = Program::create([
                'type_of_program_id' => $programAType->id,
            ]);

            $programA->programTranslations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'name' => 'Grantový inkubačný program',
                'description' => 'Program pre vlastné inovatívne nápady študentov a tímov.',
            ]);

            $programA->programTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'name' => 'Grant Incubation Program',
                'description' => 'A program for innovative ideas from students and teams.',
            ]);
        }

        /*
        | PROGRAM B
        */
        if ($programBType) {
            $programB = Program::create([
                'type_of_program_id' => $programBType->id,
            ]);

            $programB->programTranslations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'name' => 'Program živej praxe',
                'description' => 'Program pre reálne zadania od firiem a partnerov.',
            ]);

            $programB->programTranslations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'name' => 'Real Practice Program',
                'description' => 'A program focused on real-world assignments from companies and partners.',
            ]);
        }
    }
}
