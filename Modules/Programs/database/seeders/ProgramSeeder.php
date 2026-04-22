<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\TypeOfProgram;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programA = TypeOfProgram::query()->where('name', 'Program A')->first();
        $programB = TypeOfProgram::query()->where('name', 'Program B')->first();

        if ($programA !== null) {
            Program::query()->updateOrCreate(
                ['name' => 'Grantový inkubačný program'],
                [
                    'type_of_program' => $programA->id,
                    'description' => 'Program pre vlastné inovatívne nápady študentov a tímov.',
                ]
            );
        }

        if ($programB !== null) {
            Program::query()->updateOrCreate(
                ['name' => 'Program živej praxe'],
                [
                    'type_of_program' => $programB->id,
                    'description' => 'Program pre reálne zadania od firiem a partnerov.',
                ]
            );
        }
    }
}
