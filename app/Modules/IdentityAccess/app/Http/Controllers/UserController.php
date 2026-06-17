<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Services\Pdf\PdfService;
use Modules\AuditCompliance\Models\GdprReport;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Document;
use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Evaluation\Models\CommissionMember;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationRole;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('status', 'roles')
            ->where('id', '!=', $request->user()->id)
            ->where('status_id', '!=', UserStatus::ANONYMIZED->value)
            ->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email',   'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($status = $request->query('status')) {
            $query->where('status_id', $status);
        }

        return response()->json(
            $query->paginate($request->query('per_page', 15)),
            Response::HTTP_OK
        );
    }

    public function getMentors()
    {
        $mentors = User::whereHas('roles', function ($query) {
            $query->where('name', 'mentor');
        })
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($mentors, Response::HTTP_OK);
    }

    /**
     * Anonymizes a user's personal data in accordance with GDPR Article 5(1)(e)
     * (storage limitation principle) and Article 17 (right to erasure).
     */
    public function anonymizeUser(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('anonymizeUser', $user);

        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        // Wrap in a transaction to prevent partial state corruption if anything breaks
        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {

            // Safely delete a Document: clear every known reference to it first
            // (GdprReport attachments, application/milestone/call pivots), then
            // its versions and files, then the row itself. Centralizing this
            // means every deletion site gets the same cleanup instead of each
            // one re-discovering a missing FK cleanup via a 500 error.
            $deleteDocumentSafely = function ($document) {
                if (!$document) {
                    return;
                }

                \Modules\AuditCompliance\Models\GdprReport::withTrashed()
                    ->where('attachment_id', $document->id)
                    ->get()
                    ->each(fn ($report) => $report->forceDelete());

                $document->applications()->detach();

                DB::table('document_has_milestone')
                    ->where('document_id', $document->id)
                    ->delete();

                DB::table('document_has_call')
                    ->where('document_id', $document->id)
                    ->delete();

                foreach ($document->versions as $version) {
                    if ($version->file_path && Storage::exists($version->file_path)) {
                        Storage::delete($version->file_path);
                    }
                    $version->delete();
                }
                $document->delete();
            };

            // ==========================================
            // STEP 1: HANDLE STUDENT SPECIFIC DEPENDENCIES FIRST
            // ==========================================
            if ($user->isStudent()) {
                if ($user->student?->cv) {
                    Storage::delete($user->student->cv);
                }

                $user->student?->update([
                    'cv_document_id' => null,
                    'portfolio_url'  => null,
                ]);

                if ($user->student) {
                    $user->student->academicFlags()->detach();
                }

                // CRITICAL: Clear the academic record reference BEFORE touching any document tables
                if ($user->student?->academicRecord) {
                    $academicRecord = $user->student->academicRecord;

                    // Track the ID of the document to manually target it next
                    $transcriptFileId = $academicRecord->transcript_file;

                    // Delete the referencing row first
                    $academicRecord->delete();

                    // Now clean up its files and versions
                    $deleteDocumentSafely(\Modules\Applications\Models\Document::find($transcriptFileId));
                }

            } elseif ($user->isPartner()) {
                $user->organizations()->detach();
            }

            // ==========================================
            // STEP 2: METADATA ANONYMIZATION
            // ==========================================
            $user->update([
                'name'              => 'Anonymized',
                'surname'           => 'User',
                'email'             => 'anonymized' . $user->id . '@nti.com',
                'password'          => bcrypt(Str::random(64)),
                'job_position'      => null,
                'avatar'            => null,
            ]);

            $user->forceFill([
                'email_verified_at' => null,
                'anonymized_at' => now(),
            ])->save();

            $user->setStatus(UserStatus::ANONYMIZED);
            $user->userConsents()->delete();
            $user->roles()->detach();
            $user->teams()->detach();

            // ==========================================
            // STEP 3: APPLICATION & GENERAL DOCUMENT CLEANUP
            // ==========================================
            foreach (Application::where('created_by', $user->id)->cursor() as $application) {
                $application->delete();
            }

            // Fix the NOT NULL constraint & remove remaining owned documents safely
            $remainingDocuments = \Modules\Applications\Models\Document::where('owner_id', $user->id)->get();
            foreach ($remainingDocuments as $doc) {
                $deleteDocumentSafely($doc);
            }

            //Temporary fix sets evaluation to admin
            CommissionMember::where('user_id', $user->id)->update([
                'user_id' => auth()->id()
            ]);

            // ==========================================
            // STEP 4: AUDIT COMPLIANCE LOGGING
            // ==========================================
            AuditCompliance::create([
                'user_id' => null,
                'action' => 'gdpr.user.anonymize_full',
                'object_type' => 'User',
                'object_id' => $user->id,
                'ip' => 'system',
                'result' => 'success',
                'result_payload' => [
                    'actor' => 'system',
                    'object' => 'User:' . $user->id,
                ],
                'time_of_action' => now(),
            ]);
        });

        return response()->json(['message' => 'User was successfully anonymized'], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * All profile data (student, organization) is nested in the same request
     * and created via Eloquent relations on the new User object.
     * No user_id is ever accepted from the frontend.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        // Resolve the selected role name up front so we can apply conditional rules
        $roleNames = collect();
        if ($request->has('roles')) {
            $roleIds = (array) $request->input('roles');
            $roleNames = Role::whereIn('id', $roleIds)->pluck('name');
        }

        $isStudent = $roleNames->contains('student');
        $isPartner = $roleNames->contains('partner');

        $rules = [
            'name'         => ['nullable', 'string', 'max:150'],
            'surname'      => ['nullable', 'string', 'max:150'],
            'email'        => ['required', 'email', 'unique:users'],
            'password'     => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status_id'    => ['required', 'exists:statuses,id'],
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['integer', 'exists:roles,id'],
            'avatar'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
            'job_position' => ['nullable', 'string', 'max:255'],

            // Student profile — required only when role = student
            'student.study_program_id' => [$isStudent ? 'required' : 'nullable', 'integer'],
            'student.study_field_id'   => [$isStudent ? 'required' : 'nullable', 'integer'],
            'student.university_id'    => [$isStudent ? 'required' : 'nullable', 'integer'],
            'student.study_year_id'    => [$isStudent ? 'required' : 'nullable', 'integer'],
            'student.portfolio_url'    => ['nullable', 'url', 'max:255'],
            'student.academic_flags'   => ['nullable', 'array'],
            'student.academic_flags.*' => ['integer'],

            // Organization — required only when role = partner
            'organization.name' => [$isPartner ? 'required' : 'nullable', 'string', 'max:255'],
            'organization.phone' => [$isPartner ? 'required' : 'nullable', 'phone', 'max:30'],
            'organization.ico' => [$isPartner ? 'required' : 'nullable', 'digits:8'],
            'organization.web_url' => ['nullable', 'url', 'max:255'],
            'organization.description' => ['nullable', 'string'],
            'organization.address.street' => [$isPartner ? 'required' : 'nullable', 'string', 'max:120'],
            'organization.address.city' => [$isPartner ? 'required' : 'nullable', 'string', 'max:120'],
            'organization.address.postal_code' => [$isPartner ? 'required' : 'nullable', 'digits:5'],
            'organization.address.country' => [$isPartner ? 'required' : 'nullable', 'string', 'max:90'],
            'organization.sectors' => [$isPartner ? 'required' : 'nullable', 'array'],
            'organization.sectors.*' => ['integer'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            $path = null;
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
            }

            $user = User::create([
                'name'         => $validated['name'],
                'surname'      => $validated['surname'],
                'email'        => $validated['email'],
                'password'     => $validated['password'],
                'status_id'    => $validated['status_id'] ?? User::defaultStatus(),
                'avatar'       => $path,
                'job_position' => $validated['job_position'] ?? null,
            ]);

            $user->email_verified_at = now(); //Admin should create user immediately without verification
            $user->save();

            $user->roles()->attach($validated['roles']);

            // Student — created via $user->student() relation; user_id set by Eloquent
            if ($isStudent && !empty($validated['student']['study_program_id'])) {
                $s = $validated['student'];

                $student = $user->student()->create([
                    'study_program_id' => $s['study_program_id'],
                    'study_field_id'   => $s['study_field_id'] ?? null,
                    'university_id'    => $s['university_id']  ?? null,
                    'study_year_id'    => $s['study_year_id']  ?? null,
                    'portfolio_url'    => $s['portfolio_url']  ?? null,
                ]);

                if (!empty($s['academic_flags'])) {
                    $student->academicFlags()->attach($s['academic_flags']);
                }
            }

            // Organization — created and attached to the new user via relation
            if ($isPartner && !empty($validated['organization']['name'])) {
                $o = $validated['organization'];

                $address = Address::create($o['address'] ?? [
                    'street' => '', 'city' => '', 'postal_code' => '', 'country' => '',
                ]);

                $organization = Organization::create([
                    'name'        => $o['name'],
                    'phone'       => !empty($o['phone']) ? $o['phone'] : null,
                    'ico'         => $o['ico']          ?? null,
                    'web_url'     => $o['web_url']      ?? null,
                    'description' => $o['description']  ?? null,
                    'address_id'  => $address->id,
                ]);

                if (!empty($o['sectors'])) {
                    $organization->sectors()->attach($o['sectors']);
                }

                $adminRole = OrganizationRole::where('name', 'org_admin')->first();
                if (!$adminRole) {
                    return response()->json([
                        'message' => 'Missing required organization role: admin'
                    ], 422);
                }

                // Attach org to the new partner user — not the logged-in admin
                $user->organizations()->attach($organization->id, [
                    'organization_role' => $adminRole->id,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'User was created.'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'User could not be created!',
                'debug'   => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $user = User::with([
            'status',
            'roles',
            'userConsents',
            'student.studyProgram',
            'student.studyField',
            'student.studyYear',
            'student.university',
            'student.academicFlags',
            'organizations.address',
            'organizations.sectors.sectorTranslations',
        ])->findOrFail($id);

        $this->authorize('view', $user);
        return response()->json($user, Response::HTTP_OK);
    }

    public function profile(Request $request)
    {
        return $this->show($request->user()->id);
    }

    public function updateProfile(Request $request)
    {
        return $this->update($request, $request->user()->id);
    }

    public function uploadCurrentAvatar(Request $request)
    {
        return $this->uploadAvatar($request, $request->user());
    }

    public function downloadPdf(User $user, PdfService $pdfService)
    {
        $this->authorize('pdf', $user);

        $user->load(['status', 'roles', 'teams']);

        return $pdfService->download(
            'identityaccess::pdf.profile',
            ['user' => $user],
            'user-profile-' . $user->id . '.pdf'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'name'         => ['nullable', 'string', 'max:150'],
            'surname'      => ['nullable', 'string', 'max:150'],
            'email'        => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'     => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status_id'    => ['nullable', 'exists:statuses,id'],
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['integer', 'exists:roles,id'],
            'avatar'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
            'job_position' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        $oldAvatar = $user->avatar;
        $newAvatar = $oldAvatar;

        try {
            DB::beginTransaction();

            if ($request->hasFile('avatar')) {
                $newAvatar = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update([
                'name'         => $validated['name'],
                'surname'      => $validated['surname'],
                'email'        => $validated['email'],
                'status_id'    => $validated['status_id'] ?? $user->status_id,
                'avatar'       => $newAvatar,
                'job_position' => $validated['job_position'] ?? $user->job_position,
            ]);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->save();
            }

            $user->roles()->sync($validated['roles']);

            DB::commit();

            if ($request->hasFile('avatar') && $oldAvatar) {
                Storage::disk('public')->delete($oldAvatar);
            }

            return response()->json(['message' => 'User was successfully updated.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload avatar via dedicated endpoint.
     * POST + multipart is reliable; PUT with FormData in PHP often doesn't populate $_FILES.
     */
    public function uploadAvatar(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$request->hasFile('avatar')) {
            return response()->json(['message' => 'No file uploaded.'], Response::HTTP_BAD_REQUEST);
        }

        $oldAvatar = $user->avatar;
        $path      = $request->file('avatar')->store('avatars', 'public');

        try {
            DB::beginTransaction();
            $user->update(['avatar' => $path]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'User could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $user->refresh();

        return response()->json([
            'message'    => 'Avatar was successfully updated.',
            'avatar_url' => $user->avatar_url,
            'avatar'     => $user->avatar,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        try {
            DB::beginTransaction();

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();
            DB::commit();

            return response()->json(['message' => 'User was successfully deleted.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User could not be deleted.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a student profile for an existing user (admin edit-mode flow).
     * Uses $user->student()->create() so user_id is set by Eloquent, never from input.
     */
    public function createStudentProfile(Request $request, User $user)
    {
        $this->authorize('update', $user);

        if ($user->student()->exists()) {
            return response()->json(['message' => 'Študentský profil už existuje.'], Response::HTTP_CONFLICT);
        }

        $validator = Validator::make($request->all(), [
            'study_program_id' => ['required', 'integer'],
            'study_field_id'   => ['required', 'integer'],
            'university_id'    => ['required', 'integer'],
            'study_year_id'    => ['required', 'integer'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'academic_flags'   => ['nullable', 'array'],
            'academic_flags.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        $student = DB::transaction(function () use ($user, $validated) {
            $student = $user->student()->create([
                'study_program_id' => $validated['study_program_id'] ?? null,
                'study_field_id'   => $validated['study_field_id']   ?? null,
                'university_id'    => $validated['university_id']    ?? null,
                'study_year_id'    => $validated['study_year_id']    ?? null,
                'portfolio_url'    => $validated['portfolio_url']    ?? null,
            ]);

            if (!empty($validated['academic_flags'])) {
                $student->academicFlags()->attach($validated['academic_flags']);
            }

            return $student;
        });

        return response()->json(['message' => 'Študentský profil bol vytvorený.', 'student' => $student], Response::HTTP_CREATED);
    }

    /**
     * Create and attach an organization for an existing partner user (admin edit-mode flow).
     * Attaches via $user->organizations() so the correct user is linked, not the admin.
     */
    public function createOrganizationProfile(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'name'                => ['required', 'string', 'max:255'],
            'phone'               => ['required', 'phone', 'max:30'],
            'ico'                 => ['required', 'string', 'max:30'],
            'web_url'             => ['nullable', 'url', 'max:255'],
            'description'         => ['nullable', 'string'],
            'address.street'      => ['required', 'string', 'max:120'],
            'address.city'        => ['required', 'string', 'max:120'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.country'     => ['required', 'string', 'max:90'],
            'sectors'             => ['required', 'array'],
            'sectors.*'           => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        $organization = DB::transaction(function () use ($user, $validated) {
            $address = Address::create($validated['address'] ?? [
                'street' => '', 'city' => '', 'postal_code' => '', 'country' => '',
            ]);

            $organization = Organization::create([
                'name'        => $validated['name'],
                'phone'       => !empty($validated['phone']) ? $validated['phone'] : null,
                'ico'         => $validated['ico']          ?? null,
                'web_url'     => $validated['web_url']      ?? null,
                'description' => $validated['description']  ?? null,
                'address_id'  => $address->id,
            ]);

            if (!empty($validated['sectors'])) {
                $organization->sectors()->attach($validated['sectors']);
            }

            $adminRole = OrganizationRole::where('name', 'org_admin')->first();
            if (!$adminRole) {
                return response()->json([
                    'message' => 'Missing required organization role: admin'
                ], 422);
            }

            $user->organizations()->attach($organization->id, [
                'organization_role' => $adminRole->id,
            ]);

            return $organization;
        });

        return response()->json(['message' => 'Organizácia bola vytvorená.', 'organization' => $organization], Response::HTTP_CREATED);
    }
}
