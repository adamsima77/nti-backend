<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoNtiAdminUserSeeder extends Seeder
{
    private array $users = [
        [
            'email'        => 'admin@test.nti.local',
            'name'         => 'Vladimír',
            'surname'      => 'Administrátor',
            'job_position' => 'NTI Admin (demo)',
            'role'         => 'nti_admin',
        ],
        [
            'email'        => 'superadmin@test.nti.local',
            'name'         => 'Ľubomír',
            'surname'      => 'Superadministrátor',
            'job_position' => 'NTI Super Admin (demo)',
            'role'         => 'nti_superadmin',
        ],
    ];

    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        foreach ($this->users as $data) {
            $role = Role::query()->where('name', $data['role'])->first();

            if (! $role) {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['name'],
                    'surname'      => $data['surname'],
                    'password'     => self::PASSWORD,
                    'status_id'    => UserStatus::ACTIVE->value,
                    'job_position' => $data['job_position'],
                ]
            );

            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            $user->roles()->sync([$role->id]);
        }

        $this->command?->newLine();
        $this->command?->info('✅ Demo admin users created:');
        $this->command?->table(
            ['Email', 'Name', 'Role'],
            collect($this->users)->map(fn ($u) => [
                $u['email'],
                "{$u['name']} {$u['surname']}",
                $u['role'],
            ])->toArray()
        );
    }
}
