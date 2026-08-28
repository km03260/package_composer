<?php

namespace DevOps213\SSOauthenticated\Support;

/**
 * Resolve the session cookie domain when SESSION_DOMAIN holds several
 * patterns separated by commas, e.g.
 *
 *     SESSION_DOMAIN=.gedivepro.test,.local,*
 *
 * A cookie can only carry one domain, so the pattern matching the host of
 * the current request is the one used. Three forms are accepted:
 *
 *   .gedivepro.test  a literal domain, used as-is for any host below it;
 *   .local           a single label suffix: the domain is rebuilt one level
 *                    below it, ".local" + "sso.geditex.local" -> ".geditex.local",
 *                    because a single label is a public suffix a browser
 *                    would refuse as a cookie domain;
 *   *                any host, with no Domain attribute at all, so the
 *                    browser scopes the cookie to the host itself.
 *
 * When nothing matches, null is returned and the cookie stays host-only
 * rather than carrying a domain the browser would reject.
 */
class SessionDomain
{
    /**
     * Split a raw SESSION_DOMAIN value into its individual patterns.
     *
     * @return array<int, string>
     */
    public static function candidates($configured): array
    {
        if (!is_string($configured) || trim($configured) === '') {
            return [];
        }

        $patterns = array_map('trim', explode(',', $configured));

        return array_values(array_filter($patterns, fn($pattern) => $pattern !== ''));
    }

    /**
     * Pick the cookie domain the given host should use.
     *
     * @param  string|null  $configured  raw SESSION_DOMAIN value
     * @param  string|null  $host        host of the current request
     */
    public static function resolve($configured, $host): ?string
    {
        $patterns = self::candidates($configured);

        if ($patterns === []) {
            return null;
        }

        $host = self::normalizeHost($host);

        if ($host === null) {
            return null;
        }

        // false means "nothing matched yet"; null means "matched, but the
        // cookie must stay host-only". The two cannot be merged, otherwise a
        // wildcard would silently read as a failure.
        $match = false;

        foreach ($patterns as $pattern) {
            $domain = self::expand($pattern, $host);

            if ($domain === false) {
                continue;
            }

            // A wildcard is the weakest match: it only wins when no literal
            // domain applies to this host.
            if ($domain === null) {
                if ($match === false) {
                    $match = null;
                }

                continue;
            }

            // Keep the most specific domain when several ones match
            // (e.g. .app.gedivepro.com wins over .gedivepro.com).
            if (!is_string($match) || strlen($domain) > strlen($match)) {
                $match = $domain;
            }
        }

        return $match === false ? null : $match;
    }

    /**
     * Turn one pattern into the domain it yields for the given host.
     *
     * @return string|null|false  the domain, null for a host-only cookie,
     *                            false when the pattern does not match
     */
    protected static function expand(string $pattern, string $host)
    {
        if ($pattern === '*' || $pattern === '.*') {
            return null;
        }

        $bare = strtolower(ltrim($pattern, '.*'));

        if ($bare === '' || ($host !== $bare && !str_ends_with($host, '.' . $bare))) {
            return false;
        }

        // A literal domain already carries a registrable part.
        if (str_contains($bare, '.')) {
            return '.' . $bare;
        }

        // A single label is a public suffix: rebuild the domain one level
        // below it so the browser accepts it.
        $labels = explode('.', $host);

        if (count($labels) < 2) {
            return false;
        }

        return '.' . implode('.', array_slice($labels, -2));
    }

    /**
     * Strip the port and normalize the case of a host.
     */
    protected static function normalizeHost($host): ?string
    {
        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        $host = strtolower(trim($host));

        // Drop the port, keeping IPv6 literals such as [::1]:8000 intact.
        if (str_starts_with($host, '[')) {
            $host = strtok($host, ']') . ']';
        } elseif (str_contains($host, ':')) {
            $host = strtok($host, ':');
        }

        return trim($host, '.') ?: null;
    }
}
