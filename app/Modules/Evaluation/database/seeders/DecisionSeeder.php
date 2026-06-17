<?php

namespace Modules\Evaluation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Evaluation\Models\Decision;

class DecisionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Schválené'],
            ['name' => 'Zamietnuté'],
            ['name' => 'Podmienečne schválené'],
        ];

        foreach ($items as $item) {
            Decision::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}