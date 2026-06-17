<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('Žiadny user nenájdený — preskakujem NotificationSeeder.');
            return;
        }

        $categories = NotificationCategory::pluck('id', 'slug');

        $notifications = [
            [
                'notification_category_id' => $categories['project'] ?? null,
                'title'   => 'Nový projekt',
                'body'    => 'EcoTrack priradený',
                'is_read' => false,
            ],
            [
                'notification_category_id' => $categories['milestone'] ?? null,
                'title'   => 'Míľnik čaká na schválenie',
                'body'    => 'AI chatbot – MVP',
                'is_read' => false,
            ],
            [
                'notification_category_id' => $categories['consultation'] ?? null,
                'title'   => 'Nová konzultácia',
                'body'    => 'EcoTrack review',
                'is_read' => true,
                'read_at' => now()->subDays(2),
            ],
            [
                'notification_category_id' => $categories['system_alert'] ?? null,
                'title'   => 'Údržba systému',
                'body'    => 'Downtime 04.04.2026 02:00–04:00',
                'is_read' => false,
            ],
        ];

        foreach ($notifications as $data) {
            if (!$data['notification_category_id']) {
                continue;
            }

            Notifications::create([
                'user_id'                  => $user->id,
                'notification_category_id' => $data['notification_category_id'],
                'title'                    => $data['title'],
                'body'                     => $data['body'],
                'is_read'                  => $data['is_read'],
                'read_at'                  => $data['read_at'] ?? null,
            ]);
        }
    }
}
