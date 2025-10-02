<?php

use App\Http\Controllers\CooperativesController;
use App\Http\Controllers\CoopMemberController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    // Route::inertia('admin', 'Admin')->name('admin');
    // // User Management Routes
    // Route::resource('users', \App\Http\Controllers\UserController::class);
    // Route::get('users/search', [\App\Http\Controllers\UserController::class, 'search'])->name('users.search');
    // Route::get('users/export', [\App\Http\Controllers\UserController::class, 'export'])->name('users.export');
    // Route::post('users/import', [\App\Http\Controllers\UserController::class, 'import'])->name('users.import');
    Route::inertia('welcome', 'Welcome')->name('welcome');
});

Route::middleware(['auth', 'verified', 'role:officer'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Cooperatives Routes
    Route::resource('cooperatives', CooperativesController::class);
    Route::get('cooperatives/export/{type}', [CooperativesController::class, 'export'])->name('cooperatives.export');
    Route::post('cooperatives/import', [CooperativesController::class, 'import'])->name('cooperatives.import');

    // Cooperatives Nested Routes
    // Route::get('cooperatives/{cooperative}/history', [CoopHistoryController::class, 'index'])->name('cooperatives.history');
    Route::resource('cooperatives.members', CoopMemberController::class);
    Route::get('cooperatives/{cooperative}/members/{member}/files/{fileId}/download',
        [CoopMemberController::class, 'downloadFile'])
        ->name('cooperatives.members.files.download');

    Route::delete('cooperatives/{cooperative}/members/{member}/files/{fileId}',
        [CoopMemberController::class, 'deleteFile'])
        ->name('cooperatives.members.files.delete');
    // Route::get('cooperatives/{cooperative}/programs', [CoopProgramController::class, 'index'])->name('cooperatives.programs');

    // // Cooperatives Programs Routes
    // Route::resource('coopPrograms', CoopProgramController::class);
    // Route::get('coopPrograms/search', [CoopProgramController::class, 'search'])->name('coopPrograms.search');
    // Route::get('coopPrograms/export', [CoopProgramController::class, 'export'])->name('coopPrograms.export');
    // Route::post('coopPrograms/import', [CoopProgramController::class, 'import'])->name('cooprograms.import');

    // // Cooperatives Program Nested Routes
    // Route::resource('coopPrograms/{cooperative}/checklists', CoopProgramChecklistController::class);

    // Custom Command Routes
    Route::get('/sync', [SyncController::class, 'sync'])->name('sync');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
