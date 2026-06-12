<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\Applications\Database\Seeders\StatusOfApplicationSeeder;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\StateMachines\ApplicationStateMachine;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Database\Seeders\CallTypeSeeder;
use Modules\Programs\Database\Seeders\TypeOfProgramSeeder;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\TypeOfProgram;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function seedBase(): void
{
    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
    (new StatusOfApplicationSeeder())->run();
    (new TypeOfProgramSeeder())->run();
    (new CallTypeSeeder())->run();

    TeamRole::firstOrCreate(['id' => 1], ['name' => 'Vedúci tímu']);
    TeamRole::firstOrCreate(['id' => 2], ['name' => 'Člen tímu']);
}

function makeProgram(): Program
{
    $type = TypeOfProgram::first();
    return Program::firstOrCreate(['type_of_program_id' => $type->id]);
}

function makeOpenCall(array $overrides = []): Call
{
    $program  = makeProgram();
    $callType = CallType::first();

    return Call::create(array_merge([
        'name'                    => 'Testovacia výzva',
        'description'             => 'Popis výzvy',
        'application_start'       => now()->subDay(),
        'application_deadline'    => now()->addDays(7),
        'project_start'           => now()->addDays(10),
        'project_end'             => now()->addDays(100),
        'program_id'              => $program->id,
        'call_type_id'            => $callType->id,
        'force_closed'            => false,
        // Jeden nepovinný text field — submit prejde s ľubovoľnými dátami
        'application_form_schema' => [
            'fields' => [
                ['name' => 'popis', 'type' => 'text', 'required' => false],
            ],
        ],
    ], $overrides));
}

function makeStudentUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'student')->first();
    if ($role) {
        $user->roles()->sync([$role->id]);
    }
    return $user;
}

function makeTeamWithLeader(User $leader, int $totalMembers = 3): Team
{
    $team = Team::create(['name' => 'Testovací tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);

    for ($i = 1; $i < $totalMembers; $i++) {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $team->members()->attach($member->id, ['team_role_id' => 2]);
    }

    return $team;
}

beforeEach(function () {
    Mail::fake();
    seedBase();
});

// ─── /submit-application ────────────────────────────────────────────────────

test('neprihlásený používateľ nemôže podať prihlášku', function () {
    $call = makeOpenCall();
    $team = Team::create(['name' => 'Tím']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertUnauthorized();
});

test('team leader môže podať prihlášku do otvorenej výzvy', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall();
    $team   = makeTeamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'Naša aplikácia'],
    ])->assertOk();

    $status = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUBMITTED)->first();

    expect(
        Application::where('call_id', $call->id)
            ->where('team_id', $team->id)
            ->where('active_status', $status->id)
            ->exists()
    )->toBeTrue();
});

test('člen tímu bez role vedúceho nemôže podať prihlášku', function () {
    $member = makeStudentUser();
    $leader = makeStudentUser();
    $call   = makeOpenCall();

    $team = Team::create(['name' => 'Tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);
    $team->members()->attach($member->id, ['team_role_id' => 2]);

    Sanctum::actingAs($member, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertForbidden();
});

test('používateľ ktorý nie je členom tímu nemôže podať prihlášku', function () {
    $outsider = makeStudentUser();
    $call     = makeOpenCall();
    $team     = Team::create(['name' => 'Tím']);

    Sanctum::actingAs($outsider, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertForbidden();
});

test('do force_closed výzvy nie je možné podať prihlášku', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall(['force_closed' => true]);
    $team   = makeTeamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertUnprocessable();
});

test('do výzvy s vypršaným deadlinom nie je možné podať prihlášku', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall([
        'application_start'    => now()->subDays(10),
        'application_deadline' => now()->subDay(),
    ]);
    $team = makeTeamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertUnprocessable();
});

test('prihlášku nie je možné podať druhýkrát zo stavu Podané', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall();
    $team   = makeTeamWithLeader($leader);

    $submittedStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUBMITTED)->first();
    Application::create([
        'call_id'       => $call->id,
        'team_id'       => $team->id,
        'created_by'    => $leader->id,
        'active_status' => $submittedStatus->id,
        'last_update'   => now(),
    ]);

    Sanctum::actingAs($leader, ['*']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'test'],
    ])->assertUnprocessable();
});

// ─── /applications/draft ─────────────────────────────────────────────────

test('team leader môže uložiť draft prihlášky', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall();
    $team   = makeTeamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);

    $this->postJson('/api/applications/draft', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'rozpracovaná prihláška'],
    ])->assertOk()->assertJsonPath('draft.status.name', ApplicationStateMachine::STATE_DRAFT);
});

test('člen bez role vedúceho nemôže uložiť draft', function () {
    $member = makeStudentUser();
    $leader = makeStudentUser();
    $call   = makeOpenCall();

    $team = Team::create(['name' => 'Tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);
    $team->members()->attach($member->id, ['team_role_id' => 2]);

    Sanctum::actingAs($member, ['*']);

    $this->postJson('/api/applications/draft', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => null,
    ])->assertForbidden();
});

test('druhý draft pre rovnakú výzvu a tím nie je povolený', function () {
    $leader = makeStudentUser();
    $call   = makeOpenCall();
    $team   = makeTeamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);

    // Prvý draft
    $this->postJson('/api/applications/draft', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'prvý draft'],
    ])->assertOk();

    // Aktualizácia existujúceho draftu — musí prebehnúť (updateOrCreate)
    $this->postJson('/api/applications/draft', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => ['popis' => 'aktualizácia'],
    ])->assertOk();

    // V DB musí byť stále len jeden draft
    $draftStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_DRAFT)->first();
    expect(
        Application::where('call_id', $call->id)
            ->where('team_id', $team->id)
            ->where('active_status', $draftStatus->id)
            ->count()
    )->toBe(1);
});
