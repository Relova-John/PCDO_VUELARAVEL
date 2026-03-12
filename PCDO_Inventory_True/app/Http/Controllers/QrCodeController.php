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
            ->where('is_active', true)
            ->firstOrFail();

        if (! $accessControl->isUsable()) {
            abort(403, 'This QR code is no longer usable.');
        }

        if ($accessControl->type === 'access') {
            $request->session()->put('pending_access_code', $accessControl->code);

            return redirect()->route('home');
        }

        return redirect()->route('form');
    }
}