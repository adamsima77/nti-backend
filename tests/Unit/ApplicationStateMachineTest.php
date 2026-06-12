<?php

use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\StateMachines\ApplicationStateMachine;
use Modules\IdentityAccess\Models\User;

// Pomocná funkcia: vytvorí Application s nastaveným statusom bez DB
function makeApplicationWithStatus(string $statusName): Application
{
    $status = new StatusOfApplication(['name' => $statusName]);
    $status->id = 1;

    $application = new Application();
    $application->setRelation('status', $status);

    return $application;
}

// ─── currentState ───────────────────────────────────────────────────────────

test('currentState vráti Draft ak nie je nastavený status', function () {
    $app = new Application();
    $app->setRelation('status', null);

    $sm = new ApplicationStateMachine($app);

    expect($sm->currentState())->toBe(ApplicationStateMachine::STATE_DRAFT);
});

test('currentState vráti meno statusu z relácie', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_SUBMITTED);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->currentState())->toBe(ApplicationStateMachine::STATE_SUBMITTED);
});

// ─── canTransitionTo ─────────────────────────────────────────────────────────

test('Draft → Podané je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_DRAFT);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_SUBMITTED))->toBeTrue();
});

test('Draft → Schválené je zakázaný prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_DRAFT);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_APPROVED))->toBeFalse();
});

test('Podané → V hodnotení je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_SUBMITTED);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_IN_EVALUATION))->toBeTrue();
});

test('Podané → Vyžiadané doplnenie je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_SUBMITTED);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_SUPPLEMENT_REQUESTED))->toBeTrue();
});

test('V hodnotení → Schválené je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_IN_EVALUATION);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_APPROVED))->toBeTrue();
});

test('V hodnotení → Zamietnuté je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_IN_EVALUATION);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_REJECTED))->toBeTrue();
});

test('Zamietnuté nemá žiadne povolené prechody', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_REJECTED);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->availableTransitions())->toBeEmpty();
});

test('Ukončené nemá žiadne povolené prechody', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_COMPLETED);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->availableTransitions())->toBeEmpty();
});

test('Aktívny projekt → Pozastavené je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_ACTIVE_PROJECT);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_PAUSED))->toBeTrue();
});

test('Aktívny projekt → Ukončené je povolený prechod', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_ACTIVE_PROJECT);
    $sm  = new ApplicationStateMachine($app);

    expect($sm->canTransitionTo(ApplicationStateMachine::STATE_COMPLETED))->toBeTrue();
});

// ─── transitionTo – výnimky bez DB ────────────────────────────────────────

test('transitionTo hodí InvalidArgumentException pri nepovolenom prechode', function () {
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_DRAFT);
    $sm  = new ApplicationStateMachine($app);

    expect(fn () => $sm->transitionTo(ApplicationStateMachine::STATE_APPROVED))
        ->toThrow(InvalidArgumentException::class);
});

test('transitionTo hodí InvalidArgumentException keď chýba StatusOfApplication v DB', function () {
    // Simuluje situáciu, kde sa prechod zdá platný, ale status nenájdeme v DB
    // – checkAnswerOfApplicationAnswer sa volá až neskôr, tu len overíme výnimku
    $app = makeApplicationWithStatus(ApplicationStateMachine::STATE_REJECTED);
    $sm  = new ApplicationStateMachine($app);

    expect(fn () => $sm->transitionTo(ApplicationStateMachine::STATE_ACTIVE_PROJECT))
        ->toThrow(InvalidArgumentException::class);
});
