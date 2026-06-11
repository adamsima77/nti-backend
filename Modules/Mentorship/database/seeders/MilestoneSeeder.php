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
            DB::table('project_milestones')->updateOrInsert(
                [
                    'call_id' => $callId,
                    'name'    => 'Kickoff a zber podkladov',
                ],
                [
                    'call_id'             => $callId,
                    'name'                => 'Kickoff a zber podkladov',
                    'start_date' => now(),
                    'deadline'            => now()->addWeeks(2),
                    'milestone_status_id' => $statusId,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }
    }
}
