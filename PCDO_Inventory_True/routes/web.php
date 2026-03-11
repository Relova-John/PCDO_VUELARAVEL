<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'auth/Login', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user->role === 'superadmin' || $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'officer') {
            return redirect()->route('officer.dashboard');
        }

        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/cooperatives', function () {
        $user = request()->user();

        if ($user->role === 'superadmin' || $user->role === 'admin') {
            return redirect()->route('admin.cooperatives.index');
        }

        if ($user->role === 'officer') {
            return redirect()->route('officer.cooperatives.index');
        }

        return redirect()->route('home');
    })->name('cooperatives');

    Route::get('/access-control', function () {
        $user = request()->user();

        if ($user->role === 'superadmin' || $user->role === 'admin') {
            return redirect()->route('admin.access-control.index');
        }

        if ($user->role === 'officer') {
            return redirect()->route('officer.access-control.index');
        }

        return redirect()->route('home');
    })->name('access-control');

    /*
    |--------------------------------------------------------------------------
    | Superadmin + Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:superadmin,admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::inertia('/dashboard', 'admin/Dashboard')->name('dashboard');
            Route::inertia('/cooperatives', 'admin/Cooperatives/Index')->name('cooperatives.index');
            Route::inertia('/access-control', 'admin/AccessControl/Index')->name('access-control.index');
        });

    /*
    |--------------------------------------------------------------------------
    | Officer
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:officer')
        ->prefix('officer')
        ->name('officer.')
        ->group(function () {
            Route::inertia('/dashboard', 'officer/Dashboard')->name('dashboard');
            Route::inertia('/cooperatives', 'officer/Cooperatives/Index')->name('cooperatives.index');
            Route::inertia('/access-control', 'officer/AccessControl/Index')->name('access-control.index');
        });
});

require __DIR__ . '/settings.php';
