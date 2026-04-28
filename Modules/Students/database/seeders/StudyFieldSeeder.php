<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\StudyField;

class StudyFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            'Informatika',
            'Kybernetika',
            'Elektrotechnika',
            'Ekonómia a manažment',
            'Obchod a marketing',
            'Dizajn',
        ];

        foreach ($fields as $field) {
            StudyField::firstOrCreate(['name' => $field]);
        }
    }
}
