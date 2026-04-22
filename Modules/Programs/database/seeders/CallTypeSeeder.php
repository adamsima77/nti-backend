<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\CallType;

class CallTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Verejná výzva'],
            ['name' => 'Firemné zadanie'],
        ];

        foreach ($items as $item) {
            CallType::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
