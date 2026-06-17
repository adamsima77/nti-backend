<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Content\Models\Language;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Applications\Models\Application;
use Modules\Organizations\Events\OrganizationApproved;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
use Modules\IdentityAccess\Models\Role;
use Modules\Organizations\Events\OrganizationMemberInvited;
use Modules\Organizations\Models\OrganizationInvitation;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Organizations\Models\Sector;

class OrganizationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::with('address', 'sectors.sectorTranslations')->get();

        return response()->json([
            'organizations' => $organizations,
        ], Response::HTTP_OK);
    }

    public function activate(Request $request, Organization $organization)
    {
        $this->authorize('activate', $organization);

        $organization->load('users');

        $orgAdmin = $organization->users->first();

        if (!$orgAdmin) {
            return response()->json([
                'message' => 'No user found for this organization.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $orgAdmin->setStatus(UserStatus::ACTIVE);

        $langId = $this->getLanguageId($request);

        event(new OrganizationApproved($organization, $langId));

        return response()->json([
            'message' => 'Organization has been approved successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('organizations::create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Organization::class);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:30'],
            'ico'         => ['required', 'string', 'max:30', 'unique:organization,ico'],
            'web_url'     => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],

            'address.city'        => ['required', 'string', 'max:120'],
            'address.street'      => ['required', 'string', 'max:120'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.country'     => ['required', 'string', 'max:90'],

            'sectors'   => ['nullable', 'array'],
            'sectors.*' => ['integer'],
        ]);

        $organization = DB::transaction(function () use ($validated, $request) {
            $address = Address::create($validated['address']);

            $organization = Organization::create([
                'name'        => $validated['name'],
                'phone'       => !empty($validated['phone']) ? $validated['phone'] : null,
                'ico'         => $validated['ico'],
                'web_url'     => $validated['web_url'] ?? null,
                'description' => $validated['description'] ?? null,
                'address_id'  => $address->id,
            ]);

            if (!empty($validated['sectors'])) {
                $organization->sectors()->attach($validated['sectors']);
            }

            $adminRole = OrganizationRole::where('name', 'org_admin')->firstOrFail();

            $request->user()->organizations()->attach($organization->id, [
                'organization_role' => $adminRole->id,
            ]);

            return $organization;
        });

        return response()->json([
            'message'      => 'Organizácia bola úspešne vytvorená.',
            'organization' => $organization->load('address', 'sectors.sectorTranslations'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        $organization->load('address', 'sectors.sectorTranslations', 'users.roles');

        $roleIds = $organization->users
            ->pluck('pivot.organization_role')
            ->filter()
            ->unique()
            ->all();

        $roleLabels = OrganizationRole::whereIn('id', $roleIds)
            ->pluck('name', 'id')
            ->toArray();

        $members = $organization->users->map(function ($user) use ($roleLabels) {
            $pivotRole = $user->pivot->organization_role;
            $roleName = $roleLabels[$pivotRole] ?? null;

            return [
                'id' => $user->id,
                'name' => trim(sprintf('%s %s', $user->name, $user->surname)),
                'email' => $user->email,
                'status' => $user->status_id === UserStatus::ACTIVE->value ? 'active' : 'pending',
                'role' => match ($roleName) {
                    'org_admin' => 'admin',
                    'org_product_owner' => 'po',
                    default => 'member',
                },
                'addedAt' => $user->created_at?->toDateString(),
            ];
        });

        return response()->json([
            'organization' => $organization->only(['id', 'name', 'phone', 'ico', 'web_url', 'description']),
            'address' => $organization->address,
            'sectors' => $organization->sectors,
            'members' => $members,
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('organizations::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'phone'       => ['sometimes', 'string', 'max:30'],
            'ico'         => ['sometimes', 'string', 'max:30', 'unique:organization,ico,' . $organization->id],
            'web_url'     => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],

            'address.city'        => ['sometimes', 'string', 'max:120'],
            'address.street'      => ['sometimes', 'string', 'max:120'],
            'address.postal_code' => ['sometimes', 'string', 'max:20'],
            'address.country'     => ['sometimes', 'string', 'max:90'],

            'sectors'   => ['nullable', 'array'],
            'sectors.*' => ['integer'],
        ]);

        DB::transaction(function () use ($validated, $organization) {
            if (!empty($validated['address'])) {
                $organization->address->update($validated['address']);
            }

            $organization->update(
                collect($validated)->except('address', 'sectors')->toArray()
            );

            // Sync sectors — passing empty array clears all when key is present
            if (array_key_exists('sectors', $validated)) {
                $organization->sectors()->sync($validated['sectors'] ?? []);
            }
        });

        return response()->json([
            'message'      => 'Organizácia bola úspešne aktualizovaná.',
            'organization' => $organization->fresh()->load('address', 'sectors.sectorTranslations'),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        DB::transaction(function () use ($organization) {
            $address = $organization->address;

            $organization->sectors()->detach();
            $organization->users()->detach();
            $organization->delete();

            $address->delete();
        });

        return response()->json([
            'message' => 'Organizácia bola úspešne odstránená.',
        ], Response::HTTP_OK);
    }

    public function inviteMember(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:admin,member,po'],
        ]);

        $roleMap = [
            'admin' => 'org_admin',
            'member' => 'org_member',
            'po' => 'org_product_owner',
        ];

        $organizationRole = OrganizationRole::where('name', $roleMap[$validated['role']])->firstOrFail();

        $user = User::where('email', $validated['email'])->first();

        if ($user && $organization->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Už existuje člen s touto e-mailovou adresou v organizácii.',
            ], Response::HTTP_CONFLICT);
        }

        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $user = User::create([
                'name'      => preg_replace('/@.*$/', '', $validated['email']),
                'surname'   => '',
                'email'     => $validated['email'],
                'password'  => Hash::make(Str::random(32)),
                'status_id' => UserStatus::PENDING_EMAIL->value,
            ]);
        }

        // Assign global organization role if not already set
        $orgRole = Role::where('name', 'organization')->first();
        if ($orgRole && ! $user->roles()->where('name', 'organization')->exists()) {
            $user->roles()->attach($orgRole->id);
        }

        $organization->users()->attach($user->id, [
            'organization_role' => $organizationRole->id,
        ]);

        // Create invite token and send invite email (for new users only)
        if ($isNewUser) {
            $invite = OrganizationInvitation::create([
                'token'                => Str::random(64),
                'email'                => $validated['email'],
                'organization_id'      => $organization->id,
                'organization_role_id' => $organizationRole->id,
                'expires_at'           => now()->addHours(72),
            ]);

            $roleLabelMap = [
                'admin'  => 'Správca organizácie',
                'member' => 'Člen',
                'po'     => 'Product Owner',
            ];

            $lang = $request->cookie('i18n_redirected', 'sk') === 'en' ? 'en' : 'sk';

            event(new OrganizationMemberInvited(
                invitation:   $invite,
                organization: $organization,
                roleLabel:    $roleLabelMap[$validated['role']] ?? $validated['role'],
                lang:         $lang,
            ));
        }

        return response()->json([
            'message' => 'Pozvánka bola odoslaná.',
            'member'  => [
                'id'      => $user->id,
                'name'    => trim(sprintf('%s %s', $user->name, $user->surname)),
                'email'   => $user->email,
                'status'  => $user->status_id === UserStatus::ACTIVE->value ? 'active' : 'pending',
                'role'    => $validated['role'],
                'addedAt' => $user->created_at?->toDateString(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function updateMember(Request $request, Organization $organization, User $user)
    {
        $this->authorize('update', $organization);

        if (! $organization->users()->where('users.id', $user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,member,po'],
        ]);

        $roleMap = [
            'admin' => 'org_admin',
            'member' => 'org_member',
            'po' => 'org_product_owner',
        ];

        $organizationRole = OrganizationRole::where('name', $roleMap[$validated['role']])->firstOrFail();

        $organization->users()->updateExistingPivot($user->id, [
            'organization_role' => $organizationRole->id,
        ]);

        return response()->json([
            'message' => 'Role člena bola aktualizovaná.',
        ], Response::HTTP_OK);
    }

    public function removeMember(Organization $organization, User $user)
    {
        $this->authorize('update', $organization);

        if (! $organization->users()->where('users.id', $user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $organization->users()->detach($user->id);

        return response()->json([
            'message' => 'Člen bol odstránený z organizácie.',
        ], Response::HTTP_OK);
    }

    public function myOrganization(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $organization = $user->organizations()->first();

        if (! $organization) {
            return response()->json(['message' => 'Nie ste členom žiadnej organizácie.'], Response::HTTP_NOT_FOUND);
        }

        $orgRoleId = $organization->pivot->organization_role;
        $orgRoleName = OrganizationRole::find($orgRoleId)?->name;

        return response()->json([
            'organization' => $organization->only(['id', 'name', 'phone', 'ico', 'web_url', 'description']),
            'my_role' => match ($orgRoleName) {
                'org_admin'          => 'organization_admin',
                'org_product_owner'  => 'po',
                default              => 'member',
            },
        ], Response::HTTP_OK);
    }

    public function memberDashboard(Organization $organization): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewDashboard', $organization);

        $calls = $organization->calls()
            ->with([
                'currentStatusHistory.status',
                'callType',
                'program.typeOfProgram',
                'applications.team',
                'applications.status',
            ])
            ->withCount('applications')
            ->latest('id')
            ->get();

        $callsSummary = $calls->map(function ($call) {
            $assignedTeam = $call->applications
                ->first(fn ($app) => in_array($app->status?->name, ['Onboarding', 'Aktívny projekt', 'Ukončené']));

            return [
                'id'                   => $call->id,
                'name'                 => $call->name,
                'description'          => $call->description,
                'application_deadline' => $call->application_deadline?->toDateString(),
                'project_start'        => $call->project_start?->toDateString(),
                'project_end'          => $call->project_end?->toDateString(),
                'status'               => $call->currentStatusHistory?->status?->name,
                'call_type'            => $call->callType?->name,
                'program'              => $call->program?->typeOfProgram?->name,
                'applications_count'   => $call->applications_count,
                'assigned_team'        => $assignedTeam ? [
                    'id'   => $assignedTeam->team?->id,
                    'name' => $assignedTeam->team?->name,
                ] : null,
            ];
        });

        $stats = [
            'total_calls'    => $calls->count(),
            'active_calls'   => $calls->filter(fn ($c) => in_array($c->currentStatusHistory?->status?->name, ['Publikované', 'V párovaní', 'Pridelené', 'V realizácii']))->count(),
            'in_progress'    => $calls->filter(fn ($c) => $c->currentStatusHistory?->status?->name === 'V realizácii')->count(),
            'completed'      => $calls->filter(fn ($c) => $c->currentStatusHistory?->status?->name === 'Uzavreté')->count(),
        ];

        $teams = $calls->flatMap(fn ($call) => $call->applications
            ->filter(fn ($app) => in_array($app->status?->name, ['Onboarding', 'Aktívny projekt', 'Ukončené']))
            ->map(fn ($app) => [
                'team_name' => $app->team?->name,
                'call_name' => $call->name,
                'status'    => $app->status?->name,
            ])
        )->filter(fn ($t) => $t['team_name'])->values();

        $applications = $calls->flatMap(fn ($call) => $call->applications->map(fn ($app) => [
            'team_name'    => $app->team?->name ?? '—',
            'call_name'    => $call->name,
            'status'       => $app->status?->name,
            'submitted_at' => $app->created_at?->toDateString(),
        ]))->sortByDesc('submitted_at')->take(5)->values();

        return response()->json([
            'stats'        => $stats,
            'calls'        => $callsSummary,
            'teams'        => $teams,
            'applications' => $applications,
        ], Response::HTTP_OK);
    }

    public function backlog(Organization $organization)
    {
        $this->authorize('view', $organization);

        $backlog = $organization->calls()
            ->with(['currentStatusHistory.status', 'callType', 'program.typeOfProgram'])
            ->withCount('applications')
            ->latest('id')
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'name' => $call->name,
                    'description' => $call->description,
                    'created_at' => $call->created_at?->toDateTimeString(),
                    'application_deadline' => $call->application_deadline?->toDateTimeString(),
                    'program' => [
                        'id' => $call->program?->id,
                        'name' => $call->program?->typeOfProgram?->name,
                    ],
                    'call_type' => [
                        'id' => $call->callType?->id,
                        'name' => $call->callType?->name,
                    ],
                    'status' => [
                        'id' => $call->currentStatusHistory?->status?->id,
                        'name' => $call->currentStatusHistory?->status?->name,
                    ],
                    'applications_count' => $call->applications_count,
                ];
            });

        return response()->json([
            'backlog' => $backlog,
        ], Response::HTTP_OK);
    }
}
