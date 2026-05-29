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
            MentorDemoSeeder::class,
        ]);
    }
}