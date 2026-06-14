<?php

namespace DevOps213\SSOauthenticated\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DevOps213\SSOauthenticated\Http\Controllers\Concerns\InteractsWithDeviceSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Local ("Connexion hors SSO") login.
 *
 * Mirrors the SSO server's SsoAuthController::handlePopupLogin authentication
 * checks — password, dfa (device matching + e-mail verification code) and baof
 * (factory-IP block) — but authenticates against the module's own user table
 * with Auth::login instead of issuing an SSO token.
 */
class LocalLoginController extends Controller
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

    /**
     * Handle the local (SSO-bypass) login submission.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'Email' : 'Login';

        $model = $this->userModel();
        $user = $model::where($field, $request->login)->first();

        // Find user + verify password
        if (!$user || !Hash::check($request->password, $user->Mdp)) {
            return response()->json([
                'success' => false,
                'message' => __('The provided credentials are incorrect.'),
            ], 401);
        }

        // --- dfa: double authentication via device matching + e-mail code ---
        if (($user->dfa ?? 0) == 1) {
            if ($request->code && !$this->verifyDeviceCode($request->code)) {
                return response()->json([
                    'success' => false,
                    'verify' => true,
                    'message' => __('Le code est invalide.'),
                ], 401);
            }

            if ($this->checkDFA($request, $user) !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'verify' => true,
                    'message' => __('Before proceeding, please check your email for a verification link.'),
                ], 401);
            }
        }

        // --- baof: block access outside the Gedivepro premises ---
        if (($user->baof ?? 0) == 1) {
            if ($this->checkApiFactoryLocated($request, $user) !== 'located') {
                return response()->json([
                    'success' => false,
                    'located' => true,
                    'message' => __('Accès bloqué en dehors des locaux de Gedivepro'),
                ], 401);
            }
        }

        Auth::login($user, $request->boolean('remember'));

        // Optional login bookkeeping — never block the login if a column is absent.
        try {
            $user->DateDernierLogin = Carbon::now()->format('Y-m-d H:i');
            $user->NombreDeLogins = ($user->NombreDeLogins ?? 0) + 1;
            $user->save();
        } catch (\Throwable $th) {
        }

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => __('Login successful'),
            'redirect_url' => session('url.intended', url('/')),
        ]);
    }

    /**
     * Resend the dfa verification code for the current device.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'Email' : 'Login';

        $model = $this->userModel();
        $user = $model::where($field, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->Mdp)) {
            return response()->json([
                'success' => false,
                'message' => __('The provided credentials are incorrect.'),
            ], 401);
        }

        if (!$this->issueVerificationCode($request, $user)) {
            return response()->json([
                'success' => false,
                'verify' => true,
                'message' => __("Impossible d'envoyer le code. Réessayez plus tard."),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'verify' => true,
            'message' => __('Un nouveau code de vérification a été envoyé.'),
        ]);
    }
}
