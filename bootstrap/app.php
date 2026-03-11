<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogWebhookInteraction;
use App\Http\Middleware\TrackUserActivity;
use App\Http\Middleware\ValidateWebhookToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'track.activity' => TrackUserActivity::class,
            'webhook.token' => ValidateWebhookToken::class,
            'webhook.log' => LogWebhookInteraction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
