<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\Applications\Database\Seeders\StatusOfApplicationSeeder;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationStatusHistory;
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
use Modules\Students\Models\Student;
use Modules\Evaluation\Models\Evaluation;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
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

function appAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'nti_admin')->firstOrFail()->id]);
    return $user;
}

function appStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    Student::create(['user_id' => $user->id]);
    return $user;
}

function openCall(): Call
{
    // Program A must be id=1; Program B id=2 for machineFor() logic
    $typeA = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    Program::firstOrCreate(['type_of_program_id' => $typeA->id]);
    $typeB = TypeOfProgram::where('name', 'Program B')->firstOrFail();
    $program = Program::firstOrCreate(['type_of_program_id' => $typeB->id]);

    return Call::create([
        'name'                    => 'Otvorená výzva',
        'description'             => 'Popis',
        'application_start'       => now()->subDay(),
        'application_deadline'    => now()->addDays(7),
        'project_start'           => now()->addDays(10),
        'project_end'             => now()->addDays(100),
        'program_id'              => $program->id,
        'call_type_id'            => CallType::first()->id,
        'force_closed'            => false,
        // Prázdna schéma bez povinných polí — checkAnswerOfApplicationAnswer prejde
        'application_form_schema' => ['fields' => [['name' => 'info', 'type' => 'text', 'required' => false]]],
    ]);
}

function closedCall(): Call
{
    $typeA = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    Program::firstOrCreate(['type_of_program_id' => $typeA->id]);
    $typeB = TypeOfProgram::where('name', 'Program B')->firstOrFail();
    $program = Program::firstOrCreate(['type_of_program_id' => $typeB->id]);

    return Call::create([
        'name'                 => 'Uzavretá výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDays(10),
        'application_deadline' => now()->subDay(), // deadline prešiel
        'project_start'        => now()->addDays(5),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => CallType::first()->id,
        'force_closed'         => false,
    ]);
}

function teamWithLeader(User $leader): Team
{
    $team = Team::create(['name' => 'Tím prihlášky']);
    $team->members()->attach($leader->id, ['team_role_id' => 1]);
    return $team;
}

function draftApplication(Call $call, Team $team, User $creator): Application
{
    $draftStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_DRAFT)->firstOrFail();
    return Application::create([
        'call_id'       => $call->id,
        'team_id'       => $team->id,
        'created_by'    => $creator->id,
        'active_status' => $draftStatus->id,
        'last_update'   => now(),
    ]);
}

function submittedApplication(Call $call, Team $team, User $creator): Application
{
    $app = draftApplication($call, $team, $creator);
    $submittedStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUBMITTED)->firstOrFail();
    $app->update(['active_status' => $submittedStatus->id]);
    ApplicationStatusHistory::create([
        'application_id'        => $app->id,
        'status_of_application_id' => $submittedStatus->id,
        'note'                  => null,
    ]);
    return $app->fresh();
}

// ─── GET /api/applications ───────────────────────────────────────────────────

test('neprihlásený nemôže vidieť prihlášky', function () {
    $this->getJson('/api/applications')->assertUnauthorized();
});

test('student vidí len vlastné prihlášky', function () {
    $leader  = appStudent();
    $other   = appStudent();
    $call    = openCall();
    $myTeam  = teamWithLeader($leader);
    $hisTeam = teamWithLeader($other);

    draftApplication($call, $myTeam, $leader);
    draftApplication($call, $hisTeam, $other);

    Sanctum::actingAs($leader, ['*']);
    $response = $this->getJson('/api/applications')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toHaveCount(1);
});

test('admin vidí všetky prihlášky', function () {
    $leader = appStudent();
    $other  = appStudent();
    $call   = openCall();

    draftApplication($call, teamWithLeader($leader), $leader);
    draftApplication($call, teamWithLeader($other), $other);

    $admin = appAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson('/api/applications')->assertOk();
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

// ─── GET /api/applications/{id} ─────────────────────────────────────────────

test('tvorca prihlášky vidí jej detail', function () {
    $leader = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);
    $app    = draftApplication($call, $team, $leader);

    Sanctum::actingAs($leader, ['*']);
    $this->getJson("/api/applications/{$app->id}")
         ->assertOk()
         ->assertJsonPath('application.id', $app->id);
});

test('outsider nemôže vidieť cudziu prihlášku', function () {
    $leader  = appStudent();
    $outsider = appStudent();
    $call    = openCall();
    $team    = teamWithLeader($leader);
    $app     = draftApplication($call, $team, $leader);

    Sanctum::actingAs($outsider, ['*']);
    $this->getJson("/api/applications/{$app->id}")->assertForbidden();
});

test('admin vidí detail akejkoľvek prihlášky', function () {
    $leader = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);
    $app    = draftApplication($call, $team, $leader);

    $admin = appAdmin();
    Sanctum::actingAs($admin, ['*']);
    $this->getJson("/api/applications/{$app->id}")->assertOk();
});

// ─── POST /applications/draft ────────────────────────────────────────────────

test('vedúci tímu môže uložiť koncept prihlášky', function () {
    $leader = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);
    $this->postJson('/api/applications/draft', [
        'call_id' => $call->id,
        'team_id' => $team->id,
    ])->assertOk();

    expect(Application::where('call_id', $call->id)->where('team_id', $team->id)->exists())->toBeTrue();
});

test('člen tímu (nie vedúci) nemôže uložiť koncept', function () {
    $leader = appStudent();
    $member = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);
    $team->members()->attach($member->id, ['team_role_id' => 2]);

    Sanctum::actingAs($member, ['*']);
    $this->postJson('/api/applications/draft', [
        'call_id' => $call->id,
        'team_id' => $team->id,
    ])->assertForbidden();
});

test('koncept pre uzavretú výzvu vráti 422', function () {
    $leader = appStudent();
    $call   = closedCall();
    $team   = teamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);
    $this->postJson('/api/applications/draft', [
        'call_id' => $call->id,
        'team_id' => $team->id,
    ])->assertUnprocessable();
});

// ─── POST /submit-application ────────────────────────────────────────────────

test('neprihlásený nemôže odoslať prihlášku', function () {
    $call = openCall();
    $team = Team::create(['name' => 'T']);

    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => [],
    ])->assertUnauthorized();
});

test('odoslanie pre uzavretú výzvu vráti 422', function () {
    $leader = appStudent();
    $call   = closedCall();
    $team   = teamWithLeader($leader);

    Sanctum::actingAs($leader, ['*']);
    $this->postJson('/api/submit-application', [
        'call_id'   => $call->id,
        'team_id'   => $team->id,
        'form_data' => [],
    ])->assertUnprocessable();
});

// ─── POST /change-app-state/{application}/admin ──────────────────────────────

test('admin môže zmeniť stav prihlášky (Podané → Vyžiadané doplnenie)', function () {
    $leader = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);
    $app    = submittedApplication($call, $team, $leader);

    // Supplement Requested nevyžaduje žiadne ďalšie polia
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED)->firstOrFail();

    $admin = appAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$app->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertOk();

    expect($app->fresh()->active_status)->toBe($targetStatus->id);
});

test('student nemôže zmeniť stav prihlášky', function () {
    $leader   = appStudent();
    $outsider = appStudent();
    $call     = openCall();
    $team     = teamWithLeader($leader);
    $app      = submittedApplication($call, $team, $leader);

    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_IN_EVALUATION)->firstOrFail();

    Sanctum::actingAs($outsider, ['*']);
    $this->postJson("/api/change-app-state/{$app->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertForbidden();
});

test('neplatný stavový prechod vráti 403', function () {
    $leader = appStudent();
    $call   = openCall();
    $team   = teamWithLeader($leader);
    $app    = draftApplication($call, $team, $leader); // Draft

    // Draft → Vyžiadané doplnenie je neplatný prechod (musí cez Podané)
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED)->firstOrFail();

    $admin = appAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$app->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertForbidden();
});
