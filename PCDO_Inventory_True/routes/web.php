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
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('/qr/{token}', [QRCodeController::class, 'resolve'])->name('qr.resolve');

Route::get('/Form', [FormController::class, 'index'])->name('form');
Route::post('/Form', [FormController::class, 'store'])->name('form.store');

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

    Route::middleware('role:superadmin,admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/{id}', [AdminDashboardController::class, 'showDetails'])->name('dashboard.showdetails');
            Route::post('/reporting-dates', [AdminDashboardController::class, 'updateReportingDates'])->name('reporting-dates.update');
            Route::get('/dashboard/{id}/edit', [AdminDashboardController::class, 'edit'])
                ->name('dashboard.edit');
            Route::put('/dashboard/{id}', [AdminDashboardController::class, 'update'])
                ->name('dashboard.update');

            Route::get('/access-control', [AdminAccessControlController::class, 'index'])
                ->name('access-control.index');

            Route::post('/access-control', [AdminAccessControlController::class, 'store'])
                ->name('access-control.store');

            Route::patch('/access-control/{accessControl}/close', [AdminAccessControlController::class, 'close'])
                ->name('access-control.close');

            Route::patch('/access-control/{accessControl}/reopen', [AdminAccessControlController::class, 'reopen'])
                ->name('access-control.reopen');

            Route::get('/access-control/{accessControl}/qr', [AdminAccessControlController::class, 'downloadQr'])
                ->name('access-control.qr');

            Route::get('/access-control/static-form-qr', [AdminAccessControlController::class, 'downloadStaticFormQr'])
                ->name('access-control.static-form-qr');
        });

    Route::middleware('role:officer')
        ->prefix('officer')
        ->name('officer.')
        ->group(function () {
            Route::get('/dashboard', [OfficerDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/dashboard/{id}', [OfficerDashboardController::class, 'showDetails'])
                ->name('dashboard.showdetails');

            Route::get('/dashboard/{id}/edit', [OfficerDashboardController::class, 'edit'])
                ->name('dashboard.edit');
            Route::put('/dashboard/{id}', [OfficerDashboardController::class, 'update'])
                ->name('dashboard.update');

            Route::post('/dashboard/access-control/activate', [OfficerDashboardController::class, 'activate'])
                ->name('dashboard.access-control.activate');
        });
});

require __DIR__ . '/settings.php';
