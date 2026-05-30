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
        Role::firstOrCreate(['name' => 'guest']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'team_leader']);
        Role::firstOrCreate(['name' => 'partner']);
        Role::firstOrCreate(['name' => 'organization']);
        Role::firstOrCreate(['name' => 'mentor']);
        Role::firstOrCreate(['name' => 'evaluator']);
        Role::firstOrCreate(['name' => 'cms_editor']);
        Role::firstOrCreate(['name' => 'content-manager']);
        Role::firstOrCreate(['name' => 'nti_admin']);
        Role::firstOrCreate(['name' => 'nti_superadmin']);
    }
}
