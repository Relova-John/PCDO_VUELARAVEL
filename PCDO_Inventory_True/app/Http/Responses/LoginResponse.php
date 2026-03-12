<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->session()->has('pending_access_token')) {
            $token = $request->session()->pull('pending_access_token');

            return redirect()->route('qr.access', ['token' => $token]);
        }

        return redirect()->intended('/dashboard');
    }
}