<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CmsStatusSeeder::class,
            PageSeeder::class,
            //LanguageSeeder::class,
            CategorySeeder::class,
            HeroBannerSeeder::class,
            MetaTagTranslationSeeder::class,
            SiteMemberSeeder::class,
            CmsNewsContentSeeder::class,
            CmsFaqSeeder::class,
            CmsPartnersSeeder::class,
            PartnerReferenceSeeder::class,
        ]);
    }
}
