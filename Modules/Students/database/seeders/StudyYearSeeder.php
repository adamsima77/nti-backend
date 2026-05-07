<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\StudyYear;

class StudyYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudyYear::create(['name' => '1. ročník (Bc.)']);
        StudyYear::create(['name' => '2. ročník (Bc.)']);
        StudyYear::create(['name' => '3. ročník (Bc.)']);
        StudyYear::create(['name' => '1. ročník (Mgr./Ing.)']);
        StudyYear::create(['name' => '2. ročník (Mgr./Ing.)']);
    }
}
