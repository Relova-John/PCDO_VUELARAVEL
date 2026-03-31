<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    public function handle(Request $request, Closure $next, string $target): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($target === 'admin') {
            if ($user->hasRole(['admin', 'superadmin'])) {
                return $next($request);
            }

            if ($user->hasRole('officer')) {
                return redirect()->route('dashboard');
            }

            if ($user->hasRole('cooperative')) {
                return redirect()->route('coop.dashboard', $user->cooperative?->id);
            }

            abort(403);
        }

        if ($target === 'officer') {
            if ($user->hasRole('officer')) {
                return $next($request);
            }

            if ($user->hasRole(['admin', 'superadmin'])) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('cooperative')) {
                return redirect()->route('coop.dashboard', $user->cooperative?->id);
            }

            abort(403);
        }

        if ($target === 'cooperative') {
            if ($user->hasRole('cooperative')) {
                return $next($request);
            }

            if ($user->hasRole(['admin', 'superadmin'])) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('officer')) {
                return redirect()->route('dashboard');
            }

            abort(403);
        }

        abort(403);
    }
}