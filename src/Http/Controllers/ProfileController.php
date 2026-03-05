<?php

namespace DevOps213\SSOauthenticated\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    private $ssoServerUrl;

    public function __construct()
    {
        $this->ssoServerUrl = config('sso.server_url');
    }

    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return view("auth/profile", compact('user'));
        }
        return view("auth/login");
    }

    public function show()
    {
        $user = Session::get('sso_user');

        // Fetch additional user info from SSO server if needed
        try {
            $response = Http::withToken(Session::get('sso_access_token'))
                ->get($this->ssoServerUrl . '/api/sso/user/profile');

            if ($response->successful()) {
                $user = array_merge($user, $response->json());
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return view('profile', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        try {
            $response = Http::withToken(Session::get('sso_access_token'))
                ->put($this->ssoServerUrl . '/api/sso/user/profile', [
                    'name' => $request->name,
                    'email' => $request->email,
                ]);

            if ($response->successful()) {
                // Update local session
                $user = Session::get('sso_user', []);
                $user['name'] = $request->name;
                $user['email'] = $request->email;
                Session::put('sso_user', $user);

                return redirect()->route('profile')->with('success', 'Profile updated successfully');
            }

            return back()->with('error', 'Failed to update profile');
        } catch (\Exception $e) {
            return back()->with('error', 'Connection error: ' . $e->getMessage());
        }
    }
}
