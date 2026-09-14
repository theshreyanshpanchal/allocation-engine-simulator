<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render terminates TLS at its edge and proxies over a private
        // network, so the app only ever sees that hop — trust it so
        // request()->isSecure(), generated URLs, and secure-cookie flags
        // read correctly instead of assuming plain HTTP.
        $middleware->trustProxies(at: '*');

        // 120 req/min per IP is generous for a person clicking through the
        // dashboard (each interaction is one Livewire request) while
        // blunting a script hammering the public /livewire/update endpoint
        // on a free, single-instance box.
        $middleware->web(append: [
            ThrottleRequests::class.':120,1',
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
