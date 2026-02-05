<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AmortizationScheduleController;
use App\Http\Controllers\CooperativesController;
use App\Http\Controllers\CoopMemberController;
use App\Http\Controllers\CoopProgramChecklistController;
use App\Http\Controllers\CoopProgramProgressController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

app('router')->aliasMiddleware('role', RoleMiddleware::class);

Route::get('/', function () {
    $user = Auth::user();

    if (! $user) {
        return redirect()->route('login');
    }

    if (in_array($user->role, ['superadmin', 'admin'])) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->role === 'officer') {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::get('/ping', fn () => response()->json(['pong' => true]));

Route::middleware(['auth', 'role:admin|superadmin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.storeUser');
    Route::post('/admin/users/{id}/deactivate', [AdminController::class, 'deactivateUser'])->name('admin.users.deactivate');
    Route::post('/admin/users/{id}/activate', [AdminController::class, 'activateUser'])->name('admin.users.activate');
    Route::get('/admin/logs/{id}/changes', [AdminController::class, 'getLogChanges'])->name('admin.logs.changes');
});

Route::middleware(['auth', 'verified', 'role:officer'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        return app(App\Http\Controllers\DashboardController::class)->index();
    })->name('dashboard');

    // Cooperatives Routes
    Route::resource('cooperatives', CooperativesController::class);
    Route::get('cooperatives/export/{type}', [CooperativesController::class, 'export'])->name('cooperatives.export');
    Route::post('cooperatives/import', [CooperativesController::class, 'import'])->name('cooperatives.import');

    // Cooperatives Nested Routes
    Route::resource('cooperatives.members', CoopMemberController::class);
    Route::get('/cooperatives/{cooperative}/members/{member}/files/{fileId}/view', [CoopMemberController::class, 'viewFile'])
        ->name('cooperatives.members.files.view');

    Route::get('/cooperatives/{cooperative}/members/{member}/files/{fileId}/download', [CoopMemberController::class, 'downloadFile'])
        ->name('cooperatives.members.files.download');

    Route::delete('cooperatives/{cooperative}/members/{member}/files/{fileId}', [CoopMemberController::class, 'deleteFile'])->name('cooperatives.members.files.delete');
    Route::get('cooperatives/{cooperative}', [CooperativesController::class, 'show'])->name('cooperatives.show');
    Route::get('/cooperatives/{cooperative}/members/{member}/biodata/pdf', [CoopMemberController::class, 'downloadBiodataPdf'])->where(['cooperative' => '.*', 'member' => '.*'])->name('cooperatives.members.biodata.pdf');
    // Program Routes
    Route::resource('programs', ProgramController::class);

    // Nested routes for adding cooperatives to a program
    Route::get('/programs/{program}/cooperatives/create', [ProgramController::class, 'createCooperative'])->name('programs.cooperatives.create');
    Route::post('/programs/{program}/cooperatives', [ProgramController::class, 'storeCooperative'])->name('programs.cooperatives.store');
    Route::get('/programs/reports/monthly', [ProgramController::class, 'monthlyReport'])->name('programs.reports.monthly');

    // Progress Report
    Route::get('/programs/{program}/progress/create', [CoopProgramProgressController::class, 'create'])->name('programs.progress.create');
    Route::post('/programs/{program}/progress', [CoopProgramProgressController::class, 'store'])->name('programs.progress.store');
    Route::get('/progress/{report}', [CoopProgramProgressController::class, 'show'])->name('programs.progress.show');
    Route::get('/progress/{report}/download', [CoopProgramProgressController::class, 'download'])->name('programs.progress.download');

    // Nested routes for checklists under a specific program and cooperative
    Route::prefix('programs/{program}/cooperatives/{cooperative}')->group(function () {
        Route::get('checklist', [CoopProgramChecklistController::class, 'show'])->name('programs.cooperatives.checklist.show');
        Route::post('checklist/upload', [CoopProgramChecklistController::class, 'upload'])->name('programs.cooperatives.checklist.upload');
        Route::get('checklist/{file}/preview', [CoopProgramChecklistController::class, 'preview'])->name('programs.cooperatives.checklist.preview');
        Route::post('consent', [CoopProgramChecklistController::class, 'consent'])->name('programs.cooperatives.consent');
        Route::get('checklist/{file}/download', [CoopProgramChecklistController::class, 'download'])->name('programs.cooperatives.checklist.download');
        Route::delete('checklist/{file}', [CoopProgramChecklistController::class, 'delete'])->name('programs.cooperatives.checklist.delete');
        Route::post('finalize-loan', [ProgramController::class, 'finalizeLoan'])->name('cooperatives.finalizeLoan');
    });

    // Cooperatives Programs Routes
    // Route::resource('coopPrograms', CoopProgramController::class);
    // Route::get('coopPrograms/search', [CoopProgramController::class, 'search'])->name('coopPrograms.search');
    // Route::get('coopPrograms/export', [CoopProgramController::class, 'export'])->name('coopPrograms.export');
    // Route::post('coopPrograms/import', [CoopProgramController::class, 'import'])->name('cooprograms.import');

    // Payments Routes
    Route::get('amortizations', [AmortizationScheduleController::class, 'index'])->name('amortizations.index');
    Route::get('/amortizations/{coopProgram}', [AmortizationScheduleController::class, 'show'])->name('amortizations.show');
    Route::post('/schedules/{schedule}/mark-paid', [AmortizationScheduleController::class, 'markPaid'])->name('schedules.markPaid');
    Route::post('/schedules/{schedule}/note-payment', [AmortizationScheduleController::class, 'notePayment'])->name('schedules.notePayment');
    Route::post('/schedules/{schedule}/penalty', [AmortizationScheduleController::class, 'penalty'])->name('schedules.penalty');
    Route::post('/schedules/{schedule}/send-overdue-email', [AmortizationScheduleController::class, 'sendOverdueEmail'])->name('schedules.sendOverdueEmail');

    // Amortization Incomplete
    Route::post('/amortization/{loan}/incomplete', [AmortizationScheduleController::class, 'markIncomplete'])->name('loan.incomplete');
    Route::post('/amortization/{loan}/resolve', [AmortizationScheduleController::class, 'markResolved'])->name('loan.resolve');
    Route::get('/amortization/{id}/download', [AmortizationScheduleController::class, 'downloadAmortizationPdf'])->name('amortization.download');
    Route::get('/amortization/{id}/view', [AmortizationScheduleController::class, 'amortizationFile'])->name('amortization.view');
    Route::post('/schedules/{schedule}/upload-receipt', [AmortizationScheduleController::class, 'markPaid'])->name('schedules.upload-receipt');
    Route::post('/schedules/{schedule}/upload-note-receipt', [AmortizationScheduleController::class, 'notePayment'])->name('schedules.upload-note-receipt');

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::get('/notifications/{id}/download', [NotificationController::class, 'downloadnotice'])->name('notifications.download');
    Route::post('/schedules/{schedule}/send-notif', [AmortizationScheduleController::class, 'sendOverdueEmail'])->name('schedules.sendNotif');

    // Documentation Routes
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/documentation/cooperative/{id}', [DocumentationController::class, 'show'])->name('documentation.show');
    Route::get('/documentation/{id}/amortization', [DocumentationController::class, 'amortizationFile'])->name('documentation.amortization');
    Route::get('/documentation/{id}/details', [DocumentationController::class, 'cooperativeDetailsFile'])->name('documentation.details');
    Route::get('/documentation/{id}/resolved', [DocumentationController::class, 'resolvedFile'])->name('documentation.resolved.file');
    Route::get('/documentation/{id}/checklist', [DocumentationController::class, 'checklistFile'])->name('documentation.checklist.file');
    Route::get('/documentation/{id}/member-files/', [DocumentationController::class, 'memberFile'])->name('documentation.member-files');
    Route::get('/documentation/{id}/delinquent', [DocumentationController::class, 'delinquentReport'])->name('documentation.delinquent');
    Route::get('/documentation/{id}/progress', [DocumentationController::class, 'progressReportFile'])->name('documentation.progress.file');
    Route::get('/documentation/{id}/allfiles', [DocumentationController::class, 'allFile'])->name('documentation.allfiles.file');

    // // Resolved Routes
    // Route::get('/resolved/{coopProgram}/upload', [ResolvedController::class, 'create'])->name('resolved.create');
    // Route::post('/resolved/{coopProgram}', [ResolvedController::class, 'store'])->name('resolved.store');
    // Route::get('/resolved/download/{id}', [ResolvedController::class, 'download'])->name('resolved.download');

    // Cooperatives Program Nested Routes
    // Route::resource('coopPrograms/{cooperative}/checklists', CoopProgramChecklistController::class);

    // Custom Command Routes
    Route::get('/sync', function () {
        Artisan::call('sync:database');

        return response()->json(['status' => 'synced']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
