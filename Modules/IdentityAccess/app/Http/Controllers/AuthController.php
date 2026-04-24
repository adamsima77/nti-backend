<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\IdentityAccess\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\Notifications\Emails\ResetPasswordMail;


class AuthController extends Controller
{
    public function register(Request $request){
        //TODO




        //$user->setStatus(UserStatus::PENDING);
        //event(new Registered($user));
    }

    public function verifyEmail($id, $hash, Request $request)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], Response::HTTP_FORBIDDEN);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['message' => 'Email verified successfully!']);
    }

    public function resendNotification(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link resent!']);
    }


    public function me(Request $request){
        return $request->user();
    }

    public function login(Request $request){
        $validated = $request->validate([
            'email' =>  ['required', 'email','max:255'],
            'password' =>  ['required', 'string','max:255']
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email first.'
            ], 403);
        }

        if(!$user || !Hash::check($validated['password'], $user->password)){
            return response()->json(['message' => "The provided credentials are incorrect."], Response::HTTP_UNAUTHORIZED);
        }
        $token = $user->createToken("web-login")->plainTextToken;
        return response()->json(['token' => $token], Response::HTTP_OK);
    }

    public function logout(Request $request){
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
            return response()->json([
                'message' => 'User not found.'
            ], 404);
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
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'email' => ['required', 'email']
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
                'message' => "Invalid or expired reset token."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Password reset successfully.'
        ], Response::HTTP_OK);
    }
}
