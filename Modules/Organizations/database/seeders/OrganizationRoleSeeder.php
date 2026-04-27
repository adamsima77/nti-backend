<?php

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organizations\Models\OrganizationRole;

class OrganizationRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin',
            'member',
        ];

        foreach ($roles as $role) {
            OrganizationRole::firstOrCreate(['name' => $role]);
        }
    }
}
