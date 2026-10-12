<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust forwarded headers (VS Code port forwarding / tunnels) so URLs use https
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'staff' => \App\Http\Middleware\StaffAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired form (e.g. the phone app was left open for hours) goes back
        // to the page with a fresh token and what was typed, not a bare 419.
        // Laravel turns TokenMismatchException into a 419 HttpException before this runs.
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException || $request->expectsJson()) {
                return null;
            }

            if ($request->routeIs('logout')) {
                return redirect()->route('login');
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'code']))
                ->with('error', 'This page expired because it was open for a long time. It has been refreshed, please try again.');
        });
    })->create();
