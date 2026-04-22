<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\IdentityAccess\Database\Seeders\IdentityAccessDatabaseSeeder::class,
            \Modules\Content\Database\Seeders\ContentDatabaseSeeder::class,
            \Modules\Programs\Database\Seeders\ProgramsDatabaseSeeder::class,
            \Modules\Applications\Database\Seeders\ApplicationsDatabaseSeeder::class,
        ]);
    }
}
