<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Response;
use Modules\IdentityAccess\Models\User;

class AuthController extends Controller
{
    public function register(Request $request){

    }

    public function login(Request $request){
        $validated = $request->validate([
            'email' =>  ['required', 'email', 'exists:users,email'],
            'password' =>  ['required', 'string']
        ]);

        $user = User::where('email', $validated['email'])->first();
        if(!$user || !Hash::check($validated['password'], $user->password)){
            return response()->json(['message' => "The provided credentials are incorrect."], Response::HTTP_UNAUTHORIZED);
        }
        $token = $user->createToken("nti-web-user-{$user->id}")->plainTextToken;
        return response()->json(['token' => $token, Response::HTTP_OK]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.'], Response::HTTP_OK);
    }

    public function forgotPassword(Request $request){
        $validated = $request->validate([]);
    }
}
