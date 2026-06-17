<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoEvaluatorUserSeeder extends Seeder
{
    public const EMAIL_1 = 'evaluator1@test.nti.local';
    public const EMAIL_2 = 'evaluator2@test.nti.local';
    public const PASSWORD = 'Password123!';

    private array $users = [
        [
            'email'        => self::EMAIL_1,
            'name'         => 'Eva',
            'surname'      => 'Komisárová',
            'job_position' => 'Evaluátor (demo)',
        ],
        [
            'email'        => self::EMAIL_2,
            'name'         => 'Marek',
            'surname'      => 'Hodnotiteľ',
            'job_position' => 'Evaluátor (demo)',
        ],
    ];

    public function run(): void
    {
        $role = Role::query()->where('name', 'evaluator')->first();

        if (! $role) {
            $this->command?->error('Role `evaluator` does not exist.');
            return;
        }

        foreach ($this->users as $data) {
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
        $this->command?->info('Demo evaluators created/updated:');
        $this->command?->table(
            ['Email', 'Name', 'Role'],
            collect($this->users)->map(fn ($u) => [
                $u['email'],
                "{$u['name']} {$u['surname']}",
                'evaluator',
            ])->toArray()
        );
    }
}
