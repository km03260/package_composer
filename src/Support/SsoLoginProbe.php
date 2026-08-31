<?php

namespace DevOps213\SSOauthenticated\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * Mouchard de la boucle « connexion SSO reussie -> retour sur /login ».
 *
 * Quand `Auth::login()` echoue, le controleur le loggue. Mais quand la
 * connexion reussit et que l'utilisateur retombe quand meme sur /login,
 * rien n'est trace : la perte se produit *entre* deux requetes, et c'est
 * exactement le cas qui n'apparait qu'en production (domaine, HTTPS,
 * plusieurs conteneurs php, APP_KEY differente...).
 *
 * La sonde marque la requete de connexion, puis explique, a la requete
 * suivante, ce qui n'a pas survecu.
 *
 * Activation : SSO_DEBUG=true. Sans elle, `mark()` et `diagnose()` ne font
 * rien et rien n'est ni ecrit ni affiche.
 */
class SsoLoginProbe
{
    public const KEY = 'sso_login_probe';

    public static function enabled(): bool
    {
        return (bool) config('sso.debug', false);
    }

    /**
     * Marquer une connexion reussie, en session ET dans un cookie a part.
     *
     * Le cookie temoin est volontairement pose sans attribut Domain et sans
     * Secure : il revient donc dans tous les cas ou le navigateur accepte le
     * moindre cookie. Comparer sa presence a celle du cookie de session
     * separe « le navigateur jette nos cookies » de « le cookie de session
     * n'est pas valable sur ce host ».
     */
    public static function mark(Request $request, $user): void
    {
        if (!self::enabled()) {
            return;
        }

        $data = [
            'at'         => now()->toDateTimeString(),
            'user_id'    => $user->getKey(),
            'host'       => $request->getHost(),
            'scheme'     => $request->getScheme(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
        ];

        if ($request->hasSession()) {
            $request->session()->put(self::KEY, $data);
        }

        Cookie::queue(Cookie::make(
            self::KEY,
            base64_encode(json_encode($data)),
            10,      // minutes
            '/',
            null,    // aucun Domain : cookie host-only
            false,   // pas de Secure : revient aussi en http
            true,
            false,
            'lax'
        ));

        Log::info('SSO probe: Auth::login OK', $data + [
            'auth_check'     => Auth::check(),
            'session_cookie' => config('session.cookie'),
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
        ]);
    }

    /**
     * Expliquer pourquoi la requete courante repart en invite.
     *
     * @return array{verdict: string, detail: string, contexte: array}|null
     *         null quand aucune connexion recente n'a ete marquee (visite
     *         normale de /login, rien a diagnostiquer).
     */
    public static function diagnose(Request $request): ?array
    {
        if (!self::enabled()) {
            return null;
        }

        $raw = $request->cookies->get(self::KEY);
        $temoin = $raw ? json_decode(base64_decode($raw), true) : null;
        $enSession = $request->hasSession() ? $request->session()->get(self::KEY) : null;

        if (!$temoin && !$enSession) {
            return null;
        }

        $connexion = $enSession ?: $temoin;
        $cookieSession = config('session.cookie');
        $sessionRecue = $request->cookies->has($cookieSession);
        $hostActuel = $request->getHost();

        $contexte = [
            'host_connexion'      => $connexion['host'] ?? null,
            'host_actuel'         => $hostActuel,
            'scheme_connexion'    => $connexion['scheme'] ?? null,
            'scheme_actuel'       => $request->getScheme(),
            'session_connexion'   => $connexion['session_id'] ?? null,
            'session_actuelle'    => $request->hasSession() ? $request->session()->getId() : null,
            'cookie_temoin_recu'  => (bool) $temoin,
            'cookie_session_recu' => $sessionRecue,
            'nom_cookie_session'  => $cookieSession,
            'domaine_cookie'      => config('session.domain'),
            'cookie_secure'       => config('session.secure'),
            'driver_session'      => config('session.driver'),
            'user_id'             => $connexion['user_id'] ?? null,
            'auth_check'          => Auth::check(),
        ];

        // L'ordre des tests va de la cause la plus englobante a la plus fine.
        if (($connexion['host'] ?? $hostActuel) !== $hostActuel) {
            return [
                'verdict' => 'CHANGEMENT DE DOMAINE',
                'detail'  => "La connexion a eu lieu sur « {$connexion['host']} » et le retour arrive sur « {$hostActuel} ». "
                    . "Le cookie de session est pose sur le premier host et n'est pas renvoye sur le second. "
                    . "Corrige APP_URL et/ou SESSION_DOMAIN pour couvrir les deux, ou fais en sorte que le SSO "
                    . "revienne toujours sur le meme host.",
                'contexte' => $contexte,
            ];
        }

        if (!$temoin && !$sessionRecue) {
            return [
                'verdict' => 'AUCUN COOKIE RENVOYE',
                'detail'  => "Ni le cookie temoin ni le cookie de session ne reviennent, alors que la connexion "
                    . "a bien eu lieu il y a peu. Deux familles de causes : le navigateur ne les renvoie pas "
                    . "(SESSION_SECURE_COOKIE face au scheme reel, SameSite, ou un proxy qui supprime Set-Cookie), "
                    . "ou bien ils reviennent mais EncryptCookies n'arrive plus a les dechiffrer -- ce qui arrive "
                    . "quand APP_KEY a change, ou differe d'un conteneur php a l'autre.",
                'contexte' => $contexte,
            ];
        }

        if ($temoin && !$sessionRecue) {
            return [
                'verdict' => 'COOKIE DE SESSION REJETE',
                'detail'  => "Le cookie temoin (sans Domain, sans Secure) revient, mais pas le cookie de session "
                    . "« {$cookieSession} ». La difference vient de ses attributs : SESSION_DOMAIN = "
                    . var_export(config('session.domain'), true) . ", Secure = "
                    . var_export(config('session.secure'), true) . ", SameSite = "
                    . var_export(config('session.same_site'), true) . ".",
                'contexte' => $contexte,
            ];
        }

        if (!$enSession) {
            return [
                'verdict' => 'SESSION VIDE MALGRE LE COOKIE',
                'detail'  => "Le cookie de session revient, mais son contenu est perdu. Avec le driver « "
                    . config('session.driver') . " » : soit storage/framework/sessions n'est pas inscriptible, "
                    . "soit plusieurs conteneurs php se partagent le trafic sans partager le stockage de session "
                    . "(passe alors en driver redis ou database).",
                'contexte' => $contexte,
            ];
        }

        return [
            'verdict' => 'SESSION OK MAIS AUTH PERDUE',
            'detail'  => "La session a survecu (meme identifiant, donnees presentes) mais le garde d'authentification "
                . "ne retrouve pas l'utilisateur #" . ($connexion['user_id'] ?? '?') . ". "
                . "Verifie que cet id existe bien dans la table de " . config('auth.providers.users.model')
                . " en prod, et qu'aucun Auth::logout() ne s'execute entre-temps.",
            'contexte' => $contexte,
        ];
    }
}
