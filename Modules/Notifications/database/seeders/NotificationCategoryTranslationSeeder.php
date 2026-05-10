<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\NotificationCategoryTranslation;

class NotificationCategoryTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            'project' => [
                1 => 'Projekty',
                2 => 'Projects',
            ],
            'milestone' => [
                1 => 'Míľniky',
                2 => 'Milestones',
            ],
            'consultation' => [
                1 => 'Konzultácie',
                2 => 'Consultations',
            ],
            'system_alert' => [
                1 => 'Systém',
                2 => 'System',
            ],
            'registration' => [
                1 => 'Registrácia',
                2 => 'Registration',
            ],
            'verification' => [
                1 => 'Overenie',
                2 => 'Verification',
            ],
            'security' => [
                1 => 'Bezpečnosť',
                2 => 'Security',
            ],
            'onboarding' => [
                1 => 'Onboarding',
                2 => 'Onboarding',
            ],
            'application' => [
                1 => 'Prihláška',
                2 => 'Application',
            ],
            'status_change' => [
                1 => 'Zmena stavu',
                2 => 'Status Change',
            ],
            'evaluation' => [
                1 => 'Hodnotenie',
                2 => 'Evaluation',
            ],
            'team' => [
                1 => 'Tím',
                2 => 'Team',
            ],
        ];

        foreach ($translations as $slug => $langs) {
            $category = NotificationCategory::where('slug', $slug)->first();

            if (!$category) {
                continue;
            }

            foreach ($langs as $languageId => $name) {
                NotificationCategoryTranslation::updateOrCreate(
                    [
                        'notification_category_id' => $category->id,
                        'language_id'              => $languageId,
                    ],
                    ['name' => $name]
                );
            }
        }
    }
}
