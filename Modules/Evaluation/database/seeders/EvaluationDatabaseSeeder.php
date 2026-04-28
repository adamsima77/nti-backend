<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;

class EvaluationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DecisionSeeder::class,
            CommissionSeeder::class,
            CommissionMemberSeeder::class,
            EvaluationSeeder::class,
            EvaluationScoreSeeder::class,
        ]);
    }
}