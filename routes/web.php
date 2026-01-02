<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Owner\CompanyController as OwnerCompanyController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Owner\PaymentController;
use App\Http\Controllers\Owner\ReportController as OwnerReportController;
use App\Http\Controllers\Specialist\ClaimController as SpecialistClaimController;
use App\Http\Controllers\Specialist\CompanyController as SpecialistCompanyController;
use App\Http\Controllers\Underwriter\CompanyController as UnderwriterCompanyController;
use App\Http\Controllers\Underwriter\ContractController;
use App\Models\Company;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::middleware(['auth'])->group(function () {
    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::post('/companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
        Route::get('/companies/{company}/edit', function(Company $company) {
            return response()->json($company);
        });

        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{filename}', [BackupController::class, 'delete'])->name('backup.delete');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', App\Http\Controllers\Admin\UserController::class)->names('users');
    });

    Route::prefix('owner')->name('owner.')->middleware(['role:owner'])->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.updateStatus');
        Route::get('/payments/{payment}/details', [PaymentController::class, 'getDetails'])->name('payments.details');
        Route::post('/payments/{payment}/status', [PaymentController::class, 'updateStatusAjax'])->name('payments.updateStatus.ajax');
        Route::get('/companies/list', action: [OwnerCompanyController::class, 'index'])->name('companies.index');
        Route::get('/reports', action: [OwnerReportController::class, 'index'])->name(name: 'reports.index');
                Route::get('/reports/export', action: [OwnerReportController::class, 'exportPdf'])->name(name: 'reports.export');
    });

    Route::prefix('underwriter')->name('underwriter.')->middleware(['role:underwriter'])->group(function () {
        // Contracts
        Route::get('/contracts/incoming', [ContractController::class, 'incoming'])->name('contracts.incoming');
        Route::get('/contracts/outgoing', [ContractController::class, 'outgoing'])->name('contracts.outgoing');
        Route::get('/contracts/outgoing/create', [UnderwriterContractController::class, 'showCreateContract'])->name('contracts.showCreate');
        Route::post('/contracts/outgoing/store', [UnderwriterContractController::class, 'storeContract'])->name('contracts.store');
        Route::post('/contracts/{contract}/message', [UnderwriterContractController::class, 'addMessage'])->name('contract.message.add');
        Route::put('/contracts/{contract}/status', [UnderwriterContractController::class, 'updateStatus'])->name('contracts.update-status');
        Route::get('/contracts/{contract}', [UnderwriterContractController::class, 'show'])->name('contracts.show');
        Route::post('/contracts/{contract}/approve', [UnderwriterContractController::class, 'approve'])->name('contracts.approve');
        Route::post('/contracts/{contract}/reject', [UnderwriterContractController::class, 'reject'])->name('contracts.reject');
        Route::get('/contracts/export', [UnderwriterContractController::class, 'export'])->name('contracts.export');
        
        // Companies
        Route::get('/companies/list', action: [UnderwriterCompanyController::class, 'index'])->name('companies.index');
    });

    Route::prefix('specialist')->name('specialist.')->middleware(['role:specialist'])->group(function () {
        Route::resource('contracts', \App\Http\Controllers\Client\ContractController::class);
        Route::resource('claims', \App\Http\Controllers\Client\ClaimController::class);
        Route::get('reports', [\App\Http\Controllers\Client\ReportController::class, 'index'])->name('client.reports');
        Route::get('settings', [\App\Http\Controllers\Client\SettingController::class, 'index'])->name('client.settings');

        Route::get('/companies/list', action: [SpecialistCompanyController::class, 'index'])->name('companies.index');
        Route::get('/claims/{claim}/details', [\App\Http\Controllers\Client\ClaimController::class, 'details'])->name('claims.details');

        Route::prefix('claims')->name('claims.')->group(function () {

        Route::get('/export', [\App\Http\Controllers\Client\ClaimController::class, 'export'])
            ->name('export')
            ->middleware('auth');

        Route::get('/', [\App\Http\Controllers\Client\ClaimController::class, 'index'])->name('index');
        Route::get('/create', [SpecialistClaimController::class, 'create'])->name('create');
        Route::post('/', [SpecialistClaimController::class, 'store'])->name('store');
        Route::get('/{claim}', [SpecialistClaimController::class, 'show'])->name('show');
        Route::get('/{claim}/edit', [SpecialistClaimController::class, 'edit'])->name('edit');
        Route::put('/{claim}', [SpecialistClaimController::class, 'update'])->name('update');
        Route::delete('/{claim}', [SpecialistClaimController::class, 'destroy'])->name('destroy');
            
        Route::put('/{claim}/status', [SpecialistClaimController::class, 'updateStatus'])->name('update-status');
    });

});
});
