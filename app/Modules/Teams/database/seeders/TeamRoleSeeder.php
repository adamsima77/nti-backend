<?php

namespace Modules\Teams\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Teams\Models\TeamRole;

class TeamRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Vedúci tímu',
            'Člen tímu',
        ];

        foreach ($roles as $role) {
            TeamRole::firstOrCreate(['name' => $role]);
        }
    }
}
