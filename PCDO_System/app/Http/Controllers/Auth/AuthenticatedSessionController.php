<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::validate($request->only('email', 'password'))) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user->active) {
            return back()->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator account at PCDO.palawan@gmail.com to reactivate your account.',
            ]);
        }
        $code = rand(100000, 999999);

        session([
            'login_user_id' => $user->id,
            'login_code' => $code,
            'login_code_expires' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new LoginCodeMail($code));

        return redirect()->route('login.verify');
    }

    /**
     * Show the verify code page.
     */
    public function verifyCodeForm(): Response
    {
        return Inertia::render('auth/VerifyCode');
    }

    /**
     * Handle the verification code submission.
     */
    public function verifyPostCodeForm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'numeric'],
        ]);

        if (
            session('login_code') == $request->code &&
            now()->lt(session('login_code_expires'))
        ) {
            Auth::loginUsingId(session('login_user_id'));
            $request->session()->regenerate();

            session()->forget(['login_user_id', 'login_code', 'login_code_expires']);

            $user = Auth::user();

            // Redirect based on role
            if ($user->hasRole('admin')||$user->hasRole('superadmin')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('officer')) {
                return redirect()->route('dashboard');
            } elseif ($user->hasRole('coop')) {
                return redirect()->route('coop.dashboard', $user->cooperative?->id);
            } else {
                Auth::logout();
                return back()->withErrors(['code' => 'Unauthorized role.']);
            }
        }

        return back()->withErrors(['code' => 'Invalid or expired code.']);
    }

    /**
     * Handle resubmission of code
     */
    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return back()->withErrors([
                'code' => 'Session expired. Please log in again.',
            ]);
        }

        $user = User::find($userId);

        if (! $user) {
            return back()->withErrors([
                'code' => 'User not found.',
            ]);
        }

        $code = rand(100000, 999999);

        session([
            'login_code' => $code,
            'login_code_expires' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new LoginCodeMail($code));

        return back()->with('status', 'A new code has been sent to your email.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
