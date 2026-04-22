<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Programs\Models\CallCriterion;

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
            CallCriterion::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
