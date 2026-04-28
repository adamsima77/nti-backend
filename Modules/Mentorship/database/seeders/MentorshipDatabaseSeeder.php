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
            MentorshipSeeder::class,
            MilestoneSeeder::class,
            DocumentHasMilestoneSeeder::class,
            MilestoneCommentsSeeder::class,
            MentorshipSessionSeeder::class,
        ]);
    }
}