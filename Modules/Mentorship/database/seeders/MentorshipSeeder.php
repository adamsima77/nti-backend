<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MentorshipSeeder extends Seeder
{
    public function run(): void
    {
        $applicationIds = DB::table('application')->orderBy('id')->pluck('id');
        $mentorUserIds = DB::table('users')->orderBy('id')->pluck('id');

        if ($applicationIds->isEmpty() || $mentorUserIds->isEmpty()) {
            return;
        }

        $pairs = [];

        foreach ($applicationIds->take(3) as $index => $applicationId) {
            $mentorUserId = $mentorUserIds[$index % $mentorUserIds->count()];

            $pairs[] = [
                'mentor_user_id' => $mentorUserId,
                'application_id' => $applicationId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($pairs as $pair) {
            DB::table('mentorship')->updateOrInsert(
                [
                    'mentor_user_id' => $pair['mentor_user_id'],
                    'application_id' => $pair['application_id'],
                ],
                $pair
            );
        }
    }
}