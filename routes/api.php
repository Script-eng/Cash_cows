// routes/api.php

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User dashboard data
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/contributions', [ContributionController::class, 'index']);
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports/generate', [ReportController::class, 'generate']);
    Route::get('/reports/{id}/download', [ReportController::class, 'download']);
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::resource('/admin/users', AdminUserController::class);
        Route::post('/admin/contributions/verify/{id}', [AdminContributionController::class, 'verify']);
        Route::get('/admin/reports/group', [AdminReportController::class, 'groupReports']);
    });
});