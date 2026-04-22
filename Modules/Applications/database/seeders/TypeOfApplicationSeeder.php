<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\TypeOfApplication;

class TypeOfApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Executive Summary'],
            ['name' => 'Technická architektúra'],
            ['name' => 'Roadmapa'],
            ['name' => 'Rozpočet'],
            ['name' => 'Riziková analýza'],
            ['name' => 'Monetizačný model'],
        ];

        foreach ($items as $item) {
            TypeOfApplication::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
