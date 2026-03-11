<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PasswordCheck
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('route_password_valid')) {
            return $next($request);
        }

        return redirect()->route('password.prompt');
    }
}