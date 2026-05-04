<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\ConsentType;

class ConsentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConsentType::create([
            'name' => 'privacy_policy',
            'description' => 'Acknowledgement of the Privacy Policy and consent to personal data processing for platform usage',
        ]);

        ConsentType::create([
            'name' => 'application_processing',
            'description' => 'Consent to process personal and project data for application evaluation and program participation',
        ]);

        ConsentType::create([
            'name' => 'communication',
            'description' => 'Consent to receive system notifications related to applications, evaluations, and program updates',
        ]);

        ConsentType::create([
            'name' => 'mentorship_collaboration',
            'description' => 'Consent to share relevant profile and project information with mentors, evaluators, and partners',
        ]);

        ConsentType::create([
            'name' => 'analytics',
            'description' => 'Consent to use anonymized data for analytics, reporting, and system improvement',
        ]);

        ConsentType::create([
            'name' => 'public_presentation',
            'description' => 'Consent to publish project and participant information for presentation, promotion, and reporting purposes',
        ]);
    }
}
