<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\SecurityClassification;

class SecurityClasificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            SecurityClassification::create(['name' => 'public']);
            SecurityClassification::create(['name' => 'internal']);
            SecurityClassification::create(['name' => 'confidential']);
    }
}
