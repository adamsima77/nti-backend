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
        Role::create(['name' => 'guest']);
        Role::create(['name' => 'student']);
        Role::create(['name' => 'team_leader']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'mentor']);
        Role::create(['name' => 'evaluator']);
        Role::create(['name' => 'cms_editor']);
        Role::create(['name' => 'nti_admin']);
        Role::create(['name' => 'nti_superadmin']);
    }
}
