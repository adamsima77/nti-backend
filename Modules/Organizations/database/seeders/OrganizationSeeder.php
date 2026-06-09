<?php

namespace Modules\Organizations\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\Sector;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = Sector::all();

        $organizations = [
            [
                'name' => 'Slovenská inovačná spoločnosť s.r.o.',
                'phone' => '+421905123456',
                'ico' => '36985247',
                'web_url' => 'https://slovakinnovation.sk',
                'description' => 'Výskumné centrum podporujúce digitalizáciu priemyslu a vývoj softvérových riešení.',
                'address' => [
                    'city' => 'Bratislava',
                    'street' => 'Einsteinova 22',
                    'postal_code' => '851 01',
                    'country' => 'Slovakia',
                ],
                'sector_ids' => $sectors->take(2)->pluck('id')->toArray(),
            ],
            [
                'name' => 'GreenTech Solutions a.s.',
                'phone' => '+421948123456',
                'ico' => '10293847',
                'web_url' => 'https://greentech-solutions.sk',
                'description' => 'Firma poskytujúca ekologické technológie a energetické riešenia pre moderné budovy.',
                'address' => [
                    'city' => 'Košice',
                    'street' => 'Hlavná 45',
                    'postal_code' => '040 01',
                    'country' => 'Slovakia',
                ],
                'sector_ids' => $sectors->slice(2, 2)->pluck('id')->toArray(),
            ],
            [
                'name' => 'EduFuture Consulting s.r.o.',
                'phone' => '+421903654321',
                'ico' => '56473829',
                'web_url' => 'https://edufuture.sk',
                'description' => 'Konzultačná spoločnosť zameraná na vzdelávanie, talent development a kariérny rast.',
                'address' => [
                    'city' => 'Žilina',
                    'street' => 'Námestie slobody 12',
                    'postal_code' => '010 01',
                    'country' => 'Slovakia',
                ],
                'sector_ids' => $sectors->slice(4, 2)->pluck('id')->toArray(),
            ],
        ];

        foreach ($organizations as $organizationData) {
            $address = Address::query()->updateOrCreate(
                [
                    'city' => $organizationData['address']['city'],
                    'street' => $organizationData['address']['street'],
                    'postal_code' => $organizationData['address']['postal_code'],
                ],
                ['country' => $organizationData['address']['country']]
            );

            $organization = Organization::query()->updateOrCreate(
                ['name' => $organizationData['name']],
                [
                    'phone' => $organizationData['phone'],
                    'ico' => $organizationData['ico'],
                    'web_url' => $organizationData['web_url'],
                    'description' => $organizationData['description'],
                    'address_id' => $address->id,
                ]
            );

            $organization->sectors()->sync($organizationData['sector_ids']);
        }
    }
}
