<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Evaluation\Events\CommissionMemberInvited;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionInvitation;
use Modules\Evaluation\Models\CommissionMember;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;

class CommissionController extends Controller
{
    use AuthorizesRequests;

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $commissions = Commission::with([
            'members.user:id,name,surname,email',
            'invitations' => fn ($q) => $q->whereNull('accepted_at')->where('expires_at', '>', now()),
        ])->orderBy('id')->get();

        return response()->json(['data' => $commissions->map(fn ($c) => $this->format($c))]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:commission,name'],
        ]);

        $commission = Commission::create(['name' => $validated['name']]);

        return response()->json(['data' => $this->format($commission->load('members.user', 'invitations'))], Response::HTTP_CREATED);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $commission = Commission::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:commission,name,' . $id],
        ]);

        $commission->update(['name' => $validated['name']]);

        return response()->json(['data' => $this->format($commission->load('members.user', 'invitations'))]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $commission = Commission::findOrFail($id);

        // Prevent deletion if the commission is assigned to any currently active call.
        // A call is considered active when its latest status (highest id in
        // status_of_call_has_call) is NOT "Uzavreté".
        $assignedToActiveCall = CommissionMember::where('commission_id', $id)
            ->whereNotNull('call_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('status_of_call_has_call as h')
                    ->join('status_of_call as s', 's.id', '=', 'h.status_of_call_id')
                    ->whereColumn('h.call_id', 'commission_member.call_id')
                    ->where('s.name', '!=', 'Uzavreté')
                    // Only the latest status record for this call
                    ->whereNotExists(function ($inner) {
                        $inner->selectRaw('1')
                            ->from('status_of_call_has_call as h2')
                            ->whereColumn('h2.call_id', 'h.call_id')
                            ->whereColumn('h2.id', '>', 'h.id');
                    });
            })
            ->exists();

        if ($assignedToActiveCall) {
            return response()->json([
                'message' => 'Komisiu nie je možné vymazať, pretože je priradená k aktívnej výzve.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Prevent deletion if any member has evaluations
        $hasEvaluations = CommissionMember::where('commission_id', $id)
            ->whereHas('evaluations')
            ->exists();

        if ($hasEvaluations) {
            return response()->json([
                'message' => 'Komisiu nie je možné vymazať, pretože jej členovia majú priradené hodnotenia.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($commission) {
            CommissionMember::where('commission_id', $commission->id)->delete();
            $commission->delete();
        });

        return response()->json(['message' => 'Komisia bola vymazaná.']);
    }

    // ── Add / invite member ───────────────────────────────────────────────────

    public function addMember(Request $request, int $id): JsonResponse
    {
        $commission = Commission::findOrFail($id);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim($validated['email']));

        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $existingUser->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json([
                'message' => 'Administrátora nie je možné pridať do komisie.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($existingUser && $existingUser->hasAnyRole(['evaluator', 'predseda_komisie'])) {
            $alreadyMember = CommissionMember::where('commission_id', $id)
                ->where('user_id', $existingUser->id)
                ->whereNull('call_id')
                ->exists();

            if ($alreadyMember) {
                return response()->json(['message' => 'Používateľ je už členom tejto komisie.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id'       => $existingUser->id,
                'call_id'       => null,
            ]);

            return response()->json([
                'data'    => $this->format($commission->load('members.user', 'invitations')),
                'invited' => false,
            ], Response::HTTP_CREATED);
        }

        $pendingInvite = CommissionInvitation::where('commission_id', $id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvite) {
            return response()->json([
                'message' => 'Pre tento e-mail už existuje platná pozvánka.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $existingUser) {
            $existingUser = User::create([
                'name'      => preg_replace('/@.*$/', '', $email),
                'surname'   => '',
                'email'     => $email,
                'password'  => Hash::make(Str::random(32)),
                'status_id' => UserStatus::PENDING_EMAIL->value,
            ]);
        }

        $invite = CommissionInvitation::create([
            'token'         => Str::random(64),
            'email'         => $email,
            'commission_id' => $commission->id,
            'expires_at'    => now()->addHours(72),
        ]);

        $lang = $request->cookie('i18n_redirected', 'sk') === 'en' ? 'en' : 'sk';

        event(new CommissionMemberInvited(
            invitation:  $invite,
            commission:  $commission,
            lang:        $lang,
        ));

        return response()->json([
            'data'    => $this->format($commission->load('members.user', 'invitations')),
            'invited' => true,
            'message' => 'Pozvánka bola odoslaná na ' . $email . '.',
        ], Response::HTTP_CREATED);
    }

    // ── Remove member ─────────────────────────────────────────────────────────

    public function removeMember(int $id, int $memberId): JsonResponse
    {
        $member = CommissionMember::where('commission_id', $id)
            ->where('id', $memberId)
            ->whereNull('call_id')
            ->firstOrFail();

        if ($member->evaluations()->exists()) {
            return response()->json([
                'message' => 'Člena nie je možné odobrať, pretože má priradené hodnotenia.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $member->delete();

        $commission = Commission::findOrFail($id);
        return response()->json(['data' => $this->format($commission->load('members.user', 'invitations'))]);
    }

    // ── Evaluator list (for add-member dropdown) ──────────────────────────────

    public function evaluators(): JsonResponse
    {
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['evaluator', 'predseda_komisie']))
            ->select('id', 'name', 'surname', 'email')
            ->orderBy('surname')
            ->get()
            ->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => trim($u->name . ' ' . $u->surname),
                'email' => $u->email,
            ]);

        return response()->json(['data' => $users]);
    }

    // ── Format helper ─────────────────────────────────────────────────────────

    private function format(Commission $commission): array
    {
        $members = $commission->members
            ->filter(fn ($m) => $m->call_id === null)
            ->map(fn ($m) => [
                'id'      => $m->id,
                'user_id' => $m->user_id,
                'name'    => $m->user ? trim($m->user->name . ' ' . $m->user->surname) : null,
                'email'   => $m->user?->email,
                'status'  => 'active',
            ])->values();

        $pending = ($commission->relationLoaded('invitations') ? $commission->invitations : collect())
            ->filter(fn ($i) => ! $i->accepted_at && $i->expires_at > now())
            ->map(fn ($i) => [
                'id'      => null,
                'user_id' => null,
                'name'    => null,
                'email'   => $i->email,
                'status'  => 'pending',
            ])->values();

        return [
            'id'      => $commission->id,
            'name'    => $commission->name,
            'members' => $members->merge($pending)->values(),
        ];
    }
}
