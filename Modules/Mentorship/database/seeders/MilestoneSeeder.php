<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneSeeder extends Seeder
{
    public function run(): void
    {
        $callIds = DB::table('call')->orderBy('id')->pluck('id');
        $statusId = DB::table('milestone_status')->where('name', 'Plánované')->value('id');

        if ($callIds->isEmpty() || $statusId === null) {
            return;
        }

        foreach ($callIds->take(2) as $callId) {
            $milestoneId = DB::table('milestone')->updateOrInsert(
                [
                    'call_id' => $callId,
                    'name' => 'Kickoff a zber podkladov',
                ],
                [
                    'call_id' => $callId,
                    'name' => 'Kickoff a zber podkladov',
                    'description' => 'Úvodný míľnik pre nastavenie spolupráce a zber vstupov.',
                    'due_date' => now()->addWeeks(2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $createdMilestoneId = DB::table('milestone')
                ->where('call_id', $callId)
                ->where('name', 'Kickoff a zber podkladov')
                ->value('id');

            if ($createdMilestoneId !== null) {
                DB::table('milestone_has_milestone_status')->updateOrInsert(
                    [
                        'milestone_id' => $createdMilestoneId,
                        'milestone_status_id' => $statusId,
                    ],
                    [
                        'note' => 'Počiatočný stav po seede.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}