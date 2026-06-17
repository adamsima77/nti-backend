<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\ConsentType;

class ConsentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $consents = [
            'privacy_policy',
            'terms_of_service',
            'cv_processing',
            'profile_data_processing',
            'company_data_processing',
            'contact_form_processing',
        ];

        foreach ($consents as $consent) {
            ConsentType::firstOrCreate(['name' => $consent]);
        }
    }
}
