<?php

namespace Gedivepro\UserProfile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SsoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SsoLoginController extends Controller
{
    public function login()
    {
        return view('auth/login');
    }

    public function authentication(Request $request)
    {
        try {

            $user = (new SsoToken)::GetTokenRelatedUser($request->token)?->user;

            Auth::login($user);

            return redirect('/dashboard');
        } catch (\Throwable $th) {
            return redirect('/login');
        }
    }

}
