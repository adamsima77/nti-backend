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

        DB::table('mentorship_session')->updateOrInsert(
            [
                'mentorship_id' => $mentorshipId,
                'created_by' => $userId,
                'date' => now()->addDays(1)->toDateTimeString(),
            ],
            [
                'mentorship_id' => $mentorshipId,
                'created_by' => $userId,
                'date' => now()->addDays(1)->toDateTimeString(),
                'notes' => 'Prvá mentorovacia session zo seeda.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}