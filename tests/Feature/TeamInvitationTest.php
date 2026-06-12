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
use Modules\Teams\Models\TeamInvitation;
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

function invStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    Student::create(['user_id' => $user->id]);
    return $user;
}

function invTeamWithLeader(User $leader): Team
{
    $team = Team::create(['name' => 'Tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);
    return $team;
}

function createInvitation(Team $team, User $invitedBy, string $email, int $daysValid = 14): TeamInvitation
{
    return TeamInvitation::create([
        'team_id'      => $team->id,
        'email'        => $email,
        'token'        => \Illuminate\Support\Str::random(64),
        'team_role_id' => 2,
        'invited_by'   => $invitedBy->id,
        'expires_at'   => now()->addDays($daysValid),
    ]);
}

// ─── POST /api/teams/{id}/invite ────────────────────────────────────────────

test('vedúci môže pozvať registrovaného študenta', function () {
    $leader  = invStudent();
    $invited = invStudent();
    $team    = invTeamWithLeader($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => $invited->email,
    ])->assertCreated()
      ->assertJsonStructure(['invitation' => ['id', 'email', 'expires_at']]);

    expect(TeamInvitation::where('team_id', $team->id)->where('email', $invited->email)->exists())->toBeTrue();
    // Email môže byť odoslaný synchrónne alebo cez queue
    $sent   = Mail::sent(\Modules\Notifications\Emails\TeamInviteMail::class)->count();
    $queued = Mail::queued(\Modules\Notifications\Emails\TeamInviteMail::class)->count();
    expect($sent + $queued)->toBeGreaterThan(0);
});

test('vedúci nemôže pozvať neregistrovaného používateľa', function () {
    $leader     = invStudent();
    $nonStudent = User::factory()->create(['email_verified_at' => now()]);
    $team       = invTeamWithLeader($leader);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => $nonStudent->email,
    ])->assertUnprocessable();
});

test('vedúci nemôže pozvať existujúceho člena tímu', function () {
    $leader  = invStudent();
    $member  = invStudent();
    $team    = invTeamWithLeader($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($leader, ['*']);

    $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => $member->email,
    ])->assertStatus(409);
});

test('člen tímu nemôže posielať pozvánky', function () {
    $leader = invStudent();
    $member = invStudent();
    $target = invStudent();
    $team   = invTeamWithLeader($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);
    Sanctum::actingAs($member, ['*']);

    $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => $target->email,
    ])->assertForbidden();
});

// ─── POST /api/teams/invitations/accept ─────────────────────────────────────

test('pozvaný študent môže prijať pozvánku a stane sa členom', function () {
    $leader  = invStudent();
    $invited = invStudent();
    $team    = invTeamWithLeader($leader);
    $inv     = createInvitation($team, $leader, $invited->email);
    Sanctum::actingAs($invited, ['*']);

    $this->postJson('/api/teams/invitations/accept', [
        'token' => $inv->token,
    ])->assertOk();

    expect($team->members()->where('user_id', $invited->id)->exists())->toBeTrue();
    expect(TeamInvitation::find($inv->id)->accepted_at)->not->toBeNull();
});

test('expirovaná pozvánka vráti 410', function () {
    $leader  = invStudent();
    $invited = invStudent();
    $team    = invTeamWithLeader($leader);
    $inv     = createInvitation($team, $leader, $invited->email, -1); // expirovaná
    Sanctum::actingAs($invited, ['*']);

    $this->postJson('/api/teams/invitations/accept', [
        'token' => $inv->token,
    ])->assertStatus(410);
});

test('pozvánku nemôže prijať iný používateľ ako adresát', function () {
    $leader   = invStudent();
    $invited  = invStudent();
    $impostor = invStudent();
    $team     = invTeamWithLeader($leader);
    $inv      = createInvitation($team, $leader, $invited->email);
    Sanctum::actingAs($impostor, ['*']);

    $this->postJson('/api/teams/invitations/accept', [
        'token' => $inv->token,
    ])->assertForbidden();
});

test('neplatný token vráti 410', function () {
    $student = invStudent();
    Sanctum::actingAs($student, ['*']);

    $this->postJson('/api/teams/invitations/accept', [
        'token' => 'neplatny-token-xyz',
    ])->assertStatus(410);
});
