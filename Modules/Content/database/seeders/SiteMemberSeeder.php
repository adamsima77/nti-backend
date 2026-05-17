<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\SiteMember;

class SiteMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteMember::create([
            'name' => 'Michael Doe',
            'job_position' => 'Chief Executive Officer',
            'status_id' => 1,
        ]);

        SiteMember::create([
            'name' => 'John Doe',
            'job_position' => 'Project Manager',
            'status_id' => 1,
        ]);

        SiteMember::create([
            'name' => 'Jane Doe',
            'job_position' => 'Marketing Specialist',
            'status_id' => 1,
        ]);
    }
}
