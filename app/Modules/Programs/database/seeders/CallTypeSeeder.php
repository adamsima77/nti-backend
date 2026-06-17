<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\CallType;

class CallTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => null,        'name' => 'Verejná výzva'],
            ['code' => null,        'name' => 'Firemné zadanie'],
            ['code' => 'program_a', 'name' => 'Program A'],
            ['code' => 'program_b', 'name' => 'Program B'],
        ];

        foreach ($items as $item) {
            CallType::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
