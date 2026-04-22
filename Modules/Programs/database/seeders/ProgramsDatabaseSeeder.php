<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;

class ProgramsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            TypeOfProgramSeeder::class,
            ProgramSeeder::class,
            CallTypeSeeder::class,
            StatusOfCallSeeder::class,
            CallCriterionSeeder::class,
            CallSeeder::class,
        ]);
    }
}
