<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;
use Modules\Organizations\Models\Organization;
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

        $categoryId = NotificationCategory::query()->where('slug', 'team')->value('id');
        if ($categoryId !== null) {
            $lang      = $request->cookie('i18n_redirected', 'sk');
            $orgName   = $invite->organization->name;
            $memberName = trim($user->name.' '.$user->surname) ?: $user->email;

            $orgAdmins = $invite->organization->users()
                ->wherePivot('organization_role', 'org_admin')
                ->get();

            foreach ($orgAdmins as $admin) {
                Notifications::query()->create([
                    'user_id'                  => $admin->id,
                    'notification_category_id' => $categoryId,
                    'notifiable_type'          => Organization::class,
                    'notifiable_id'            => $invite->organization_id,
                    'title'                    => $lang === 'en' ? 'Member accepted invitation' : 'Člen prijal pozvánku',
                    'body'                     => $lang === 'en'
                        ? $memberName.' has accepted the invitation to join "'.$orgName.'". Their account is pending NTI admin approval.'
                        : $memberName.' prijal/a pozvánku do organizácie „'.$orgName.'". Účet čaká na schválenie administrátorom NTI.',
                    'is_read'                  => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Účet bol aktivovaný. Môžete sa prihlásiť.',
        ]);
    }
}
