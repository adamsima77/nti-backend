<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneCommentsSeeder extends Seeder
{
    public function run(): void
    {
        $milestoneId = DB::table('milestone')->orderBy('id')->value('id');
        $userId = DB::table('users')->orderBy('id')->value('id');

        if ($milestoneId === null || $userId === null) {
            return;
        }

        DB::table('milestone_comments')->updateOrInsert(
            [
                'milestone_id' => $milestoneId,
                'user_id' => $userId,
                'parent_comment_id' => null,
            ],
            [
                'milestone_id' => $milestoneId,
                'user_id' => $userId,
                'parent_comment_id' => null,
                'comment_text' => 'Seedovaný komentár k míľniku.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}