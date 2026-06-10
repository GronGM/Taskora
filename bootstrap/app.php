<?php

use App\Console\Commands\ReleaseDueOrdersCommand;
use App\Http\Middleware\EnsureBetaAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ReleaseDueOrdersCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureBetaAccess::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->dashboardPath() ?? '/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($request->expectsJson()) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [403, 404, 429, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $response->getStatusCode(),
            ])->toResponse($request)->setStatusCode($response->getStatusCode());
        });
    })->create();
