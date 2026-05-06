<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Applications\Models\SecurityClassification;
use Modules\IdentityAccess\Events\OrganizationOnboarded;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\IdentityAccess\Events\StudentOnboarded;
use Modules\IdentityAccess\Events\UserRegistered;
use Modules\IdentityAccess\Models\ConsentType;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\Status;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\UserConsent;
use Modules\Organizations\Models\Address;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Students\Models\Student;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use AuthorizesRequests;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'role'     => ['required', 'string', 'in:student,partner'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'email'     => $validated['email'],
                'password'  => $validated['password'],
                'status_id' => UserStatus::PENDING_EMAIL->value,
            ]);

            $role = Role::where('name', $validated['role'])->firstOrFail();
            $user->roles()->attach($role->id);


            $termsConsent = ConsentType::where('name', 'privacy_policy')->firstOrFail();

            UserConsent::create([
                'user_id'    => $user->id,
                'consent_id' => $termsConsent->id,
                'granted'    => true,
                'granted_at' => now(),
                'revoked_at' => null,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $user->sendEmailVerificationNotification();

            DB::commit();
            return response()->json(['message' => 'Registration successful, verify email!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Registration failed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function organizationOnboarding(Request $request){
        $this->authorize('onboarding', $request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'phone:SK,CZ,AUTO'],
            'ico' => ['required', 'digits:8'],
            'web_url' => ['nullable', 'url', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'digits:5'],
            'country' => ['required', 'string', 'max:255'],
            'sector' => ['required', 'array'],
            'sector.*' => ['required', 'integer', 'exists:sector,id']
        ]);

        try {
            DB::beginTransaction();

            $address = Address::create([
                'city' => $validated['city'],
                'street' => $validated['street'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country']
            ]);

            $organization = Organization::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'ico' => $validated['ico'],
                'web_url' => $validated['web_url'] ?? null,
                'address_id' => $address->id
            ]);

            $org_admin = OrganizationRole::where('name', 'org_admin')->firstOrFail();

            $organization->sectors()->sync($validated['sector']);

            $organization->users()->attach(auth()->id(), [
                'organization_role' => $org_admin->id,
            ]);

            $request->user()->setStatus(UserStatus::ACTIVE);

            DB::commit();

            event(new OrganizationOnboarded($organization, $request->user()->email));

            return response()->json([
                'message' => 'Onboarding was successful'
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Organization could not be onboarded !'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function studentOnboarding(Request $request){
        $this->authorize('onboarding', $request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'study_program' => ['required', 'integer', 'exists:study_program,id'],
            'study_field' => ['required', 'integer', 'exists:study_field,id'],
            'university' => ['required', 'integer', 'exists:university,id'],
            'cv' => ['required', 'file', 'mimes:pdf,docx'],
            'year_of_study' => ['required', 'integer', 'between:1,6'],
            'portfolio_url' => ['nullable', 'string', 'max:255']
        ]);

        try {
            DB::beginTransaction();

            $request->user()->update([
                'name' => $validated['name'],
                'surname' => $validated['surname']
            ]);

            $securityClassification = SecurityClassification::where('name', 'internal')->firstOrFail();

            $uploadedFile = $validated['cv'];
            $fileName = $uploadedFile->getClientOriginalName();
            $storedFileName = Str::uuid(). '_' . $fileName;
            $filePath = Storage::disk('local')->putFileAs('documents', $uploadedFile, $storedFileName);

            $document = Document::create([
                'owner_id' => $request->user()->id,
                'security_classification_id' => $securityClassification->id,
            ]);

            DocumentVersion::create([
                'document_id' => $document->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
            ]);

            Student::create([
                'user_id' => $request->user()->id,
                'study_program_id' => $validated['study_program'],
                'study_field_id' => $validated['study_field'],
                'university_id' => $validated['university'],
                'year_of_study' => $validated['year_of_study'],
                'portfolio_url' => $validated['portfolio_url'] ?? null,
                'cv_document_id' => $document->id
            ]);

            $request->user()->setStatus(UserStatus::ACTIVE);

            DB::commit();

            event(new StudentOnboarded($request->user()));

            return response()->json(['message' => 'Onboarding was successful'], 200);

        } catch (\Throwable $th) {
            DB::rollBack();

            if (isset($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyEmail($id, $hash, Request $request)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link.'], Response::HTTP_FORBIDDEN);
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], Response::HTTP_OK);
        }

        $user->markEmailAsVerified();
        $user->setStatus(UserStatus::PENDING_ONBOARDING);
        event(new UserRegistered($user));

        $token = $user->createToken(
            name: 'web-token',
        )->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], Response::HTTP_OK);
    }

    public function resendNotification(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], Response::HTTP_BAD_REQUEST);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link resent!']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return response()->json($user);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->status_id === UserStatus::PENDING_EMAIL->value) {
            return response()->json([
                'message' => 'Your account is pending email approval.'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->status_id === UserStatus::INACTIVE->value) {
            return response()->json([
                'message' => 'Your account has been deactivated.'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->status_id === UserStatus::BANNED->value) {
            return response()->json([
                'message' => 'Your account has been blocked. Contact support.'
            ], Response::HTTP_FORBIDDEN);
        }

        $user->load('roles');

        $token = $user->createToken('web-login')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.'], Response::HTTP_OK);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        event(new PasswordResetRequested($user));

        return response()->json([
            'message' => 'Reset link has been sent to your email address.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols()
            ],
        ]);

        $status = PasswordBroker::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'token' => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordChanged($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
                'status' => $status,
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}
