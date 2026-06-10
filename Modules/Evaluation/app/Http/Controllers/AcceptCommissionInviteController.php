<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionInvitation;
use Modules\Evaluation\Models\CommissionMember;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\Role;

class AcceptCommissionInviteController extends Controller
{

    public function show(Request $request): JsonResponse
    {
        $invite = CommissionInvitation::with('commission')
            ->where('token', $request->query('token'))
            ->first();

        if (! $invite || $invite->isAccepted() || $invite->isExpired()) {
            return response()->json([
                'valid'   => false,
                'message' => 'Pozvánka je neplatná alebo vypršala.',
            ], 422);
        }

        return response()->json([
            'valid'           => true,
            'email'           => $invite->email,
            'commission_name' => $invite->commission->name,
        ]);
    }


    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'name'     => ['required', 'string', 'max:100'],
            'surname'  => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $invite = CommissionInvitation::with('commission')
            ->where('token', $validated['token'])
            ->first();

        if (! $invite || $invite->isAccepted() || $invite->isExpired()) {
            return response()->json(['message' => 'Pozvánka je neplatná alebo vypršala.'], 422);
        }

        $user = User::where('email', $invite->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Účet nebol nájdený.'], 404);
        }

        $user->name              = $validated['name'];
        $user->surname           = $validated['surname'];
        $user->password          = Hash::make($validated['password']);
        $user->status_id         = UserStatus::PENDING_APPROVAL->value;
        $user->email_verified_at = now();
        $user->save();

        $role = Role::where('name', 'evaluator')->first();
        if ($role && ! $user->roles()->where('name', 'evaluator')->exists()) {
            $user->roles()->attach($role->id);
        }

        CommissionMember::firstOrCreate([
            'user_id'       => $user->id,
            'commission_id' => $invite->commission_id,
            'call_id'       => null,
        ]);

        $invite->accepted_at = now();
        $invite->save();

        return response()->json([
            'message' => 'Účet bol aktivovaný. Môžete sa prihlásiť po schválení administrátorom NTI.',
        ]);
    }
}
