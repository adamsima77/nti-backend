<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class IdentityAccessPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(StatusSeeder::class);
    }

    public function test_student_role_has_expected_permissions(): void
    {
        $role = Role::query()->where('name', 'student')->firstOrFail();

        $permissions = $role->permissions()->pluck('name')->all();

        $this->assertContains('students.profile.view_own', $permissions);
        $this->assertContains('applications.create', $permissions);
        $this->assertContains('teams.view_own', $permissions);
        $this->assertContains('content.view', $permissions);
    }

    public function test_organization_role_has_expected_permissions(): void
    {
        $role = Role::query()->where('name', 'organization')->firstOrFail();

        $permissions = $role->permissions()->pluck('name')->all();

        $this->assertContains('organizations.view', $permissions);
        $this->assertContains('organizations.edit_own', $permissions);
        $this->assertContains('applications.view_own', $permissions);
        $this->assertContains('content.view', $permissions);
    }

    public function test_evaluator_role_has_expected_permissions(): void
    {
        $role = Role::query()->where('name', 'evaluator')->firstOrFail();

        $permissions = $role->permissions()->pluck('name')->all();

        $this->assertContains('evaluation.view_any', $permissions);
        $this->assertContains('evaluation.create', $permissions);
        $this->assertContains('evaluation.submit', $permissions);
        $this->assertContains('evaluation.export', $permissions);
    }

    public function test_content_manager_role_has_expected_permissions(): void
    {
        $role = Role::query()->where('name', 'content-manager')->firstOrFail();

        $permissions = $role->permissions()->pluck('name')->all();

        $this->assertContains('content.view', $permissions);
        $this->assertContains('content.create', $permissions);
        $this->assertContains('content.edit_any', $permissions);
        $this->assertContains('content.publish', $permissions);
    }

    public function test_auth_me_returns_permissions_for_organization_role(): void
    {
        $user = User::factory()->create();
        $organizationRole = Role::query()->where('name', 'organization')->firstOrFail();
        $user->roles()->sync([$organizationRole->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('roles.0.name', 'organization');

        $permissions = collect($response->json('roles.0.permissions'))->pluck('name')->all();

        $this->assertContains('organizations.view', $permissions);
        $this->assertContains('organizations.edit_own', $permissions);
    }

    public function test_user_can_anonymize_their_own_account(): void
    {
        $user = User::factory()->create([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@test.local',
        ]);

        $studentRole = Role::query()->where('name', 'student')->firstOrFail();
        $user->roles()->sync([$studentRole->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/users/anonymize-user/{$user->id}");

        $response->assertOk();

        $user->refresh();

        $this->assertSame('Anonymized', $user->name);
        $this->assertSame('User', $user->surname);
        $this->assertSame("anonymized{$user->id}@nti.com", $user->email);
        $this->assertNotNull($user->anonymized_at);
        $this->assertCount(0, $user->roles()->get());
    }
}
