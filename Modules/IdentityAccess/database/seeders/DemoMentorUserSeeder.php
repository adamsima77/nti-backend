<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoMentorUserSeeder extends Seeder
{
    public const EMAIL = 'mentor@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'mentor')->first();

        if (! $role) {
            $this->command?->error('Role `mentor` does not exist.');
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'         => 'Matej',
                'surname'      => 'Novotný',
                'password'     => self::PASSWORD, // hashed via User cast
                'status_id'    => UserStatus::ACTIVE->value,
                'job_position' => 'Mentor (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        $this->command?->newLine();
        $this->command?->info('Demo mentor created/updated:');

        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Matej Novotný'],
                ['Status', 'active (verified email)'],
                ['Role', 'mentor'],
            ]
        );
    }
}

