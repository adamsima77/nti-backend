<?php

namespace Modules\Applications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Applications\Models\StatusOfApplication;

class StatusOfApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Draft'],
            ['name' => 'Podané'],
            ['name' => 'V hodnotení'],
            ['name' => 'Vyžiadané doplnenie'],
            ['name' => 'Schválené'],
            ['name' => 'Zamietnuté'],
        ];

        foreach ($items as $item) {
            StatusOfApplication::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
