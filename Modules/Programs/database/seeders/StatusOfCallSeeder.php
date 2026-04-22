<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\StatusOfCall;

class StatusOfCallSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Draft'],
            ['name' => 'Publikované'],
            ['name' => 'Zatvorené'],
        ];

        foreach ($items as $item) {
            StatusOfCall::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
