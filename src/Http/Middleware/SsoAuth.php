<?php

namespace DevOps213\SSOauthenticated\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SsoAuth
{

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        // Get token from multiple sources
        $token = $request->bearerToken()
            ?? $request->input('access_token')
            ?? $request->cookie('sso_access_token')
            ?? session('sso_access_token');

        if (!$token) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Store intended URL for redirect after login
            session(['url.intended' => $request->fullUrl()]);

            // Return view with login button
            return response()->view('auth.login');
        }

        // Verify token with SSO server
        $response = Http::timeout(10)->post(config('sso.server_url') . '/api/sso/verify', [
            'token' => $token,
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret')
        ]);

        if (!$response->successful() || !$response->json()['valid']) {
            // Clear invalid token
            session()->forget('sso_access_token');
            setcookie('sso_access_token', '', time() - 3600, '/');

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            return redirect()->route('login');
        }

        // Store user info
        $userData = $response->json()['user'];
        $request->merge(['sso_user' => $userData]);

        // Store in session for blade views
        session(['sso_user' => $userData]);
        session(['sso_access_token' => $token]);

        return $next($request);
    }
}
