<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request){
          $validated = $request->validate([]);
    }

    public function login(Request $request){
        $validated = $request->validate([]);
    }

    public function logout(Request $request){
        $validated = $request->validate([]);
    }

    public function forgotPassword(Request $request){
        $validated = $request->validate([]);
    }
}
