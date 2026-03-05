composer require 213devops/ssoauthenticated:dev-master

php artisan vendor:publish --tag=ssoauth-config
php artisan vendor:publish --tag=ssoauth-views
php artisan vendor:publish --tag=ssoauth-assets

php artisan vendor:publish --tag=sso-routes
require base_path('routes/ssoauthenticated.php');

SSO_SERVER_URL=https://your-sso-server.com
SSO_CLIENT_ID=your_client_id
SSO_CLIENT_SECRET=your_client_secret

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