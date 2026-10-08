<?php

use App\Http\Middleware\VerifyMetaSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Meta sends server-to-server POST requests without a browser CSRF token.
        // The webhook still requires Meta's X-Hub-Signature-256 validation below.
        $middleware->validateCsrfTokens(except: ['webhooks/meta']);
        $middleware->alias(['meta.signature' => VerifyMetaSignature::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
