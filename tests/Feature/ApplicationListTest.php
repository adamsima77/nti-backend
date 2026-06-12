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

beforeEach(function () {
    Mail::fake();

    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
    (new StatusOfApplicationSeeder())->run();
    (new TypeOfProgramSeeder())->run();
    (new CallTypeSeeder())->run();

    TeamRole::firstOrCreate(['id' => 1], ['name' => 'Vedúci tímu']);
    TeamRole::firstOrCreate(['id' => 2], ['name' => 'Člen tímu']);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function appListAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'nti_admin')->firstOrFail()->id]);
    return $user;
}

function appListStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    return $user;
}

function makeAppForTeam(User $leader, string $stateName = ApplicationStateMachine::STATE_SUBMITTED): Application
{
    $type    = TypeOfProgram::first();
    $program = Program::firstOrCreate(['type_of_program_id' => $type->id]);
    $call    = Call::create([
        'name'                 => 'Výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => CallType::first()->id,
        'force_closed'         => false,
        'application_form_schema' => ['fields' => []],
    ]);

    $team   = Team::create(['name' => 'Tím']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);

    $status = StatusOfApplication::where('name', $stateName)->firstOrFail();

    return Application::create([
        'call_id'       => $call->id,
        'team_id'       => $team->id,
        'created_by'    => $leader->id,
        'active_status' => $status->id,
        'last_update'   => now(),
    ]);
}

// ─── GET /api/applications ───────────────────────────────────────────────────

test('student vidí len prihlášky svojho tímu', function () {
    $studentA = appListStudent();
    $studentB = appListStudent();

    makeAppForTeam($studentA);
    makeAppForTeam($studentB);

    Sanctum::actingAs($studentA, ['*']);

    $response = $this->getJson('/api/applications')->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    // Len 1 prihláška — vlastná
    expect($ids)->toHaveCount(1);
});

test('admin vidí všetky prihlášky', function () {
    $studentA = appListStudent();
    $studentB = appListStudent();

    makeAppForTeam($studentA);
    makeAppForTeam($studentB);

    $admin = appListAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson('/api/applications')->assertOk();
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
});

test('neprihlásený nemôže pristupovať na zoznam prihlášok', function () {
    $this->getJson('/api/applications')->assertUnauthorized();
});

test('neoverený email nemôže pristupovať na zoznam prihlášok', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/applications')->assertForbidden();
});

// ─── GET /api/admin/applications ────────────────────────────────────────────

test('admin vidí zoznam prihlášok na admin endpointe bez draftov', function () {
    $student = appListStudent();
    makeAppForTeam($student, ApplicationStateMachine::STATE_SUBMITTED);
    makeAppForTeam($student, ApplicationStateMachine::STATE_DRAFT);

    $admin = appListAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson('/api/admin/applications')->assertOk();
    $names = collect($response->json('data'))->pluck('status.name');

    // Draft (active_status=1) je vylúčený z admin endpointu
    expect($names)->not->toContain(ApplicationStateMachine::STATE_DRAFT);
});

test('student dostane prázdny zoznam na admin endpointe (bez draftov svojich prihlášok)', function () {
    $student = appListStudent();
    makeAppForTeam($student, ApplicationStateMachine::STATE_DRAFT);
    Sanctum::actingAs($student, ['*']);

    // fetchForAdmin vylučuje draft (active_status != 1) a zobrazuje všetkým overeným
    $response = $this->getJson('/api/admin/applications')->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

// ─── Filtrovanie ─────────────────────────────────────────────────────────────

test('admin môže filtrovať prihlášky podľa stavu', function () {
    $studentA = appListStudent();
    $studentB = appListStudent();

    makeAppForTeam($studentA, ApplicationStateMachine::STATE_SUBMITTED);
    makeAppForTeam($studentB, ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED);

    $admin  = appListAdmin();
    $status = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUBMITTED)->firstOrFail();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson("/api/applications?status_id={$status->id}")->assertOk();
    $statuses = collect($response->json('data'))->pluck('status.name')->unique()->values();

    expect($statuses->all())->toBe([ApplicationStateMachine::STATE_SUBMITTED]);
});
