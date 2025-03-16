<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminMemberController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminContributionController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Middleware\CheckRole;

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    // Other auth routes for guests
});

// Auth Routes (accessible to all authenticated users)
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', function () {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('member.dashboard');
        }
    })->name('dashboard');
    
    // Dashboard redirect based on role
    
    
    // Member Dashboard
    Route::get('/member/dashboard', [MemberDashboardController::class, 'index'])->name('member.dashboard');
    
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Member Routes (authenticated & verified users)
Route::middleware(['auth', 'verified'])->group(function () {
    // Contributions
    Route::resource('contributions', ContributionController::class)->only(['index', 'show']);
    Route::get('/contributions', [ContributionController::class, 'index'])->name('contributions.index');
    
    // Reports
    Route::resource('reports', ReportController::class);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::get('/reports/{report}/preview', [ReportController::class, 'preview'])->name('reports.preview');
});

// Admin Routes
Route::middleware(['auth', CheckRole::class.':admin'])->prefix('admin')->name('admin.')->group(function () {
//Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Member Management
    Route::resource('members', AdminMemberController::class);
    Route::get('/members/{member}/reset-password', [AdminMemberController::class, 'resetPassword'])->name('members.reset-password');
    Route::get('/members/{member}/refund', [AdminMemberController::class, 'showRefund'])->name('members.refund');
    Route::post('/members/{member}/refund', [AdminMemberController::class, 'processRefund'])->name('members.process-refund');
    Route::get('/members/{member}/contribution', [AdminMemberController::class, 'createContribution'])->name('members.contribution.create');
    Route::post('/members/{member}/contribution', [AdminMemberController::class, 'storeContribution'])->name('members.contribution.store');
    
    // Contribution Management
    Route::resource('contributions', AdminContributionController::class);
    Route::post('/contributions/{contribution}/verify', [AdminContributionController::class, 'verify'])->name('contributions.verify');
    Route::post('/contributions/batch-verify', [AdminContributionController::class, 'batchVerify'])->name('contributions.batch-verify');
    Route::get('/contributions/batch/create', [AdminContributionController::class, 'batchCreate'])->name('contributions.batch.create');
    Route::post('/contributions/batch', [AdminContributionController::class, 'batchStore'])->name('contributions.batch.store');
    Route::get('/contributions/import', [AdminContributionController::class, 'importForm'])->name('contributions.import.form');
    Route::post('/contributions/import', [AdminContributionController::class, 'import'])->name('contributions.import');
    
    // Report Generation
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
    Route::get('/reports/financial', [AdminReportController::class, 'generateFinancialReport'])->name('reports.financial');
    Route::get('/reports/compliance', [AdminReportController::class, 'generateComplianceReport'])->name('reports.compliance');
    Route::get('/reports/member-statement', [AdminReportController::class, 'generateMemberStatement'])->name('reports.member-statement');
    Route::get('/reports/group', [ReportController::class, 'groupReports'])->name('reports.group');
});