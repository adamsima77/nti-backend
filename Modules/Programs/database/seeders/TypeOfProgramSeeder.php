<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\TypeOfProgram;

class TypeOfProgramSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Program A'],
            ['name' => 'Program B'],
        ];

        foreach ($items as $item) {
            TypeOfProgram::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
