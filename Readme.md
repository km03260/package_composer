# SSO Authenticated Package

## Installation

```bash
composer require 213devops/ssoauthenticated:dev-master
```

## Publishing Assets

```bash
php artisan vendor:publish --tag=ssoauth-config
php artisan vendor:publish --tag=ssoauth-views
php artisan vendor:publish --tag=ssoauth-assets
php artisan vendor:publish --tag=ssoauth-routes
```

## Routes Configuration

### Add this line to routes/web.php:

```bash
require base_path('routes/ssoauthenticated.php');
```

## Environment Variables

### Add to .env file:

```bash
SSO_SERVER_URL=https://your-sso-server.com
SSO_CLIENT_ID=your_client_id
SSO_CLIENT_SECRET=your_client_secret

# Optional — required only for the "baof" check of the local login (see below).
# Requests whose IP differs from this value are blocked outside the premises.
IP_FACTORY_LOCATED=
```

## Local Login ("Connexion hors SSO")

The login page (`ssoauth::auth.login`) exposes a **Connexion hors SSO** button that
opens a modal and authenticates the user against the module's own user table —
bypassing the SSO server. It mirrors the SSO server's checks, in order:

1. **Password** — `Hash::check($password, $user->Mdp)`.
2. **dfa** (`$user->dfa == 1`) — device matching against the `user_matching` table.
   An unknown device gets a verification code e-mailed (`DeviceVerificationMail`);
   the modal resubmits with that code (valid 15 min) to trust the device.
3. **baof** (`$user->baof == 1`) — blocks the login when the request IP differs
   from `IP_FACTORY_LOCATED`.

The form posts to `route('auth.local.login')` and the user model is resolved from
`config('auth.providers.users.model')`, so no host code changes are required.

### Requirements

The `dfa` / `baof` checks **degrade gracefully**: when the supporting columns/table
are absent they are skipped and the login is password-only. To enable them, the host
application must provide:

- a `user_matching` table (columns: `user_id`, `user_agent`, `platform`, `device`,
  `version`, `matching_regex`, `ip_request`, `match_token`, `confirmed_at`, `created_at`);
- `dfa` and `baof` (tinyint/boolean) columns on the user table;
- `IP_FACTORY_LOCATED` in `.env` (for `baof`);
- a configured mailer (for the `dfa` verification e-mail).

> The `jenssegers/agent` dependency (used by device matching) is pulled in
> automatically via Composer.

## Logout

### Use this route for logout:

```bash
route('sso.logout')
```

## Main Layout Example

```bash
@extends('ssoauth-layout-main')

@section('content')
    <h1>Welcome, {{ $user->name }}</h1>
    <p>Your email: {{ $user->email }}</p>
    <p>Connected devices:</p>
    <ul>
        @foreach($user->matchings as $device)
            <li>{{ $device->platform }} - {{ $device->device }} (IP: {{ $device->ip_request }})</li>
        @endforeach
    </ul>
@endsection


@extends('ssoauth-layout-main')

@section('content')
    <h1>Welcome, {{ $user->name }}</h1>
    <p>Your email: {{ $user->email }}</p>
    <p>Connected devices:</p>
    <ul>
        @foreach($user->matchings as $device)
            <li>{{ $device->platform }} - {{ $device->device }} (IP: {{ $device->ip_request }})</li>
        @endforeach
    </ul>
@endsection

```
