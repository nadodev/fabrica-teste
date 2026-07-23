<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AuditAdministrativeMutation;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Shared\Presentation\Http\Middleware\EnsureIdempotency;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['webhooks/asaas']);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin/*')
            ? '/admin/login'
            : '/entrar');

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'audit.admin' => AuditAdministrativeMutation::class,
            'idempotent' => EnsureIdempotency::class,
        ]);

        $middleware->web(append: [
            AddSecurityHeaders::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'cardNumber',
            'cardCcv',
        ]);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
