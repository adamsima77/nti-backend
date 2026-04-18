<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Models\User;
use Illuminate\Auth\Events\PasswordReset;


class AuthController extends Controller
{
    public function register(Request $request){

    }

    public function login(Request $request){
        $validated = $request->validate([
            'email' =>  ['required', 'email','max:255'],
            'password' =>  ['required', 'string','max:255']
        ]);

        $user = User::where('email', $validated['email'])->first();
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

        $status = PasswordBroker::sendResetLink(
            $request->only('email')
        );
        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset link coudlnt be sent.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Reset link has been sent to your email address.'
        ], Response::HTTP_OK);
    }

    public function resetPassword(Request $request){
        $validated = $request->validate([
            'token' => ['required'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'email' => ['required', 'email']
        ]);
        $log_token = "";
        $status = PasswordBroker::reset(
            $validated,
            function (User $user, string $password) use (&$log_token) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
                $user->save();
                $user->tokens()->delete();
                $log_token = $user->createToken("web-login")->plainTextToken;
                //Event that notifies laravel internally
                event(new PasswordReset($user));
                //Notify notification class of change
                event(new PasswordChanged($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json(['message' => "Invalid or expired reset token."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return response()->json(['message' => 'Password reset successfully.', 'token' => $log_token], Response::HTTP_OK);
    }
}
