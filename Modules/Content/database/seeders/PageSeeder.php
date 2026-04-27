<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\Page;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::create(['name' => 'home']);
        Page::create(['name' => 'about']);
        Page::create(['name' => 'calls-and-deadlines']);
        Page::create(['name' => 'program-a']);
        Page::create(['name' => 'program-b']);
        Page::create(['name' => 'contact']);
        Page::create(['name' => 'partners']);
}

}
