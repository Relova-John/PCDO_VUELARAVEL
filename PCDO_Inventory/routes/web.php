<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\InventoryFormController;
use App\Http\Controllers\ReportingDateController;
use App\Http\Controllers\QRCodeController;
use App\Http\Middleware\PasswordCheck;

Route::get('/', function () {
    return redirect()->route('inventory.index');
})->name('home');

// View Routes
Route::middleware([PasswordCheck::class])->group(function () {
    Route::get('/cooperatives', [CooperativeController::class, 'index'])->name('cooperatives.index');
    Route::get('/cooperatives/{id}', [CooperativeController::class, 'show'])->name('cooperatives.show');
    Route::post('/reporting-dates', [ReportingDateController::class, 'store'])->name('reporting-dates.store');
});

// Form routes 
Route::get('/inventory', [InventoryFormController::class, 'index'])->name('inventory.index');
Route::post('/inventory', [InventoryFormController::class, 'store'])->name('inventory.store');

// use Synclogger & Electron
Route::get('/qr-code', [QRCodeController::class, 'index'])->name('qr-code.index');

Route::get('/password-prompt', function () {
    return inertia('PasswordPrompt');
})->name('password.prompt');

Route::post('/password-prompt', function (Illuminate\Http\Request $request) {
    $request->validate([
        'password' => 'required|string',
    ]);

    $correctPassword = config('passwords.route');

    if ($request->password === $correctPassword) {
        $request->session()->put('route_password_valid', true);
        return redirect()->intended(route('cooperatives.index'));
    }

    return redirect()->back()->withErrors(['password' => 'Invalid password']);
})->name('password.submit');

require __DIR__ . '/settings.php';
