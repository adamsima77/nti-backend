<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\OrganizationInvitation;

class AcceptInviteController extends Controller
{
    // GET /auth/invite?token=xxx  — validate token, return org + role info
    public function show(Request $request): JsonResponse
    {
        $invite = OrganizationInvitation::with('organization', 'organizationRole')
            ->where('token', $request->query('token'))
            ->first();

        if (! $invite || $invite->isAccepted() || $invite->isExpired()) {
            return response()->json([
                'valid'   => false,
                'message' => 'Pozvánka je neplatná alebo vypršala.',
            ], 422);
        }

        return response()->json([
            'valid'             => true,
            'email'             => $invite->email,
            'organization_name' => $invite->organization->name,
            'role'              => $invite->organizationRole->name,
        ]);
    }

    // POST /auth/accept-invite
    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'name'     => ['required', 'string', 'max:100'],
            'surname'  => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $invite = OrganizationInvitation::with('organization', 'organizationRole')
            ->where('token', $validated['token'])
            ->first();

        if (! $invite || $invite->isAccepted() || $invite->isExpired()) {
            return response()->json([
                'message' => 'Pozvánka je neplatná alebo vypršala.',
            ], 422);
        }

        $user = User::where('email', $invite->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Účet nebol nájdený.'], 404);
        }

        // Set name, password + mark email verified, but wait for NTI admin approval
        $user->name              = $validated['name'];
        $user->surname           = $validated['surname'];
        $user->password          = Hash::make($validated['password']);
        $user->status_id         = UserStatus::PENDING_APPROVAL->value;
        $user->email_verified_at = now();
        $user->save();

        // Mark invite as accepted
        $invite->accepted_at = now();
        $invite->save();

        return response()->json([
            'message' => 'Účet bol aktivovaný. Môžete sa prihlásiť.',
        ]);
    }
}
