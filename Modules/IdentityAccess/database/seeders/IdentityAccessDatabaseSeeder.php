<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;

class IdentityAccessDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
             RoleSeeder::class,
             PermissionSeeder::class,
             StatusSeeder::class,
             ConsentTypeSeeder::class,
         ]);
        // Removed random user and consent factory seeding. Use explicit demo seeders only.

        // Predvídateľný študentský účet na testovanie (e-mail zodpovedá menu).
        $this->call([
            DemoStudentUserSeeder::class,
            DemoMentorUserSeeder::class,
            DemoEvaluatorUserSeeder::class,
            DemoOrganizationUserSeeder::class,
        ]);
    }
}
