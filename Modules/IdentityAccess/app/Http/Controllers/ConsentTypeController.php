<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\IdentityAccess\Models\ConsentType;

class ConsentTypeController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', ConsentType::class);
        $consent_types = ConsentType::all();
        return response()->json(['consent_types' => $consent_types], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ConsentType::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
        ]);
        ConsentType::create([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);
        return response()->json(['message' => 'Consent type created successfully.'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $consent_type = ConsentType::findOrFail($id);
        $this->authorize('view', $consent_type);
        return response()->json(['consent_type' => $consent_type], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $consent_type = ConsentType::findOrFail($id);
        $this->authorize('update', $consent_type);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000']
        ]);
        $consent_type->update([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);
        return response()->json(['Consent type updated successfully.'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $consent_type = ConsentType::findOrFail($id);
        $this->authorize('delete', $consent_type);
        $consent_type->delete();
        return response()->json(['message' => 'Consent type deleted successfully.'], Response::HTTP_OK);
    }
}
