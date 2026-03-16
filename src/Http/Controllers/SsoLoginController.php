<?php

namespace DevOps213\SSOauthenticated\Http\Controllers;

use App\Http\Controllers\Controller;
use DevOps213\SSOauthenticated\Models\SsoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class SsoLoginController extends Controller
{
    public function login()
    {
        return view('ssoauth::auth.login');
    }

    public function authentication(Request $request)
    {
        try {

            $user = (new SsoToken)::GetTokenRelatedUser($request->token)?->user;

            Auth::login($user);

            $url = session('url.intended');

            $path = Str::after($url, url('/'));

            $_redirect = $request->redirect_url ?? $path ?? '/';
            return redirect($_redirect);
        } catch (\Throwable $th) {
            return redirect('/login');
        }
    }

    public function callback()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return view("auth/profile", compact('user'));
        }
        return view("auth/login");
    }
}
