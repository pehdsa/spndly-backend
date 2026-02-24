<?php

use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', RegisterController::class)->name('api.v1.auth.register');

Route::middleware('throttle:10,1')->group(function (): void {
    Route::get('invitations/validate', [InvitationController::class, 'validateToken'])->name('api.v1.invitations.validate');
});

Route::middleware('auth:api')->group(function (): void {
    Route::post('auth/logout', LogoutController::class)->name('api.v1.auth.logout');
});

Route::middleware(['auth:api', 'active', 'track.activity'])->group(function (): void {
    Route::get('auth/me', MeController::class)->name('api.v1.auth.me');

    Route::middleware('can:admin')->group(function (): void {
        Route::apiResource('users', UserController::class)->only(['index', 'show', 'destroy']);
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('api.v1.users.block');
        Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('api.v1.users.unblock');
        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('api.v1.users.update-role');

        Route::get('invitations', [InvitationController::class, 'index'])->name('api.v1.invitations.index');
        Route::post('invitations', [InvitationController::class, 'store'])->name('api.v1.invitations.store');
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('api.v1.invitations.destroy');
    });
});
