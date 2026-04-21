<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Category;
use Modules\Content\Models\CategoryTranslation;
use Modules\Content\Models\Language;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $category = Category::create(['slug' => 'events']);
         $category_1 = Category::create(['slug' => 'announcements']);
         $category_2 = Category::create(['slug' => 'blog']);

        $category->categoryTranslations()->create([
            'name' => 'Udalosti',
            'language_id' => LanguageType::SLOVAK->value
        ]);

         $category->categoryTranslations()->create([
             'name' => 'Events',
             'language_id' => LanguageType::ENGLISH->value
         ]);

         $category_1->categoryTranslations()->create([
             'name' => 'Oznámenia',
             'language_id' => LanguageType::SLOVAK->value
         ]);

        $category_1->categoryTranslations()->create([
             'name' => 'Announcements',
             'language_id' => LanguageType::ENGLISH->value
         ]);

        $category_2->categoryTranslations()->create([
             'name' => 'Blog',
             'language_id' => LanguageType::SLOVAK->value
         ]);

         $category_2->categoryTranslations()->create([
             'name' => 'Blog',
             'language_id' => LanguageType::ENGLISH->value
         ]);
    }
}

