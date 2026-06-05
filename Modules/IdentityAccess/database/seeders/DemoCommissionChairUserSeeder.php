<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoCommissionChairUserSeeder extends Seeder
{
    public const EMAIL = 'predseda@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'predseda_komisie')->first();

        if (! $role) {
            $this->command?->error('Role `predseda_komisie` does not exist.');
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Peter',
                'surname' => 'Predseda',
                'password' => self::PASSWORD,
                'status_id' => UserStatus::ACTIVE->value,
                'job_position' => 'Predseda komisie (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        $this->command?->newLine();
        $this->command?->info('Demo commission chair created/updated:');

        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Peter Predseda'],
                ['Status', 'active (verified email)'],
                ['Role', 'predseda_komisie'],
            ]
        );
    }
}
