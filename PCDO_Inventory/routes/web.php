<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\InventoryFormController;
use App\Http\Controllers\ReportingDateController;
use App\Http\Controllers\QRCodeController;

Route::get('/', function () {
    return redirect()->route('cooperatives.index');
})->name('home');

// View Routes
Route::get('/cooperatives', [CooperativeController::class, 'index'])->name('cooperatives.index');
Route::get('/cooperatives/{id}', [CooperativeController::class, 'show'])->name('cooperatives.show');
Route::post('/reporting-dates', [ReportingDateController::class, 'store'])->name('reporting-dates.store');

// Form routes 
Route::get('/inventory', [InventoryFormController::class, 'index'])->name('inventory.index');
Route::post('/inventory', [InventoryFormController::class, 'store'])->name('inventory.store');

// use Synclogger & Electron
Route::get('/qr-code', [QRCodeController::class, 'index'])->name('qr-code.index');

require __DIR__ . '/settings.php';
