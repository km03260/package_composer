<?php

namespace DevOps213\SSOauthenticated\Http\Controllers\Concerns;

use Carbon\Carbon;
use DevOps213\SSOauthenticated\Mail\DeviceVerificationMail;
use DevOps213\SSOauthenticated\Models\MatchingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * Device-security checks shared by the local ("Connexion hors SSO") and QR login
 * flows — mirrors the SSO server's matching / dfa / baof logic.
 */
trait InteractsWithDeviceSecurity
{
    /**
     * Verify a dfa code: confirm the matching device when the code is valid
     * (and emitted less than 15 minutes ago).
     *
     * @param string|null $code
     * @return bool
     */
    protected function verifyDeviceCode($code): bool
    {
        if (!$code) {
            return false;
        }

        $_match = MatchingUser::where('match_token', $code)->first();

        if ($_match && Carbon::now()->tz('Africa/Algiers')->diffInMinutes($_match->created_at) < 15) {
            $_match->update([
                'match_token' => null,
                'confirmed_at' => Carbon::now()->tz('Africa/Algiers'),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check double authentication: confirm the current device is trusted,
     * otherwise create a pending match and e-mail a verification code.
     *
     * @param Request $request
     * @param mixed $user
     * @return string 'confirmed' when the device is trusted, 'verify' otherwise
     */
    protected function checkDFA(Request $request, $user): string
    {
        $_matches = $this->agentMatching($request, $user->id);
        unset($_matches['ip_request']);

        if (MatchingUser::where('user_id', $user->id)->exists()) {
            $confirmed = MatchingUser::where('user_id', $user->id)
                ->whereNotNull('confirmed_at')
                ->where($_matches)
                ->exists();

            if (!$confirmed) {
                $_match = MatchingUser::updateOrCreate($_matches, array_merge(
                    $this->agentMatching($request, $user->id),
                    ['match_token' => Str::random(10)]
                ));

                $this->sendVerificationCode($user, $_match);

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

    /**
     * E-mail the device verification code, logging any failure.
     *
     * @param mixed $user
     * @param MatchingUser $match
     * @return bool
     */
    protected function sendVerificationCode($user, MatchingUser $match): bool
    {
        if (empty($user->Email)) {
            Log::warning('Device security: user has no e-mail address.', ['user_id' => $user->id]);
            return false;
        }

        try {
            Mail::to($user->Email)->send(new DeviceVerificationMail($match));
            return true;
        } catch (\Throwable $th) {
            Log::error('Device security: failed to send verification code.', [
                'user_id' => $user->id,
                'error' => $th->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refresh (or create) the pending match for the current device with a new code
     * and e-mail it. Returns false when the mail could not be sent.
     *
     * @param Request $request
     * @param mixed $user
     * @return bool
     */
    protected function issueVerificationCode(Request $request, $user): bool
    {
        $_matches = $this->agentMatching($request, $user->id);
        unset($_matches['ip_request']);

        $match = MatchingUser::updateOrCreate($_matches, array_merge(
            $this->agentMatching($request, $user->id),
            ['match_token' => Str::random(10)]
        ));

        return $this->sendVerificationCode($user, $match);
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
    protected function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
