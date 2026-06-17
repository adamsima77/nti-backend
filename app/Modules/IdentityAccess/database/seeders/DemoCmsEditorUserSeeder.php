<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class DemoCmsEditorUserSeeder extends Seeder
{
    public const EMAIL = 'cms.editor@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $role = Role::query()->where('name', 'cms_editor')->first();

        if (! $role) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'         => 'Katarína',
                'surname'      => 'Editorová',
                'password'     => self::PASSWORD,
                'status_id'    => UserStatus::ACTIVE->value,
                'job_position' => 'CMS Editor (demo)',
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        $this->command?->newLine();
        $this->command?->info('✅ Demo CMS Editor created:');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Katarína Editorová'],
                ['Status', 'active'],
                ['Role', 'cms_editor'],
            ]
        );
    }
}
