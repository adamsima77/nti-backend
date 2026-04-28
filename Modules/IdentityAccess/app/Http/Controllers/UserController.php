<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\IdentityAccess\Models\User;
use Illuminate\Http\Response;
class UserController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('status', 'roles')->orderByDesc('created_at')
                ->paginate(15);
        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'surname' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status_id' => ['nullable', 'exists:statuses,id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096']
        ]);

        try{
            DB::beginTransaction();
            $path = null;
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
            }
            $user = User::create([
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status_id' => $validated['status_id']
                    ?? User::defaultStatus(),
                'avatar' => $path
            ]);
            $user->roles()->attach($validated['roles']);
            DB::commit();
            return response()->json(['message' => 'User was created.'], Response::HTTP_CREATED);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User could not be created !'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $user = User::with('status', 'roles', 'userConsents')->findOrFail($id);
        $this->authorize('view', $user);
        return response()->json($user, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    $this->authorize('update', $user);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:150'],
        'surname' => ['required', 'string', 'max:150'],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        'password' => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        'status_id' => ['nullable', 'exists:statuses,id'],
        'roles' => ['required', 'array', 'min:1'],
        'roles.*' => ['integer', 'exists:roles,id'],
        'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096']
    ]);

    $oldAvatar = $user->avatar;
    $newAvatar = $oldAvatar;

    try {
        DB::beginTransaction();

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
        }

        $data = [
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'status_id' => $validated['status_id'] ?? $user->status_id,
            'avatar' => $newAvatar
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);
        $user->roles()->sync($validated['roles']);

        DB::commit();


        if ($request->hasFile('avatar') && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'message' => 'User was successfully updated.'
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'User could not be updated!',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        try{
            DB::beginTransaction();
            if($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->delete();
            DB::commit();
            return response()->json(['message' => 'User was succesfully deleted.'], Response::HTTP_OK);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User could not be deleted.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
