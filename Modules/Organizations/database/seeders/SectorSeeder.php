<?php

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organizations\Models\Sector;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            'Informačné technológie',
            'Strojárstvo',
            'Elektrotechnika',
            'Stavebníctvo',
            'Financie a bankovníctvo',
            'Zdravotníctvo',
            'Vzdelávanie',
            'Poľnohospodárstvo',
            'Obchod a marketing',
            'Doprava a logistika',
        ];

        foreach ($sectors as $sector) {
            Sector::firstOrCreate(['name' => $sector]);
        }
    }
}
