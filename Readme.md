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

# Optional — public path of the Gedivepro logo shown at the top of the modals.
# Defaults to images/Gedivepro_logo.png
SSO_LOGO_URL=images/Gedivepro_logo.png
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

A **Renvoyer le code** button re-requests a fresh verification code via
`route('auth.local.resend')`. The Gedivepro logo (see `SSO_LOGO_URL`) is shown at the
top of the QR and local-login modals. The login modal is available on both the
`auth.login` and `auth.prelogin` views.

The **QR-code login** (`auth.qr.authentication`) enforces the same matching / dfa /
baof checks. When dfa is required it redirects to a verification page
(`ssoauth::auth.qr-verify`) where the user enters the e-mailed code (with a resend
button), then completes the login.

> Internal links use named routes (`route('auth.qr.authentication')`, etc.) because the
> package routes are served under the `sso` prefix. After upgrading the package,
> re-publish the routes: `php artisan vendor:publish --tag=ssoauth-routes --force`.

> **No e-mail received?** The verification mail uses the application's default
> `MAIL_FROM_ADDRESS`. Make sure the mailer is configured; send failures are written to
> `storage/logs` (`Local login dfa: failed to send verification code`).

### Requirements

The `user_matching` table (used to record/trust devices) ships as a package migration
and is loaded automatically — run it after install:

```bash
php artisan migrate
```

The `dfa` / `baof` checks **degrade gracefully**: when the supporting columns are absent
they are skipped and the login is password-only. To enable them, the host application
must also provide:

- `dfa` and `baof` (tinyint/boolean) columns on the user table (no row written to
  `user_matching` unless `dfa = 1` — device matching is part of the dfa check);
- `IP_FACTORY_LOCATED` in `.env` (for `baof`; when unset, `baof` never blocks);
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
