<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();

    TeamRole::firstOrCreate(['id' => 1], ['name' => 'Vedúci tímu']);
    TeamRole::firstOrCreate(['id' => 2], ['name' => 'Člen tímu']);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeVerifiedStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'student')->firstOrFail();
    $user->roles()->sync([$role->id]);
    Student::create(['user_id' => $user->id]);
    return $user;
}

function makeVerifiedAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'nti_admin')->firstOrFail();
    $user->roles()->sync([$role->id]);
    return $user;
}

function makeTeamWithLeaderUser(User $leader): Team
{
    $team = Team::create(['name' => 'Testovací tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);
    return $team;
}

// ─── Vytvorenie tímu ────────────────────────────────────────────────────────

test('prihlásený používateľ môže vytvoriť tím a stane sa vedúcim', function () {
    $user = makeVerifiedStudent();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/teams', [
        'name' => 'Môj nový tím',
    ]);

    $response->assertCreated()
             ->assertJsonPath('team.name', 'Môj nový tím')
             ->assertJsonPath('team.myRole', 'Vedúci tímu');

    expect(Team::where('name', 'Môj nový tím')->exists())->toBeTrue();
});

test('neprihlásený používateľ nemôže vytvoriť tím', function () {
    $this->postJson('/api/teams', ['name' => 'Tím'])->assertUnauthorized();
});

test('neoverený email nemôže vytvoriť tím', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/teams', ['name' => 'Tím'])->assertForbidden();
});

// ─── Zobrazenie tímu ────────────────────────────────────────────────────────

test('člen tímu vidí detail tímu', function () {
    $leader = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->getJson("/api/teams/{$team->id}")
         ->assertOk()
         ->assertJsonPath('team.id', $team->id);
});

test('používateľ mimo tímu nevidí detail tímu', function () {
    $leader  = makeVerifiedStudent();
    $outsider = makeVerifiedStudent();
    $team    = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($outsider, ['*']);

    $this->getJson("/api/teams/{$team->id}")->assertForbidden();
});

test('admin vidí všetky tímy', function () {
    makeTeamWithLeaderUser(makeVerifiedStudent());
    makeTeamWithLeaderUser(makeVerifiedStudent());

    $admin = makeVerifiedAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson('/api/teams')->assertOk();
    expect(count($response->json('teams')))->toBeGreaterThanOrEqual(2);
});

// ─── Úprava tímu ────────────────────────────────────────────────────────────

test('vedúci môže zmeniť názov tímu', function () {
    $leader = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->patchJson("/api/teams/{$team->id}", ['name' => 'Nový názov'])
         ->assertOk()
         ->assertJsonPath('team.name', 'Nový názov');
});

test('člen tímu nemôže zmeniť názov tímu', function () {
    $leader = makeVerifiedStudent();
    $member = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($member, ['*']);

    $this->patchJson("/api/teams/{$team->id}", ['name' => 'Pokus'])->assertForbidden();
});

// ─── Zmazanie tímu ──────────────────────────────────────────────────────────

test('vedúci môže zmazať tím', function () {
    $leader = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->deleteJson("/api/teams/{$team->id}")->assertOk();

    expect(Team::find($team->id))->toBeNull();
});

test('člen tímu nemôže zmazať tím', function () {
    $leader = makeVerifiedStudent();
    $member = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($member, ['*']);

    $this->deleteJson("/api/teams/{$team->id}")->assertForbidden();
});

// ─── Pridanie člena ─────────────────────────────────────────────────────────

test('vedúci môže pridať registrovaného študenta do tímu', function () {
    $leader  = makeVerifiedStudent();
    $newMember = makeVerifiedStudent();
    $team    = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/members", [
        'email' => $newMember->email,
    ])->assertCreated();

    expect($team->members()->where('user_id', $newMember->id)->exists())->toBeTrue();
});

test('do tímu nie je možné pridať neregistrovaného študenta', function () {
    $leader  = makeVerifiedStudent();
    $nonStudent = User::factory()->create(['email_verified_at' => now()]);
    $team    = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/members", [
        'email' => $nonStudent->email,
    ])->assertUnprocessable();
});

test('do tímu nie je možné pridať rovnakého člena dvakrát', function () {
    $leader  = makeVerifiedStudent();
    $member  = makeVerifiedStudent();
    $team    = makeTeamWithLeaderUser($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/members", [
        'email' => $member->email,
    ])->assertStatus(409);
});

// ─── Odobratie člena ────────────────────────────────────────────────────────

test('vedúci môže odobrať člena z tímu', function () {
    $leader = makeVerifiedStudent();
    $member = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($leader, ['*']);

    $this->deleteJson("/api/teams/{$team->id}/members/{$member->id}")->assertOk();

    expect($team->members()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('vedúci nemôže odobrať neexistujúceho člena', function () {
    $leader = makeVerifiedStudent();
    $team   = makeTeamWithLeaderUser($leader);
    Sanctum::actingAs($leader, ['*']);

    $outsider = makeVerifiedStudent();

    $this->deleteJson("/api/teams/{$team->id}/members/{$outsider->id}")->assertNotFound();
});
