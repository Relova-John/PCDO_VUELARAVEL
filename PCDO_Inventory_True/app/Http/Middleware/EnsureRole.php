<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */


    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $hasRole = $user->roles()
            ->whereIn('name', $roles)
            ->exists();

        if (! $hasRole) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
