<?php

namespace Modules\Mentorship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneStatusSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Plánované',
            'V riešení',
            'Dokončené',
        ];

        foreach ($items as $item) {
            DB::table('milestone_status')->updateOrInsert(
                ['name' => $item],
                [
                    'name' => $item,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}