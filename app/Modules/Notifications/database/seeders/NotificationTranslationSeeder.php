<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\Notifications;
use Modules\Notifications\Models\NotificationTranslation;

class NotificationTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            'Nový projekt' => [
                1 => ['title' => 'Nový projekt',                   'body' => 'EcoTrack priradený'],
                2 => ['title' => 'New Project',                    'body' => 'EcoTrack assigned'],
            ],
            'Míľnik čaká na schválenie' => [
                1 => ['title' => 'Míľnik čaká na schválenie',      'body' => 'AI chatbot – MVP'],
                2 => ['title' => 'Milestone Awaiting Approval',    'body' => 'AI chatbot – MVP'],
            ],
            'Nová konzultácia' => [
                1 => ['title' => 'Nová konzultácia',               'body' => 'EcoTrack review'],
                2 => ['title' => 'New Consultation',               'body' => 'EcoTrack review'],
            ],
            'Údržba systému' => [
                1 => ['title' => 'Údržba systému',                 'body' => 'Downtime 04.04.2026 02:00–04:00'],
                2 => ['title' => 'System Maintenance',             'body' => 'Downtime 04.04.2026 02:00–04:00'],
            ],
        ];

        foreach ($translations as $title => $langs) {
            $notification = Notifications::where('title', $title)->first();

            if (!$notification) {
                continue;
            }

            foreach ($langs as $languageId => $data) {
                NotificationTranslation::updateOrCreate(
                    [
                        'notification_id' => $notification->id,
                        'language_id'     => $languageId,
                    ],
                    $data
                );
            }
        }
    }
}
