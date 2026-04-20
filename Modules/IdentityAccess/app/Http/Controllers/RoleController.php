<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

class RoleController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);
        $roles = Role::paginate(15);
        return response()->json(['roles' => $roles], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);
        $validated = $request->validate([
           'name' => ['required', 'string', 'max:255'],
        ]);
        Role::create([
            'name' => $validated['name']
        ]);
        return response()->json(['message' => 'Role created!'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('view', $role);
        return response()->json(['role' => $role], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);
        $role->update([
            'name' => $validated['name']
        ]);
        return response()->json(['message' => 'Role updated!'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);
        $role->delete();
        return response()->json(['message' => 'Role deleted!'], Response::HTTP_OK);
    }
}
