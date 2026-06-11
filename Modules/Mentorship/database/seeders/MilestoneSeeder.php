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

        foreach ($callIds->take(3) as $callId) {
            $milestones = [
                'Úvodná analýza a wireframy',
                'MVP funkčné jadro backendu',
                'Finálna akceptácia a nasadenie'
            ];

            foreach ($milestones as $index => $name) {
                DB::table('project_milestones')->updateOrInsert(
                    [
                        'call_id' => $callId,
                        'name'    => $name,
                    ],
                    [
                        'call_id'             => $callId,
                        'name'                => $name,
                        'deadline'            => now()->addWeeks(($index + 1) * 2),
                        'milestone_status_id' => $statusId,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]
                );
            }
        }
    }
}
