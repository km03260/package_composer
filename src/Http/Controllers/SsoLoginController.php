<?php

namespace DevOps213\SSOauthenticated\Http\Controllers;

use App\Http\Controllers\Controller;
use DevOps213\SSOauthenticated\Http\Controllers\Concerns\InteractsWithDeviceSecurity;
use DevOps213\SSOauthenticated\Models\SsoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class SsoLoginController extends Controller
{
    use InteractsWithDeviceSecurity;

    /**
     * Resolve the application's configured authenticatable model.
     *
     * @return class-string
     */
    protected function userModel(): string
    {
        return config('auth.providers.users.model', \App\Models\User::class);
    }

    public function login()
    {
        return view('ssoauth::auth.login');
    }

    /**
     * logout
     * @param Request $request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return view("ssoauth::auth.prelogin");
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

    /**
     * qrAuthentication
     * Logs the user in by resolving the `usersso` identifier scanned from a QR code.
     * @param Request $request
     */
    public function qrAuthentication(Request $request)
    {
        try {
            $model = $this->userModel();
            $user = $model::where('usersso', $request->usersso)->first();

            if (!$user) {
                return redirect('/login')->withErrors(['usersso' => 'QR code invalide ou utilisateur introuvable.']);
            }

            // --- dfa: device matching + e-mail verification code ---
            if (($user->dfa ?? 0) == 1) {
                if ($request->code && !$this->verifyDeviceCode($request->code)) {
                    return view('ssoauth::auth.qr-verify', [
                        'usersso' => $request->usersso,
                        'error' => __('Le code est invalide.'),
                    ]);
                }

                if ($this->checkDFA($request, $user) !== 'confirmed') {
                    return view('ssoauth::auth.qr-verify', [
                        'usersso' => $request->usersso,
                        'error' => null,
                    ]);
                }
            }

            // --- baof: block access outside the Gedivepro premises ---
            if (($user->baof ?? 0) == 1 && $this->checkApiFactoryLocated($request, $user) !== 'located') {
                return redirect('/login')->withErrors([
                    'usersso' => __('Accès bloqué en dehors des locaux de Gedivepro'),
                ]);
            }

            Auth::login($user);

            $url = session('url.intended');

            $path = Str::after($url, url('/'));

            $_redirect = $request->redirect_url ?? $path ?? '/';
            return redirect($_redirect);
        } catch (\Throwable $th) {
            Log::error('QR authentication failed.', [
                'usersso' => $request->usersso,
                'error' => $th->getMessage(),
            ]);
            return redirect('/login');
        }
    }

    /**
     * Resend the QR-login dfa verification code for the current device.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function qrResendCode(Request $request)
    {
        $request->validate(['usersso' => 'required|string']);

        $model = $this->userModel();
        $user = $model::where('usersso', $request->usersso)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('Utilisateur introuvable.'),
            ], 404);
        }

        if (!$this->issueVerificationCode($request, $user)) {
            return response()->json([
                'success' => false,
                'message' => __("Impossible d'envoyer le code. Réessayez plus tard."),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Un nouveau code de vérification a été envoyé.'),
        ]);
    }

    /**
     * callback
     * @param Request $request
     */
    public function callback(Request $request)
    {
        $redirect = Session::get('pre_url') ?? '/';

        if (Auth::check()) {
            $user = Auth::user();
            return view("ssoauth::auth.profile", compact('user', 'redirect'));
        } elseif ($request->has('accessToken')) {
            $user = (new SsoToken)::GetTokenRelatedUser($request->accessToken)?->user;

            Auth::login($user);
            return view("ssoauth::auth.profile", compact('user', 'redirect'));

        }
        return view("ssoauth::auth.login", compact('redirect'));
    }
}
