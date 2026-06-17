<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\CmsStatus;

class CmsStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                 CmsStatus::create(['name' => 'Publikované']);
                 CmsStatus::create(['name' => 'Koncept']);
    }
}
