<?php

namespace DevOps213\SSOauthenticated\Http\Middleware;

use Closure;
use DevOps213\SSOauthenticated\Support\SessionDomain;
use Illuminate\Http\Request;

/**
 * SESSION_DOMAIN may list several domains separated by commas. A cookie can
 * only hold one of them, so the domain matching the host of the current
 * request is applied before any session or queued cookie is created.
 *
 * Registered on the global stack by SSOAuthenticatedServiceProvider, after
 * TrustProxies so the host is the real one, and before the web group starts
 * the session.
 */
class ResolveSessionDomain
{
    public function handle(Request $request, Closure $next)
    {
        $config = app('config');

        $domain = SessionDomain::resolve($config->get('session.domain'), $request->getHost());

        $config->set('session.domain', $domain);

        // The cookie jar may already have been resolved with the raw value,
        // so its defaults are realigned with the domain of this request.
        if (app()->resolved('cookie')) {
            cookie()->setDefaultPathAndDomain(
                $config->get('session.path', '/'),
                $domain,
                $config->get('session.secure', false),
                $config->get('session.same_site')
            );
        }

        return $next($request);
    }
}
