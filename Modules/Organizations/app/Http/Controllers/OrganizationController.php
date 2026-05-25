<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Events\OrganizationApproved;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
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

    /**
     * Store a newly created resource in storage.
     *
     * When an admin creates an organization on behalf of a partner user they
     * pass `partner_user_id`. The organization is then attached to that user
     * instead of to the authenticated admin. When `partner_user_id` is absent
     * (e.g. a partner self-registering) the authenticated user is attached,
     * preserving the original self-registration flow.
     */
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

            $adminRole = OrganizationRole::where('name', 'admin')->firstOrFail();

            // Self-registration: attach org to the authenticated user
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

        return response()->json([
            'organization' => $organization->load('address', 'sectors.sectorTranslations', 'users'),
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

    public function backlog(Organization $organization)
    {
        $this->authorize('view', $organization);

        $backlog = $organization->calls()
            ->with('statusHistory.status', 'callType')
            ->whereHas('statusHistory', function ($query) {
                $query->whereIn('status_of_call_id', function ($sub) {
                    $sub->select('id')
                        ->from('status_of_call')
                        ->whereIn('name', ['published', 'matching', 'assigned', 'in_progress']);
                })->latest()->limit(1);
            })
            ->get();

        return response()->json([
            'backlog' => $backlog,
        ], Response::HTTP_OK);
    }
}
