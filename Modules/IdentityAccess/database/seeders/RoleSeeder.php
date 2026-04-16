<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'návštevník']);
        Role::create(['name' => 'študent']);
        Role::create(['name' => 'vedúci tímu']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'mentor']);
        Role::create(['name' => 'hodnotiteľ']);
        Role::create(['name' => 'editor obsahu']);
        Role::create(['name' => 'nti administrátor']);
        Role::create(['name' => 'super administrátor']);
    }
}
