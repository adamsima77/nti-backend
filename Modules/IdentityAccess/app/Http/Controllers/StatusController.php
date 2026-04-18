<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\IdentityAccess\Models\Status;
use Illuminate\Http\Response;
class StatusController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Status::class);
        $statuses = Status::all();
        return response()->json(['statuses' => $statuses], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Status::class);
        $validated = $request->validate([
           'name' =>  ['required', 'string', 'max:255']
        ]);
        Status::create([
            'name' => $validated['name']
        ]);
        return response()->json(['message' => 'Status created successfully.'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $status = Status::findOrFail($id);
        $this->authorize('view', $status);
        return response()->json(['status' => $status], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $status = Status::findOrFail($id);
        $this->authorize('update', $status);
        $validated = $request->validate([
            'name' =>  ['required', 'string', 'max:255']
        ]);
        $status->update([
            'name' => $validated['name']
        ]);
        return response()->json(['message' => 'Status updated.'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $status = Status::findOrFail($id);
        $this->authorize('delete', $status);
        $status->delete();
        return response()->json(['message' => 'Status deleted successfully.'], Response::HTTP_OK);
    }
}
