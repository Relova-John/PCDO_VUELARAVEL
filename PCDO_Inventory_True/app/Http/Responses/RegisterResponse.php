<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->filled('qr_token')) {
            return redirect()->route('qr.resolve', [
                'token' => $request->input('qr_token'),
            ]);
        }

        return redirect()->intended('/dashboard');
    }
}