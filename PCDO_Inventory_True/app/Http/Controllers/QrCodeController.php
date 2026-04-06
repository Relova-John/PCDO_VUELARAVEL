<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use Illuminate\Http\Request;

class QRCodeController extends Controller
{
    public function resolve(string $token, Request $request)
    {
        $accessControl = AccessControl::query()
            ->where('token', $token)
            ->firstOrFail();

        if (! $accessControl->isUsable()) {
            abort(403, 'This QR code is no longer usable.');
        }

        if ($accessControl->type === 'access') {
            if (! $request->user()) {
                return redirect()->route('login', [
                    'qr_token' => $token,
                ]);
            }

            $request->session()->put('pending_access_code', $accessControl->code);

            return redirect()->route('dashboard');
        }

        return redirect()->route('form');
    }
}