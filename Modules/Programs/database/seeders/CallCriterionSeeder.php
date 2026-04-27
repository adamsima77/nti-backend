<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\Criterion;

class CallCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Inovatívnosť'],
            ['name' => 'Technická realizovateľnosť'],
            ['name' => 'Biznis potenciál'],
            ['name' => 'Tímová pripravenosť'],
        ];

        foreach ($items as $item) {
            Criterion::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
