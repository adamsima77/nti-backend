<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\NotificationCategory;

class NotificationCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug'  => 'project',
                'name'  => 'Projekty',
                'icon'  => 'Users',
                'color' => 'bg-blue-600',
            ],
            [
                'slug'  => 'milestone',
                'name'  => 'Míľniky',
                'icon'  => 'Flag',
                'color' => 'bg-yellow-500',
            ],
            [
                'slug'  => 'consultation',
                'name'  => 'Konzultácie',
                'icon'  => 'MessageSquare',
                'color' => 'bg-purple-600',
            ],
            [
                'slug'  => 'system_alert',
                'name'  => 'Systém',
                'icon'  => 'AlertTriangle',
                'color' => 'bg-red-600',
            ],
            [
                'slug'  => 'registration',
                'name'  => 'Registrácia',
                'icon'  => 'UserPlus',
                'color' => 'bg-green-600',
            ],
            [
                'slug'  => 'verification',
                'name'  => 'Overenie',
                'icon'  => 'ShieldCheck',
                'color' => 'bg-green-500',
            ],
            [
                'slug'  => 'security',
                'name'  => 'Bezpečnosť',
                'icon'  => 'Shield',
                'color' => 'bg-red-600',
            ],
            [
                'slug'  => 'onboarding',
                'name'  => 'Onboarding',
                'icon'  => 'Rocket',
                'color' => 'bg-blue-500',
            ],
            [
                'slug'  => 'application',
                'name'  => 'Prihláška',
                'icon'  => 'FileText',
                'color' => 'bg-indigo-600',
            ],
            [
                'slug'  => 'status_change',
                'name'  => 'Zmena stavu',
                'icon'  => 'RefreshCw',
                'color' => 'bg-orange-500',
            ],
            [
                'slug'  => 'evaluation',
                'name'  => 'Hodnotenie',
                'icon'  => 'Star',
                'color' => 'bg-yellow-600',
            ],
            [
                'slug'  => 'team',
                'name'  => 'Tím',
                'icon'  => 'Users',
                'color' => 'bg-teal-600',
            ],
        ];

        foreach ($categories as $category) {
            NotificationCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
