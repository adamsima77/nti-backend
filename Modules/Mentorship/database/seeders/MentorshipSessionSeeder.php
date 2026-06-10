<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MentorshipSessionSeeder extends Seeder
{
    public function run(): void
    {
        $mentorshipId = DB::table('mentorship')->orderBy('id')->value('id');
        $userId = DB::table('users')->orderBy('id')->value('id');

        if ($mentorshipId === null || $userId === null) {
            return;
        }

        $scheduledAt = now()->addDays(1)->toDateTimeString();

        DB::table('mentorship_session')->updateOrInsert(
            [
                'mentorship_id' => $mentorshipId,
                'created_by' => $userId,
                'scheduled_at' => $scheduledAt,
            ],
            [
                'mentorship_id' => $mentorshipId,
                'created_by' => $userId,
                'title' => 'Úvodné mentorské stretnutie',
                'type' => 'online',
                'meeting_url' => 'https://meet.google.com/abc-defg-hij',
                'scheduled_at' => $scheduledAt,
                'agenda' => 'Prvá mentorovacia session zo seeda.',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
