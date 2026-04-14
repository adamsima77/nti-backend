<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Applications\Models\Applications;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApplicationsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Applications::class);

        $query = Applications::with('user');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Only show user's own applications unless admin
        if (!auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        $applications = $query->paginate(15);

        return response()->json($applications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Applications::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $application = Applications::create([
            ...$validated,
            'user_id' => auth()->id(),
            'submitted_at' => now(),
        ]);

        return response()->json($application->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Applications $application): JsonResponse
    {
        $this->authorize('view', $application);

        return response()->json($application->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Applications $application): JsonResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $application->update($validated);

        return response()->json($application->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Applications $application): JsonResponse
    {
        $this->authorize('delete', $application);

        $application->delete();

        return response()->json(['message' => 'Application deleted successfully']);
    }

    /**
     * Approve an application.
     */
    public function approve(Applications $application): JsonResponse
    {
        $this->authorize('approve', $application);

        $application->update(['status' => 'approved']);

        return response()->json($application->load('user'));
    }

    /**
     * Reject an application.
     */
    public function reject(Applications $application): JsonResponse
    {
        $this->authorize('reject', $application);

        $application->update(['status' => 'rejected']);

        return response()->json($application->load('user'));
    }
}
