<?php

use App\Http\Controllers\AttachmentController;
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

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Photo serving: auth (this group) + parent-SITREP policy (in controller)
    Route::get('/attachments/{attachment}/file', [AttachmentController::class, 'show'])
        ->name('attachments.show');
});

// Reporting Personnel section
Route::middleware(['auth', 'verified', 'role:reporting-personnel'])->group(function () {
    Route::get('/reporting/dashboard', DashboardController::class)
        ->name('reporting.dashboard');

    Route::get('/reporting/sitreps/create', [SitrepController::class, 'create'])
        ->name('reporting.sitreps.create');
    Route::post('/reporting/sitreps', [SitrepController::class, 'store'])
        ->name('reporting.sitreps.store');
});

require __DIR__.'/auth.php';
