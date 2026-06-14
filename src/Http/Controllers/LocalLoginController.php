<?php

namespace DevOps213\SSOauthenticated\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DevOps213\SSOauthenticated\Mail\DeviceVerificationMail;
use DevOps213\SSOauthenticated\Models\MatchingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

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
            if ($request->code) {
                $_match = MatchingUser::where('match_token', $request->code)->first();

                if (!$_match) {
                    return response()->json([
                        'success' => false,
                        'verify' => true,
                        'message' => __('Le code est invalide.'),
                    ], 401);
                } elseif (Carbon::now()->tz('Africa/Algiers')->diffInMinutes($_match->created_at) < 15) {
                    $_match->update([
                        'match_token' => null,
                        'confirmed_at' => Carbon::now()->tz('Africa/Algiers'),
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'verify' => true,
                        'message' => __('Le code est invalide.'),
                    ], 401);
                }
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
     * Check double authentication: confirm the current device is trusted,
     * otherwise create a pending match and e-mail a verification code.
     *
     * @param Request $request
     * @param mixed $user
     * @return string 'confirmed' when the device is trusted, 'verify' otherwise
     */
    private function checkDFA(Request $request, $user): string
    {
        $_matches = $this->agentMatching($request, $user->id);
        unset($_matches['ip_request']);

        if (MatchingUser::where('user_id', $user->id)->exists()) {
            $confirmed = MatchingUser::where('user_id', $user->id)
                ->whereNotNull('confirmed_at')
                ->where($_matches)
                ->exists();

            if (!$confirmed) {
                try {
                    MatchingUser::updateOrCreate($_matches, array_merge(
                        $this->agentMatching($request, $user->id),
                        ['match_token' => Str::random(10)]
                    ));

                    $_match = MatchingUser::where($_matches)->first();

                    Mail::to($user->Email)->send(new DeviceVerificationMail($_match));
                } catch (\Throwable $th) {
                }

                return 'verify';
            }

            return 'confirmed';
        }

        // First device for this user is trusted automatically.
        MatchingUser::create(array_merge(
            $this->agentMatching($request, $user->id),
            ['confirmed_at' => Carbon::now()]
        ));

        return 'confirmed';
    }

    /**
     * Build the device fingerprint used for matching.
     *
     * @param Request $request
     * @param int|null $userId
     * @return array
     */
    protected function agentMatching(Request $request, $userId): array
    {
        $agent = new Agent();
        $platform = $agent->platform();
        $_head = $agent->getHttpHeaders();

        return [
            'user_id' => $userId,
            'user_agent' => array_key_exists('HTTP_SEC_CH_UA', $_head)
                ? $_head['HTTP_SEC_CH_UA']
                : ($_head['HTTP_USER_AGENT'] ?? null),
            'platform' => $platform,
            'device' => $agent->isDesktop()
                ? (array_values($agent->getDesktopDevices())[0] ?? null)
                : $agent->device(),
            'version' => $agent->isDesktop() ? null : $agent->version($platform),
            'matching_regex' => $agent->getMatchesArray()[0] ?? null,
            'ip_request' => $this->ip(),
        ];
    }

    /**
     * Resolve the client IP address.
     *
     * @return string
     */
    private function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Block access outside the factory premises.
     *
     * @param Request $request
     * @param mixed $user
     * @return string 'located' when allowed
     */
    protected function checkApiFactoryLocated(Request $request, $user): string
    {
        $ipRequest = $this->agentMatching($request, $user->id)['ip_request'];

        if (env('IP_FACTORY_LOCATED')) {
            return env('IP_FACTORY_LOCATED') == $ipRequest ? 'located' : 'outside';
        }

        return 'located';
    }
}
