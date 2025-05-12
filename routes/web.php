<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Client\SettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Client\ClaimController;
use App\Http\Controllers\Client\ContractController;
use App\Http\Controllers\Underwriter\ContractController as UnderwriterContractController;
use App\Http\Controllers\Underwriter\ClaimController as UnderwriterClaimController;
use App\Http\Controllers\Underwriter\PaymentController as UnderwriterPaymentController;
use App\Http\Controllers\Client\ReportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::middleware(['auth'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::resource('users', App\Http\Controllers\Admin\UserController::class)->names('admin.users');
        Route::resource('companies', CompanyController::class)->names('admin.companies');
        Route::resource('contracts', App\Http\Controllers\Admin\ContractController::class)->names('admin.contracts');
        Route::get('contracts/{id}', [ContractController::class, 'show'])->name('admin.contracts.show');
    });

    Route::prefix('underwriter')->name('underwriter.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Underwriter\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/contracts', [UnderwriterContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/{contract}', [UnderwriterContractController::class, 'show'])->name('contracts.show');
        Route::post('/contracts/{contract}/approve', [UnderwriterContractController::class, 'approve'])->name('contracts.approve');
        Route::post('/contracts/{contract}/reject', [UnderwriterContractController::class, 'reject'])->name('contracts.reject');

        Route::get('/claims', [UnderwriterClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [UnderwriterClaimController::class, 'show'])->name('claims.show');
        Route::post('/claims/{claim}/approve', [UnderwriterClaimController::class, 'approve'])->name('claims.approve');
        Route::post('/claims/{claim}/reject', [UnderwriterClaimController::class, 'reject'])->name('claims.reject');

        Route::get('payments', [UnderwriterPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [UnderwriterPaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/approve', [UnderwriterPaymentController::class, 'approve'])->name('payments.approve');
        Route::post('payments/{payment}/reject', [UnderwriterPaymentController::class, 'reject'])->name('payments.reject');
    });

    Route::prefix('client')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('client.dashboard');
        Route::resource('contracts', \App\Http\Controllers\Client\ContractController::class);
        Route::resource('claims', \App\Http\Controllers\Client\ClaimController::class);
        Route::get('reports', [\App\Http\Controllers\Client\ReportController::class, 'index'])->name('client.reports');
        Route::get('settings', [\App\Http\Controllers\Client\SettingController::class, 'index'])->name('client.settings');
    });

    Route::prefix('client')->name('client.')->middleware(['auth'])->group(function () {
        Route::resource('contracts', ContractController::class)->except(['show']);
        Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');

        Route::resource('claims', ClaimController::class);
        Route::get('/claim/{id}', [ClaimController::class, 'show'])->name('claim.show');

        Route::resource('companies', \App\Http\Controllers\Client\CompanyController::class)->only(['index', 'show']);

        Route::get('reports', [ReportController::class, 'index'])->name('reports');

        Route::get('settings', [SettingController::class, 'index'])->name('settings');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
    });

    Route::get('contracts/create', [ContractController::class, 'create'])->name('client.contracts.create');
});
