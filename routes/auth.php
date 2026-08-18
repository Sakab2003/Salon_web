<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Check if registration is enabled
if (user_registration()) {
    Route::middleware('guest')->group(function () {
        Route::get('inscription', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('inscription', [RegisteredUserController::class, 'store']);
        Route::get('register', function() { return redirect()->route('register'); });
    });
}

Route::middleware('guest')->group(function () {
    Route::get('connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('connexion', [AuthenticatedSessionController::class, 'store']);
    Route::get('login', function() { return redirect()->route('login'); });

    Route::get('mot-de-passe-oublie', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('forgot-password', function() { return redirect()->route('password.request'); });

    Route::get('reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verifier-email', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    Route::get('verifier-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'store']);

    Route::post('deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
});

// Social Login Routes
Route::group(['namespace' => 'Auth', 'middleware' => 'guest'], function () {
    Route::get('connexion/{provider}', [SocialLoginController::class, 'redirectToProvider'])->name('social.login');
    Route::get('connexion/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback'])->name('social.login.callback');
});
