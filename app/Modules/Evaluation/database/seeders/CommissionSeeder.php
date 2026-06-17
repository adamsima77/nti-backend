<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Commission;

class CommissionSeeder extends Seeder
{
    public function run(): void
    {
        Commission::create(['name' => 'Testovacia komisia']);
    }
}
