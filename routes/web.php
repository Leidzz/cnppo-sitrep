<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reporting\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // After login, send each role to its own dashboard section.
    // Only the reporting dashboard exists so far; other roles keep the
    // Breeze placeholder until their sections are built.
    if (auth()->user()->hasRole('reporting-personnel')) {
        return redirect()->route('reporting.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Reporting Personnel section
Route::middleware(['auth', 'verified', 'role:reporting-personnel'])->group(function () {
    Route::get('/reporting/dashboard', DashboardController::class)
        ->name('reporting.dashboard');
});

require __DIR__.'/auth.php';
