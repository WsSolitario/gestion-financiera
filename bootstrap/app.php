<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',      // ← IMPORTANTE: que exista esta línea
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // No redirigir invitados (devolver 401 JSON en APIs)
        $middleware->redirectGuestsTo(fn() => null);
    })

    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
    ])

    ->withExceptions(function (Exceptions $exceptions) {
        // Puedes dejarlo vacío por ahora
    })
    ->create();
