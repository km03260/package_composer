<?php

use Illuminate\Support\Facades\Route;
use DevOps213\SSOauthenticated\Http\Controllers\SsoLoginController;

Route::middleware('web')->group(function () {
    Route::get('/sso/login', [SsoLoginController::class, 'login'])->name('sso.login');
    Route::post('/sso/logout', [SsoLoginController::class, 'logout'])->name('sso.logout');
    Route::get('/auth/sso/callback', [SsoLoginController::class, 'callback'])->name('sso.callback');
    Route::get('/profile', [SsoLoginController::class, 'profile'])->name('profile');
    Route::get('/auth/authentication', [SsoLoginController::class, 'authentication'])
        ->name('auth.sso.authentication');


});
