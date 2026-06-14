<?php

use Illuminate\Support\Facades\Route;
use DevOps213\SSOauthenticated\Http\Controllers\SsoLoginController;
use DevOps213\SSOauthenticated\Http\Controllers\LocalLoginController;

Route::middleware('web')->group(function () {
    Route::get('/sso/login', [SsoLoginController::class, 'login'])->name('sso.login');

    // Local ("Connexion hors SSO") login — password + dfa + baof checks.
    Route::post('/auth/local/login', [LocalLoginController::class, 'handleLogin'])
        ->name('auth.local.login');
    Route::post('/auth/local/resend', [LocalLoginController::class, 'resendCode'])
        ->name('auth.local.resend');
    Route::post('/sso/logout', [SsoLoginController::class, 'logout'])->name('sso.logout');
    Route::get('/auth/sso/callback', [SsoLoginController::class, 'callback'])->name('sso.callback');
    Route::get('/profile', [SsoLoginController::class, 'profile'])->name('profile');
    Route::get('/auth/authentication', [SsoLoginController::class, 'authentication'])
        ->name('auth.sso.authentication');
    Route::get('/auth/qr-authentication', [SsoLoginController::class, 'qrAuthentication'])
        ->name('auth.qr.authentication');


});
