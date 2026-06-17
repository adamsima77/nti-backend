<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\University;

class UniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universities = [
            'Slovenská poľnohospodárska univerzita v Nitre',
            'Univerzita Konštantína Filozofa v Nitre',
            'Technická univerzita v Košiciach',
            'Slovenská technická univerzita v Bratislave',
            'Žilinská univerzita v Žiline',
            'Univerzita Mateja Bela v Banskej Bystrici',
            'Prešovská univerzita v Prešove',
            'Univerzita Pavla Jozefa Šafárika v Košiciach',
        ];

        foreach ($universities as $university) {
            University::firstOrCreate(['name' => $university]);
        }
    }
}
