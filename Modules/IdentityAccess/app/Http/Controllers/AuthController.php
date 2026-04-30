<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\IdentityAccess\Events\UserRegistered;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Enums\UserStatus;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // TODO
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

        $user->update(['status_id' => UserStatus::ACTIVE->value]);

        if ($user->hasVerifiedEmail()) {
            event(new UserRegistered($user));
        }

        return response()->json(['message' => 'Email verified successfully. You can now log in.'], Response::HTTP_OK);
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

        if ($user->status_id === UserStatus::PENDING->value) {
            return response()->json([
                'message' => 'Your account is pending approval.'
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
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $status = PasswordBroker::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->tokens()->delete();

                event(new PasswordChanged($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Invalid or expired reset token.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Password reset successfully.'
        ], Response::HTTP_OK);
    }
}
