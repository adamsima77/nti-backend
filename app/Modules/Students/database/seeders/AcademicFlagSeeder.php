<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\AcademicFlag;

class AcademicFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flags = [
            'Dekan\'s list',
            'Erasmus študent',
            'Doktorand',
            'Štipendista',
            'Publikovaný výskum',
            'Červený diplom',
            'Víťaz olympiády',
            'Vedúci tímového projektu',
            'Člen študentskej rady',
            'Absolvent zahraničnej stáže',
        ];

        foreach ($flags as $flag) {
            AcademicFlag::firstOrCreate(['name' => $flag]);
        }
    }
}
