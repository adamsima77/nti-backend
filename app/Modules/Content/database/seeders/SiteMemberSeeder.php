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
            'image' => 'https://ui-avatars.com/api/?name=Michael+Doe&background=edf2f7&color=3b82f6',
            'status_id' => 1,
        ]);

        SiteMember::create([
            'name' => 'John Doe',
            'job_position' => 'Project Manager',
            'image' => 'https://ui-avatars.com/api/?name=John+Doe&background=edf2f7&color=3b82f6',
            'status_id' => 1,
        ]);

        SiteMember::create([
            'name' => 'Jane Doe',
            'job_position' => 'Marketing Specialist',
            'image' => 'https://ui-avatars.com/api/?name=Jane+Doe&background=edf2f7&color=3b82f6',
            'status_id' => 1,
        ]);
    }
}
