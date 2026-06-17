<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Programs\Models\Call;
use Modules\Programs\StateMachines\CallStateMachine;
use Modules\Programs\StateMachines\CallStateMachineProgramA;

class CallWorkflowController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('programs::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('programs::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show(Call $call)
    {
        $machine = $this->machineFor($call);

        return response()->json([
            'current_state'         => $machine->currentState(),
            'available_transitions' => $machine->availableTransitions(),
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('programs::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function transition(Request $request, Call $call)
    {
        $this->authorize('transition', $call);

        $validated = $request->validate([
            'state' => ['required', 'string', 'exists:status_of_call,name'],
            'note'  => ['nullable', 'string'],
        ]);

        $machine = $this->machineFor($call);

        if (!$machine->canTransitionTo($validated['state'])) {
            return response()->json([
                'message'               => "Prechod zo stavu '{$machine->currentState()}' do stavu '{$validated['state']}' nie je povolený.",
                'current_state'         => $machine->currentState(),
                'available_transitions' => $machine->availableTransitions(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $missing = $machine->missingFields($validated['state']);

        if (!empty($missing)) {
            return response()->json([
                'message'        => 'Chýbajú povinné polia pre prechod do stavu "' . $validated['state'] . '".',
                'missing_fields' => $missing,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $machine->transitionTo($validated['state'], $validated['note'] ?? null);

        return response()->json([
            'message'               => 'Stav výzvy bol úspešne zmenený na "' . $validated['state'] . '".',
            'current_state'         => $validated['state'],
            'available_transitions' => $this->machineFor($call->fresh())->availableTransitions(),
        ], Response::HTTP_OK);
    }

    private function machineFor(Call $call): CallStateMachine|CallStateMachineProgramA
    {
        $call->loadMissing('program');

        return $call->program_id === 1
            ? new CallStateMachineProgramA($call)
            : new CallStateMachine($call);
    }
}
