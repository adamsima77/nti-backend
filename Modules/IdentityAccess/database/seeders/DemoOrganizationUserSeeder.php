<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationRole;

class DemoOrganizationUserSeeder extends Seeder
{
    public const EMAIL = 'organization@test.nti.local';
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        // 1. Find the "organization" system role
        $role = Role::query()->where('name', 'organization')->first();
        if (! $role) {
            $this->command?->warn('Role "organization" not found. Skipping.');
            return;
        }

        // 2. Create or update the user (only columns that exist)
        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'      => 'Adriana',
                'surname'   => 'Orgánová',
                'password'  => bcrypt(self::PASSWORD),
                'status_id' => UserStatus::ACTIVE->value,
            ]
        );
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $user->roles()->sync([$role->id]);

        // 3. Create address
        $address = Address::query()->firstOrCreate(
            ['street' => 'Štúrova 10', 'city' => 'Nitra'],
            [
                'street'      => 'Štúrova 10',
                'city'        => 'Nitra',
                'postal_code' => '949 01',
                'country'     => 'Slovakia',
            ]
        );

        // 4. Create organization (no description column)
        $organization = Organization::query()->updateOrCreate(
            ['ico' => '87654321'],
            [
                'name'       => 'Nitra Digital Solutions s.r.o.',
                'phone'      => '+421 37 111 22 33',
                'web_url'    => 'https://nitra-digital.local',
                'address_id' => $address->id,
            ]
        );

        // 5. Ensure the organization_role 'org_admin' exists
        $orgRole = OrganizationRole::query()->firstOrCreate(
            ['name' => 'org_admin'],
            ['name' => 'org_admin']
        );

        // 6. Attach user to organization with the correct pivot column name
        //    The column is named 'organization_role' (not 'organization_role_id')
        $organization->users()->syncWithoutDetaching([
            $user->id => ['organization_role' => $orgRole->id],
        ]);

        // 7. Output success
        $this->command?->newLine();
        $this->command?->info('✅ Demo organization user created:');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Email', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Name', 'Adriana Orgánová'],
                ['Organization', 'Nitra Digital Solutions s.r.o.'],
                ['Role', 'organization'],
                ['Org Role', $orgRole->name],
            ]
        );
    }
}