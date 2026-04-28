<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentHasMilestoneSeeder extends Seeder
{
    public function run(): void
    {
        $documentIds = DB::table('document')->orderBy('id')->pluck('id');
        $milestoneIds = DB::table('milestone')->orderBy('id')->pluck('id');

        if ($documentIds->isEmpty() || $milestoneIds->isEmpty()) {
            return;
        }

        DB::table('document_has_milestone')->updateOrInsert(
            [
                'document_id' => $documentIds[0],
                'milestone_id' => $milestoneIds[0],
            ],
            [
                'document_id' => $documentIds[0],
                'milestone_id' => $milestoneIds[0],
            ]
        );
    }
}