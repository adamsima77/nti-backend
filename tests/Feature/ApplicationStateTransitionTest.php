<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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

function makeAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'nti_admin')->first();
    if ($role) {
        $user->roles()->sync([$role->id]);
    }
    return $user;
}

function makeApplicationInState(string $stateName, ?User $creator = null): Application
{
    $program  = Program::firstOrCreate(['type_of_program_id' => TypeOfProgram::first()->id]);
    $callType = CallType::first();

    $call = Call::create([
        'name'                    => 'Výzva',
        'description'             => 'Popis',
        'application_start'       => now()->subDay(),
        'application_deadline'    => now()->addDays(7),
        'project_start'           => now()->addDays(10),
        'project_end'             => now()->addDays(100),
        'program_id'              => $program->id,
        'call_type_id'            => $callType->id,
        'force_closed'            => false,
        'application_form_schema' => ['fields' => []],
    ]);

    $team    = Team::create(['name' => 'Tím']);
    $creator = $creator ?? User::factory()->create(['email_verified_at' => now()]);
    $status  = StatusOfApplication::where('name', $stateName)->firstOrFail();

    return Application::create([
        'call_id'       => $call->id,
        'team_id'       => $team->id,
        'created_by'    => $creator->id,
        'active_status' => $status->id,
        'last_update'   => now(),
    ]);
}

// ─── Admin zmena stavu ───────────────────────────────────────────────────────

test('admin môže zmeniť stav z Podané na Vyžiadané doplnenie', function () {
    $admin        = makeAdmin();
    $application  = makeApplicationInState(ApplicationStateMachine::STATE_SUBMITTED, $admin);
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED)->first();

    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$application->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertOk();

    expect($application->fresh()->active_status)->toBe($targetStatus->id);
});

test('admin nemôže zmeniť stav na nepovolený prechod', function () {
    $admin       = makeAdmin();
    $application = makeApplicationInState(ApplicationStateMachine::STATE_DRAFT);
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_APPROVED)->first();

    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$application->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertForbidden();
});

test('admin nemôže nastaviť rovnaký stav znovu', function () {
    $admin       = makeAdmin();
    $application = makeApplicationInState(ApplicationStateMachine::STATE_SUBMITTED);
    $sameStatus  = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUBMITTED)->first();

    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$application->id}/admin", [
        'state_id' => $sameStatus->id,
    ])->assertUnprocessable();
});

test('neprihlásený používateľ nemôže zmeniť stav', function () {
    $application  = makeApplicationInState(ApplicationStateMachine::STATE_SUBMITTED);
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_IN_EVALUATION)->first();

    $this->postJson("/api/change-app-state/{$application->id}/admin", [
        'state_id' => $targetStatus->id,
    ])->assertUnauthorized();
});

// ─── Audit trail ─────────────────────────────────────────────────────────────

test('zmena stavu vytvára záznam v histórii', function () {
    $admin        = makeAdmin();
    $application  = makeApplicationInState(ApplicationStateMachine::STATE_SUBMITTED, $admin);
    $targetStatus = StatusOfApplication::where('name', ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED)->first();

    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/change-app-state/{$application->id}/admin", [
        'state_id' => $targetStatus->id,
        'note'     => 'Odosielam do hodnotenia',
    ])->assertOk();

    expect(
        ApplicationStatusHistory::where('application_id', $application->id)
            ->where('status_of_application_id', $targetStatus->id)
            ->exists()
    )->toBeTrue();
});

// ─── ApplicationStateMachine integrácia s DB ──────────────────────────────

test('transitionTo uloží nový stav a vytvorí históriu', function () {
    $admin       = makeAdmin();
    $application = makeApplicationInState(ApplicationStateMachine::STATE_SUBMITTED, $admin);

    $sm = new ApplicationStateMachine($application, $admin);
    // Prechod Podané → Vyžiadané doplnenie nevyžaduje žiadne extra polia
    $sm->transitionTo(ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED, 'Doplňte prosím');

    $application->refresh()->load('status');

    expect($application->status->name)->toBe(ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED);
    expect(
        ApplicationStatusHistory::where('application_id', $application->id)->count()
    )->toBeGreaterThanOrEqual(1);
});

test('transitionTo nastaví submitted_at pri prechode do Podané', function () {
    $user        = User::factory()->create();
    $application = makeApplicationInState(ApplicationStateMachine::STATE_DRAFT, $user);

    $sm = new ApplicationStateMachine($application, $user);
    $sm->transitionTo(ApplicationStateMachine::STATE_SUBMITTED);

    $application->refresh();

    expect($application->submitted_at)->not->toBeNull();
});
