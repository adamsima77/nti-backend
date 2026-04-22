<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;

class ApplicationsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            StatusOfApplicationSeeder::class,
            TypeOfApplicationSeeder::class,
        ]);
    }
}

