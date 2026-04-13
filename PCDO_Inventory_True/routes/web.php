<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AccessControlController as AdminAccessControlController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\AccessControlController as OfficerAccessControlController;
use App\Http\Controllers\Guest\FormController;
use App\Http\Controllers\QRCodeController;

Route::inertia('/', 'auth/Login', [
    'canResetPassword' => Features::enabled(Features::resetPasswords()),
])->name('home');

Route::get('/qr/{token}', [QRCodeController::class, 'resolve'])->name('qr.resolve');

Route::get('/form', function () {
    $user = request()->user();

    if (! $user) {
        return redirect()->route('guest.create');
    }

    if ($user->hasRole(['officerI', 'officerII'])) {
        return redirect()->route('officer.create');
    }

    if ($user->hasRole(['admin', 'superadmin'])) {
        return redirect()->route('admin.create');
    }

    return redirect()->route('guest.create');
})->name('form');

Route::middleware('guest')->name('guest.')->group(function () {
    Route::get('/guest/form', [FormController::class, 'index'])->name('create');
    Route::post('/guest/form', [FormController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user->hasRole(['superadmin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole(['officerI', 'officerII'])) {
            return redirect()->route('officer.dashboard');
        }

        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/access-control', function () {
        $user = request()->user();

        if ($user->hasRole(['superadmin', 'admin'])) {
            return redirect()->route('admin.access-control.index');
        }

        return redirect()->route('home');
    })->name('access-control');

    Route::middleware('role:superadmin,admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/create', [AdminDashboardController::class, 'create'])->name('create');
            Route::post('/create', [AdminDashboardController::class, 'store'])->name('store');
            Route::get('/dashboard/{id}', [AdminDashboardController::class, 'showDetails'])->name('dashboard.showdetails');
            Route::post('/reporting-dates', [AdminDashboardController::class, 'updateReportingDates'])->name('reporting-dates.update');
            Route::get('/dashboard/{id}/edit', [AdminDashboardController::class, 'edit'])->name('dashboard.edit');
            Route::put('/dashboard/{id}', [AdminDashboardController::class, 'update'])->name('dashboard.update');
            Route::get('/dashboard/summary-report/pdf', [AdminDashboardController::class, 'downloadSummaryPdf'])->name('dashboard.summary-report.pdf');

            Route::get('/access-control', [AdminAccessControlController::class, 'index'])->name('access-control.index');
            Route::post('/access-control', [AdminAccessControlController::class, 'store'])->name('access-control.store');
            Route::post('/users', [AdminAccessControlController::class, 'createUsers'])->name('createUser');
            Route::post('/users/{id}/deactivate', [AdminAccessControlController::class, 'deactivateUser'])->name('users.deactivate');
            Route::post('/users/{id}/activate', [AdminAccessControlController::class, 'activateUser'])->name('users.activate');
            Route::get('/logs/{id}/changes', [AdminAccessControlController::class, 'getLogChanges'])->name('logs.changes');
            Route::post('users/{user}/change-role', [AdminAccessControlController::class, 'changeRole'])->name('users.changeRole');
            Route::post('users/{user}/change-location', [AdminAccessControlController::class, 'changeLocation']);
            Route::get('/logs/{id}/changes', [AdminAccessControlController::class, 'getLogChanges'])->name('logs.changes');
            Route::get('/access-control/static-form-qr', [AdminAccessControlController::class, 'downloadStaticFormQr'])->name('access-control.static-form-qr');
        });

    Route::middleware('role:officerI,officerII')
        ->prefix('officer')
        ->name('officer.')
        ->group(function () {
            Route::get('/dashboard', [OfficerDashboardController::class, 'index'])->name('dashboard');
            Route::get('/create', [OfficerDashboardController::class, 'create'])->name('create');
            Route::post('/create', [OfficerDashboardController::class, 'store'])->name('store');
            Route::get('/dashboard/{id}', [OfficerDashboardController::class, 'showDetails'])->name('dashboard.showdetails');
            Route::get('/dashboard/{id}/edit', [OfficerDashboardController::class, 'edit'])->name('dashboard.edit');
            Route::put('/dashboard/{id}', [OfficerDashboardController::class, 'update'])->name('dashboard.update');
        });
});

require __DIR__ . '/settings.php';
