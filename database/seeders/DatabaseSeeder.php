<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Database\Seeders\LanguageSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            \Modules\IdentityAccess\Database\Seeders\IdentityAccessDatabaseSeeder::class,
            \Modules\Content\Database\Seeders\ContentDatabaseSeeder::class,
            \Modules\Students\Database\Seeders\StudentsDatabaseSeeder::class,
            \Modules\Teams\Database\Seeders\TeamsDatabaseSeeder::class,
            \Modules\Organizations\Database\Seeders\OrganizationsDatabaseSeeder::class,
            \Modules\Applications\Database\Seeders\ApplicationsDatabaseSeeder::class,
            \Modules\Programs\Database\Seeders\ProgramsDatabaseSeeder::class,
            \Modules\Mentorship\Database\Seeders\MentorshipDatabaseSeeder::class,
            \Modules\Applications\Database\Seeders\DemoProjectSeeder::class,
            \Modules\Evaluation\Database\Seeders\EvaluationDatabaseSeeder::class,
            \Modules\Notifications\Database\Seeders\NotificationCategorySeeder::class,
            \Modules\Notifications\Database\Seeders\NotificationCategoryTranslationSeeder::class,
        ]);
    }
}
