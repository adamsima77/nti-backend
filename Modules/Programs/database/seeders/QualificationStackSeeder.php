<?php

namespace Modules\Programs\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Enums\LanguageType;
use Modules\Programs\Models\QualificationStack;

class QualificationStackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataset = [

            [
                'sk' => 'Vývoj softvéru (desktop, mobilné aplikácie, embedded systémy)',
                'en' => 'Software Development (desktop, mobile applications, embedded systems)',
            ],
            [
                'sk' => 'AI a dátové technológie (AI aplikácie, dátové technológie, strojové učenie)',
                'en' => 'AI and Data Technologies (AI applications, data technologies, machine learning)',
            ],
            [
                'sk' => 'Webové aplikácie (internetové / prehliadačové aplikácie)',
                'en' => 'Web Applications (internet / browser applications)',
            ],
            [
                'sk' => 'Herný vývoj (herné aplikácie, jazyk a platforma)',
                'en' => 'Game Development (game applications, language and platform)',
            ],
            [
                'sk' => 'IoT a embedded systémy (softvérové aj hardvérové komponenty)',
                'en' => 'IoT and Embedded Systems (software and hardware components)',
            ],


            [
                'sk' => 'Kvalifikačný stack 01: objektové technológie, úvod do softvérového inžinierstva, mobilné aplikácie, senzory, manažment projektov, testovanie',
                'en' => 'Qualification Stack 01: object technologies, introduction to software engineering, mobile applications, sensors, project management, testing',
            ],
            [
                'sk' => 'Kvalifikačný stack 02: databázové systémy, počítačová analýza dát, AI, úvod do strojového učenia, neurónové siete, hĺbková analýza dát',
                'en' => 'Qualification Stack 02: database systems, computer data analysis, AI, introduction to machine learning, neural networks, deep data analysis',
            ],
            [
                'sk' => 'Kvalifikačný stack 03: jazyky webu, FE/BE technológie, webové aplikácie na platforme Java',
                'en' => 'Qualification Stack 03: web languages, FE/BE technologies, web applications on the Java platform',
            ],
            [
                'sk' => 'Kvalifikačný stack 04: herné vývojové prostredia, vývoj 3D aplikácií, virtuálna a rozšírená realita',
                'en' => 'Qualification Stack 04: game development environments, 3D application development, virtual and augmented reality',
            ],
            [
                'sk' => 'Kvalifikačný stack 05: programovanie v jazyku C, internet vecí, inteligentné systémy, robotické a priemyselné systémy',
                'en' => 'Qualification Stack 05: C programming, internet of things, intelligent systems, robotic and industrial systems',
            ],
        ];

        foreach ($dataset as $data) {

            $stack = QualificationStack::create([]);


            $stack->translations()->create([
                'language_id' => LanguageType::SLOVAK->value,
                'name' => $data['sk'],
            ]);


            $stack->translations()->create([
                'language_id' => LanguageType::ENGLISH->value,
                'name' => $data['en'],
            ]);
        }
    }
}
