<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\StudyProgram;

class StudyProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            'Informatika',
            'Aplikovaná informatika',
            'Softvérové inžinierstvo',
            'Kybernetická bezpečnosť',
            'Umelá inteligencia',
            'Počítačové siete a komunikácie',
            'Informačné systémy',
            'Manažment v informatike',
        ];

        foreach ($programs as $program) {
            StudyProgram::firstOrCreate(['name' => $program]);
        }
    }
}
