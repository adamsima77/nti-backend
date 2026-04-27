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
        $memberOne   = SiteMember::create(['name' => 'Michael Doe']);
        $memberTwo   = SiteMember::create(['name' => 'John Doe']);
        $memberThree = SiteMember::create(['name' => 'Jane Doe']);

        $memberOne->siteMemberTranslations()->createMany([
            ['job_position' => 'Backend Developer', 'language_id' => LanguageType::ENGLISH->value],
            ['job_position' => 'Backend vývojár',   'language_id' => LanguageType::SLOVAK->value],
        ]);

        $memberTwo->siteMemberTranslations()->createMany([
            ['job_position' => 'Frontend Developer', 'language_id' => LanguageType::ENGLISH->value],
            ['job_position' => 'Frontend vývojár',   'language_id' => LanguageType::SLOVAK->value],
        ]);

        $memberThree->siteMemberTranslations()->createMany([
            ['job_position' => 'Fullstack Developer', 'language_id' => LanguageType::ENGLISH->value],
            ['job_position' => 'Fullstack vývojár',   'language_id' => LanguageType::SLOVAK->value],
        ]);
    }
}
