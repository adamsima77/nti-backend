<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;

class MentorshipDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([

            MilestoneStatusSeeder::class,

            MentorDemoSeeder::class,
            MentorshipSeeder::class,
            MentorshipSessionSeeder::class,
            MilestoneSeeder::class,
            MilestoneCommentsSeeder::class,
            DocumentHasMilestoneSeeder::class,
        ]);
    }
}
