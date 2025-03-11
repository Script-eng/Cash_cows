<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ContributionController as AdminContributionController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest routes
Route::middleware('guest')->group(function () {
    // Laravel Breeze already handles these routes
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Contributions
    Route::resource('contributions', ContributionController::class)->only(['index', 'show']);
    
    // Reports
    Route::resource('reports', ReportController::class);
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    
    // Profile routes (from Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Admin contribution management
    Route::resource('contributions', AdminContributionController::class);
    Route::post('/contributions/{contribution}/verify', [AdminContributionController::class, 'verify'])->name('contributions.verify');
    
    // Admin report management
    Route::get('/reports/group', [ReportController::class, 'groupReports'])->name('reports.group');
});
