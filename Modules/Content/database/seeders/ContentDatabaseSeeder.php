<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\News;
use Modules\Content\Models\Partner;
use Modules\Content\Models\PartnerReference;

class ContentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PageSeeder::class,
            LanguageSeeder::class,
            CategorySeeder::class,
            HeroBannerSeeder::class

        ]);
        News::factory()->count(20)->create();
        PartnerReference::factory()->count(20)->create();
        Partner::factory()->count(20)->create();

    }
}
