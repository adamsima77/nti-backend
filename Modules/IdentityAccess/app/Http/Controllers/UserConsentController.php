<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\IdentityAccess\Models\UserConsent;
use Illuminate\Http\Response;
class UserConsentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', UserConsent::class);
        $consents = UserConsent::with(['user', 'consent'])->orderBy('created_at', 'desc')->get();
        return response()->json(['consents' => $consents], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', UserConsent::class);
        $validated = $request->validate([
            'granted' => ['required', 'boolean'],
            'granted_at' => ['required', 'date'],
            'revoked_at' => ['nullable', 'date'],
            'ip' => ['required', 'ip'],
            'user_agent' => ['required', 'string'],
            'consent_id' => ['required', 'exists:consent_types,id'],
        ]);
        UserConsent::create([
            'granted' => $validated['granted'],
            'granted_at' => $validated['granted_at'],
            'revoked_at' => $validated['revoked_at'],
            'ip' => $validated['ip'],
            'user_agent' => $validated['user_agent'],
            'user_id' => $request->user()->id,
            'consent_id' => $validated['consent_id'],

        ]);
        return response()->json(['message' => 'Consent created'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $consent = UserConsent::with(['user', 'consent'])->findOrFail($id);
        $this->authorize('view', $consent);
        return response()->json(['consent' => $consent], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $consent = UserConsent::findOrFail($id);
        $this->authorize('update', $consent);
        $validated = $request->validate([
            'granted' => ['required', 'boolean'],
            'granted_at' => ['required', 'date'],
            'revoked_at' => ['nullable', 'date'],
            'ip' => ['required', 'ip'],
            'user_agent' => ['required', 'string'],
            'consent_id' => ['required', 'exists:consent_types,id'],
        ]);

        $consent->update([
            'granted' => $validated['granted'],
            'granted_at' => $validated['granted_at'],
            'revoked_at' => $validated['revoked_at'],
            'ip' => $validated['ip'],
            'user_agent' => $validated['user_agent'],
            'user_id' => $request->user()->id,
            'consent_id' => $validated['consent_id'],
        ]);
        return response()->json(['message' => 'Consent updated'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $consent = UserConsent::findOrFail($id);
        $this->authorize('delete', $consent);
        $consent->delete();
        return response()->json(['message' => 'Consent deleted'], Response::HTTP_OK);
    }
}
