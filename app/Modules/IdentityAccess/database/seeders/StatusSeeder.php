<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::updateOrCreate(['id' => 1], ['name' => 'pending_email']);
        Status::updateOrCreate(['id' => 2], ['name' => 'pending_onboarding']);
        Status::updateOrCreate(['id' => 3], ['name' => 'active']);
        Status::updateOrCreate(['id' => 4], ['name' => 'inactive']);
        Status::updateOrCreate(['id' => 5], ['name' => 'banned']);
        Status::updateOrCreate(['id' => 6], ['name' => 'pending_approval']);
        Status::updateOrCreate(['id' => 7], ['name' => 'anonymized']);
    }
}
