<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\ConsentType;

class ConsentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConsentType::create([
            'name' => 'terms',
            'description' => 'Súhlas s obchodnými podmienkami',
        ]);

        ConsentType::create([
            'name' => 'cookies',
            'description' => 'Súhlas s používaním cookies',
        ]);

        ConsentType::create([
            'name' => 'marketing',
            'description' => 'Súhlas s marketingovými emailami',
        ]);

        ConsentType::create([
            'name' => 'profiling',
            'description' => 'Súhlas s personalizáciou a profilovaním',
        ]);
    }
}
