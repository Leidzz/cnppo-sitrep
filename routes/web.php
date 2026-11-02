<?php

use App\Http\Controllers\Admin\AdminGpsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Command\DashboardController as CommandDashboardController;
use App\Http\Controllers\Command\SitrepController as CommandSitrepController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reporting\DashboardController;
use App\Http\Controllers\Reporting\SitrepController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('reporting-personnel')) {
        return redirect()->route('reporting.dashboard');
    }

    if (auth()->user()->hasRole('command-center')) {
        return redirect()->route('command.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// CHANGED: 'active' added — deactivated accounts are locked out everywhere
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications: auth (this group); content is user-scoped in the controller
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Photo serving: auth (this group) + parent-SITREP policy (in controller)
    Route::get('/attachments/{attachment}/file', [AttachmentController::class, 'show'])
        ->name('attachments.show');
});

// Reporting Personnel section
Route::middleware(['auth', 'verified', 'active', 'role:reporting-personnel'])->group(function () {
    Route::get('/reporting/dashboard', DashboardController::class)
        ->name('reporting.dashboard');

    Route::get('/reporting/sitreps/create', [SitrepController::class, 'create'])
        ->name('reporting.sitreps.create');

    Route::post('/reporting/sitreps', [SitrepController::class, 'store'])
        ->name('reporting.sitreps.store');

    Route::get('/reporting/sitreps', [SitrepController::class, 'index'])
        ->name('reporting.sitreps.index');

    Route::get('/reporting/sitreps/{sitrep}', [SitrepController::class, 'show'])
        ->name('reporting.sitreps.show');

    Route::get('/reporting/sitreps/{sitrep}/edit', [SitrepController::class, 'edit'])
        ->name('reporting.sitreps.edit');

    Route::put('/reporting/sitreps/{sitrep}', [SitrepController::class, 'update'])
        ->name('reporting.sitreps.update');
});

// Command Center section — province-wide review
Route::middleware(['auth', 'verified', 'active', 'role:command-center'])->group(function () {
    Route::get('/command/dashboard', CommandDashboardController::class)
        ->name('command.dashboard');

    Route::get('/command/incoming', [CommandSitrepController::class, 'incoming'])
        ->name('command.incoming');

    Route::get('/command/sitreps/{sitrep}', [CommandSitrepController::class, 'show'])
        ->name('command.sitreps.show');

    Route::post('/command/sitreps/{sitrep}/decide', [CommandSitrepController::class, 'decide'])
        ->name('command.sitreps.decide');

    Route::get('/command/reports', [CommandSitrepController::class, 'reports'])
        ->name('command.reports.index');

    Route::get('/command/map', [CommandSitrepController::class, 'map'])
        ->name('command.map');

    Route::get('/command/map/data', [CommandSitrepController::class, 'mapData'])
        ->name('command.map.data');
});

// Administrator section — configuration + GPS accuracy study
Route::middleware(['auth', 'verified', 'active', 'role:administrator'])->group(function () {
    // NEW (Phase 4 Step 1): admin dashboard + user management
    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->name('admin.dashboard');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])
        ->name('admin.users.toggleActive');

    // GPS study (Phase 5)
    Route::get('/admin/gps', [AdminGpsController::class, 'index'])
        ->name('admin.gps.index');

    Route::post('/admin/gps/points', [AdminGpsController::class, 'storePoint'])
        ->name('admin.gps.points.store');

    Route::delete('/admin/gps/points/{referencePoint}', [AdminGpsController::class, 'destroyPoint'])
        ->name('admin.gps.points.destroy');

    Route::get('/admin/gps/points/{referencePoint}/capture', [AdminGpsController::class, 'capture'])
        ->name('admin.gps.capture');

    Route::post('/admin/gps/points/{referencePoint}/observations', [AdminGpsController::class, 'record'])
        ->name('admin.gps.observations.store');

    Route::get('/admin/gps/results', [AdminGpsController::class, 'results'])
        ->name('admin.gps.results');

    Route::get('/admin/gps/export', [AdminGpsController::class, 'export'])
        ->name('admin.gps.export');
});

require __DIR__.'/auth.php';
