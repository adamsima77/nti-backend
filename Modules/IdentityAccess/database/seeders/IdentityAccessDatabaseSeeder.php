<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\UserConsent;

class IdentityAccessDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->call([
             RoleSeeder::class,
             StatusSeeder::class,
             ConsentTypeSeeder::class
         ]);
        User::factory()->count(10)->create();
        UserConsent::factory()->count(20)->create();
    }
}
