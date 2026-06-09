<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\Organization;

class DemoPartnerUserSeeder extends Seeder
{
    public const EMAIL = 'partner@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'partner')->first();

        if (! $role) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'         => 'Róbert',
                'surname'      => 'Partnér',
                'password'     => self::PASSWORD,
                'status_id'    => UserStatus::ACTIVE->value,
                'job_position' => 'Business Development Manager (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        $organization = Organization::query()->updateOrCreate(
            ['ico' => '12345678'],
            [
                'name'               => 'Acme Technology Solutions s.r.o.',
                'email'              => 'company@acme-tech.local',
                'phone'              => '+421 2 123 45 67',
                'address'            => 'Ul. Inovácií 123, 949 01 Nitra',
                'sector'             => 'IT / Vývoj softvéru',
                'employee_count'     => 50,
                'description'        => 'Vedúca technologická spoločnosť špecializujúca sa na vývoj custom softvéru a AI riešení.',
                'website'            => 'https://acme-tech.example.com',
                'status'             => 'active',
            ]
        );

        $organization->users()->syncWithoutDetaching([$user->id]);

        $this->command?->newLine();
        $this->command?->info('✅ Demo partner user and organization created:');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Name', 'Róbert Partnér'],
                ['Organization', 'Acme Technology Solutions s.r.o.'],
                ['Role', 'partner'],
            ]
        );
    }
}
