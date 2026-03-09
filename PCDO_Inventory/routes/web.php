<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\InventoryFormController;
use App\Http\Controllers\ReportingDateController;

Route::get('/', function () {
    return view('cooperatives.index');
})->name('home');

// View Routes
Route::get('/cooperatives', [CooperativeController::class, 'index'])->name('cooperatives.index');
Route::get('/cooperatives/{id}', [CooperativeController::class, 'show'])->name('cooperatives.show');
Route::post('/reporting-dates', [ReportingDateController::class, 'store'])->name('reporting-dates.store');

// Form routes 
Route::get('/inventory', [InventoryFormController::class, 'index'])->name('inventory.index');
Route::post('/inventory', [InventoryFormController::class, 'store'])->name('inventory.store');

// use Synclogger & Electron
// QR verify device for online

require __DIR__ . '/settings.php';
