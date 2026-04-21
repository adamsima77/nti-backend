<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Category;
use Modules\Content\Models\News;
use Modules\Content\Models\NewsTranslation;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\Status;
use Modules\IdentityAccess\Models\User;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        $statusId = Status::query()->inRandomOrder()->value('id')
            ?? Status::first()?->id;

        if (!$statusId) {
            $statusId = Status::create([
                'name' => 'active',
            ])->id;
        }

        $user = User::where('email', 'cms@site.test')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'CMS',
                'surname' => 'Editor',
                'email' => 'cms@site.test',
                'password' => bcrypt('password'),
                'status_id' => $statusId,
            ]);

            $role = Role::where('name', 'editor obsahu')->first();

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }

        return [
            'slug' => $this->faker->slug(),
            'category_id' => Category::query()->inRandomOrder()->value('id')
                ?? Category::first()?->id,
            'user_id' => $user->id,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (News $news) {
            $news->newsTranslations()->createMany([
                [
                    'title' => $this->faker->sentence(),
                    'description' => $this->faker->paragraph(),
                    'language_id' => LanguageType::ENGLISH->value,
                ],
                [
                    'title' => $this->faker->sentence(),
                    'description' => $this->faker->paragraph(),
                    'language_id' => LanguageType::SLOVAK->value,
                ],
            ]);
        });
    }
}

