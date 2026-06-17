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
            'org_admin',
            'org_member',
            'org_product_owner'
        ];

        foreach ($roles as $role) {
            OrganizationRole::firstOrCreate(['name' => $role]);
        }
    }
}
