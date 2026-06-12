<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Programs\Database\Seeders\CallTypeSeeder;
use Modules\Programs\Database\Seeders\StatusOfCallSeeder;
use Modules\Programs\Database\Seeders\TypeOfProgramSeeder;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Programs\Models\TypeOfProgram;
use Modules\Programs\StateMachines\CallStateMachine;
use Modules\Programs\StateMachines\CallStateMachineProgramA;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new TypeOfProgramSeeder())->run();
    (new CallTypeSeeder())->run();
    (new StatusOfCallSeeder())->run();
});

function makeCallInState(?string $stateName = null): Call
{
    // Ensure Program A gets id=1 so Program B gets id=2 (matches machineFor() logic)
    $typeA = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    Program::firstOrCreate(['type_of_program_id' => $typeA->id]);

    $typeB   = TypeOfProgram::where('name', 'Program B')->firstOrFail();
    $program = Program::firstOrCreate(['type_of_program_id' => $typeB->id]);
    $callType = CallType::first();

    $call = Call::create([
        'name'                 => 'Testovacia výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => $callType->id,
        'force_closed'         => false,
    ]);

    if ($stateName !== null) {
        $status = StatusOfCall::where('name', $stateName)->firstOrFail();
        StatusOfCallHasCall::create([
            'call_id'           => $call->id,
            'status_of_call_id' => $status->id,
        ]);
    }

    return $call;
}

// ─── CallStateMachine (Program B) ───────────────────────────────────────────

test('call bez histórie je v stave Draft', function () {
    $call = makeCallInState();
    $sm   = new CallStateMachine($call);

    expect($sm->currentState())->toBe(CallStateMachine::STATE_DRAFT);
});

test('Draft → Čaká na schválenie je povolený', function () {
    $call = makeCallInState(CallStateMachine::STATE_DRAFT);
    $sm   = new CallStateMachine($call);

    expect($sm->canTransitionTo(CallStateMachine::STATE_PENDING))->toBeTrue();
});

test('Draft → Publikované priamo je zakázané', function () {
    $call = makeCallInState(CallStateMachine::STATE_DRAFT);
    $sm   = new CallStateMachine($call);

    expect($sm->canTransitionTo(CallStateMachine::STATE_PUBLISHED))->toBeFalse();
});

test('Čaká na schválenie → Publikované je povolené', function () {
    $call = makeCallInState(CallStateMachine::STATE_PENDING);
    $sm   = new CallStateMachine($call);

    expect($sm->canTransitionTo(CallStateMachine::STATE_PUBLISHED))->toBeTrue();
});

test('Čaká na schválenie → Draft (zamietnutie) je povolené', function () {
    $call = makeCallInState(CallStateMachine::STATE_PENDING);
    $sm   = new CallStateMachine($call);

    expect($sm->canTransitionTo(CallStateMachine::STATE_DRAFT))->toBeTrue();
});

test('Uzavreté nemá žiadne prechody', function () {
    $call = makeCallInState(CallStateMachine::STATE_CLOSED);
    $sm   = new CallStateMachine($call);

    expect($sm->availableTransitions())->toBeEmpty();
});

test('transitionTo hodí výnimku pri nepovolenom prechode', function () {
    $call = makeCallInState(CallStateMachine::STATE_DRAFT);
    $sm   = new CallStateMachine($call);

    expect(fn () => $sm->transitionTo(CallStateMachine::STATE_PUBLISHED))
        ->toThrow(InvalidArgumentException::class);
});

// ─── CallStateMachineProgramA ────────────────────────────────────────────────

test('Program A: Draft → Publikované je povolené', function () {
    $call = makeCallInState(CallStateMachineProgramA::STATE_DRAFT);
    $call->program_id = 1;
    $sm = new CallStateMachineProgramA($call);

    expect($sm->canTransitionTo(CallStateMachineProgramA::STATE_PUBLISHED))->toBeTrue();
});

test('Program A: Uzavreté nemá žiadne prechody', function () {
    $call = makeCallInState(CallStateMachineProgramA::STATE_CLOSED);
    $call->program_id = 1;
    $sm = new CallStateMachineProgramA($call);

    expect($sm->availableTransitions())->toBeEmpty();
});

test('Program A: nevyžaduje organization_id pri publikovaní', function () {
    $typeA    = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    $program  = Program::firstOrCreate(['type_of_program_id' => $typeA->id]);
    $callType = CallType::first();

    $call = Call::create([
        'name'                 => 'Test',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'call_type_id'         => $callType->id,
        'organization_id'      => null,
        'program_id'           => $program->id,
        'force_closed'         => false,
    ]);
    $sm = new CallStateMachineProgramA($call);

    expect($sm->missingFields(CallStateMachineProgramA::STATE_PUBLISHED))->toBeEmpty();
});
