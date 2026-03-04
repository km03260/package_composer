<?php

use Illuminate\Support\Facades\Route;
use Gedivepro\UserProfile\Http\Controllers\SsoLoginController;

Route::middleware('web')->group(function () {
    Route::get('/sso/login', [SsoLoginController::class, 'login'])->name('sso.login');
    Route::get('/profile', [SsoLoginController::class, 'profile'])->name('profile');
});
