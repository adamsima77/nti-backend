<?php

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\Sector;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = Sector::all();

        Organization::factory(10)->create()->each(
            function (Organization $organization) use ($sectors) {
            $organization->sectors()->attach(
                $sectors->random(rand(1, 3))->pluck('id')->toArray()
            );
        });
    }
}
