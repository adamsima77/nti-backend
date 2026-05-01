<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\IdentityAccess\Models\Permission;
use Illuminate\Http\Response;
class PermissionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Permission::class);
        $permissions = Permission::paginate(15);
        return response()->json($permissions, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);
        Permission::create(['name' => $validated['name']]);
        return response()->json(['message' => 'Permission created successfully.'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('view', $permission);
        return response()->json($permission, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('update', $permission);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);
        $permission->update($validated);
        return response()->json(['message' => 'Permission updated successfully.'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('delete', $permission);
        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully.'], Response::HTTP_OK);
    }
}
