<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Appended, not prepended: it reads the session, so it has to run
        // after StartSession.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        /*
         * Tunnels and load balancers (ngrok, Cloudflare, a production proxy)
         * terminate TLS and forward to the app over plain HTTP. Untrusted,
         * Laravel sees "http" and emits http:// asset URLs on an https:// page,
         * which the browser blocks as mixed content — the CSS silently never
         * loads and the page renders unstyled.
         *
         * Not read from env(): this file is evaluated before .env is loaded,
         * so env() here is always null.
         *
         * '*' is correct when the app is only ever reachable through the proxy
         * — the proxy is the sole source of these headers. If this app is ever
         * exposed directly as well, narrow it to the proxy's IP ranges,
         * because a client that can reach it directly could otherwise spoof
         * X-Forwarded-For and its own address.
         */
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
