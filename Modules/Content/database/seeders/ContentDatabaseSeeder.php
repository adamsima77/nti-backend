<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\FrequentlyAskedQuestion;
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
            HeroBannerSeeder::class,
            FrequentlyAskedQuestionSeeder::class,
            MetaTagTranslationSeeder::class,
            SiteMemberSeeder::class,
            NewsSeeder::class
        ]);
        PartnerReference::factory()->count(20)->create();
        Partner::factory()->count(20)->create();
    }
}
