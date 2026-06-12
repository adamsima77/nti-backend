<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Applications\Database\Seeders\StatusOfApplicationSeeder;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Database\Seeders\CallTypeSeeder;
use Modules\Programs\Database\Seeders\StatusOfCallSeeder;
use Modules\Programs\Database\Seeders\TypeOfProgramSeeder;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Organizations\Models\Organization;
use Modules\Programs\Models\TypeOfProgram;
use Modules\Programs\StateMachines\CallStateMachine;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();

    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
    (new StatusOfApplicationSeeder())->run();
    (new TypeOfProgramSeeder())->run();
    (new CallTypeSeeder())->run();
    (new StatusOfCallSeeder())->run();
});

function makeAdminUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'nti_admin')->first();
    if ($role) {
        $user->roles()->sync([$role->id]);
    }
    return $user;
}

function makeStudentUserForCall(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::where('name', 'student')->first();
    if ($role) {
        $user->roles()->sync([$role->id]);
    }
    return $user;
}

function makeProgramB(): Program
{
    // Create Program A first so it gets id=1; Program B gets id=2.
    // machineFor() checks program_id === 1 for Program A routing.
    $typeA = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    Program::firstOrCreate(['type_of_program_id' => $typeA->id]);

    $typeB = TypeOfProgram::where('name', 'Program B')->firstOrFail();
    return Program::firstOrCreate(['type_of_program_id' => $typeB->id]);
}

function makeCall(array $overrides = []): Call
{
    $program  = makeProgramB();
    $callType = CallType::first();

    return Call::create(array_merge([
        'name'                 => 'Testovacia výzva',
        'description'          => 'Popis výzvy',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => $callType->id,
        'force_closed'         => false,
    ], $overrides));
}

function putCallInState(Call $call, string $stateName): void
{
    $status = StatusOfCall::where('name', $stateName)->firstOrFail();
    StatusOfCallHasCall::create([
        'call_id'           => $call->id,
        'status_of_call_id' => $status->id,
    ]);
}

// ─── Workflow API ────────────────────────────────────────────────────────────

test('neprihlásený nemôže meniť stav výzvy', function () {
    $call = makeCall();

    $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PENDING,
    ])->assertUnauthorized();
});

test('student nemôže meniť stav výzvy', function () {
    $student = makeStudentUserForCall();
    $call    = makeCall();

    Sanctum::actingAs($student, ['*']);

    $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PENDING,
    ])->assertForbidden();
});

test('admin môže posunúť výzvu z Draft na Čaká na schválenie', function () {
    $admin = makeAdminUser();
    $call  = makeCall();

    Sanctum::actingAs($admin, ['*']);

    $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PENDING,
    ])->assertOk()->assertJsonPath('current_state', CallStateMachine::STATE_PENDING);
});

test('admin môže schváliť výzvu z Čaká na schválenie na Publikované', function () {
    $admin = makeAdminUser();
    $org   = Organization::factory()->create();
    $call  = makeCall(['organization_id' => $org->id]);
    putCallInState($call, CallStateMachine::STATE_PENDING);

    Sanctum::actingAs($admin, ['*']);

    $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PUBLISHED,
    ])->assertOk()->assertJsonPath('current_state', CallStateMachine::STATE_PUBLISHED);
});

test('neplatný prechod vráti 422', function () {
    $admin = makeAdminUser();
    $call  = makeCall();

    Sanctum::actingAs($admin, ['*']);

    // Draft → Publikované priamo je zakázané
    $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PUBLISHED,
    ])->assertUnprocessable();
});

test('prechod na Publikované bez povinných polí vráti 422 s listom chýbajúcich polí', function () {
    $admin = makeAdminUser();

    // Výzva bez organization_id (povinné pre Program B → Publikované)
    $call = Call::create([
        'name'                 => 'Neúplná výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => makeProgramB()->id,
        'call_type_id'         => CallType::first()->id,
        'force_closed'         => false,
        // organization_id chýba – povinné pre Publikované v Program B
    ]);
    putCallInState($call, CallStateMachine::STATE_PENDING);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/calls/{$call->id}/workflow", [
        'state' => CallStateMachine::STATE_PUBLISHED,
    ]);

    $response->assertUnprocessable()
             ->assertJsonStructure(['missing_fields']);

    expect($response->json('missing_fields'))->not->toBeEmpty();
});

test('GET workflow endpoint vráti aktuálny stav a dostupné prechody', function () {
    $admin = makeAdminUser();
    $call  = makeCall();

    Sanctum::actingAs($admin, ['*']);

    $this->getJson("/api/v1/calls/{$call->id}/workflow")
        ->assertOk()
        ->assertJsonStructure(['current_state', 'available_transitions'])
        ->assertJsonPath('current_state', CallStateMachine::STATE_DRAFT);
});
