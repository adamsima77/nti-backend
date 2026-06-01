<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoEvaluatorUserSeeder extends Seeder
{
    public const EMAIL = 'evaluator@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'evaluator')->first();

        if (! $role) {
            $this->command?->error('Role `evaluator` does not exist.');
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Eva',
                'surname' => 'Komisárová',
                'password' => self::PASSWORD,
                'status_id' => UserStatus::ACTIVE->value,
                'job_position' => 'Evaluator (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        $this->command?->newLine();
        $this->command?->info('Demo evaluator created/updated:');

        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Eva Komisárová'],
                ['Status', 'active (verified email)'],
                ['Role', 'evaluator'],
            ]
        );
    }
}