<?php

namespace App\Http\Middleware;

use App\Support\StaffAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets caretakers and maintenance staff into only the admin pages their role
 * allows; the owner gets everything.
 */
class StaffAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            StaffAccess::allows($request->user(), $request->route()?->getName()),
            403,
            'Your role does not have access to this page. Ask the owner if you need it.',
        );

        return $next($request);
    }
}
