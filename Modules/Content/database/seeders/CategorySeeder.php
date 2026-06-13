<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Category;
use Modules\Content\Models\CategoryTranslation;
use Modules\Content\Models\Language;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'events',        'sk' => 'Udalosti',  'en' => 'Events'],
            ['slug' => 'announcements', 'sk' => 'Oznámenia', 'en' => 'Announcements'],
            ['slug' => 'blog',          'sk' => 'Blog',      'en' => 'Blog'],
        ];

        foreach ($items as $item) {
            $category = Category::firstOrCreate(['slug' => $item['slug']]);

            $category->categoryTranslations()->updateOrCreate(
                ['language_id' => LanguageType::SLOVAK->value],
                ['name' => $item['sk']]
            );

            $category->categoryTranslations()->updateOrCreate(
                ['language_id' => LanguageType::ENGLISH->value],
                ['name' => $item['en']]
            );
        }
    }
}
