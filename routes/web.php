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
use App\Http\Controllers\Owner\PaymentController;
use App\Http\Controllers\Specialist\ClaimController as SpecialistClaimController;
use App\Http\Controllers\Underwriter\CompanyController as UnderwriterCompanyController;
use App\Http\Controllers\Underwriter\ContractController as ControllersUnderwriterContractController;
use App\Http\Controllers\Underwriter\ContractController as HttpControllersUnderwriterContractController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Uri\Rfc3986\Uri;

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

    Route::get('/contracts/incoming', [UnderwriterContractController::class, 'incoming'])
         ->name('contracts.incoming');
    Route::get('/contracts/outgoing', [UnderwriterContractController::class, 'outgoing'])
         ->name('contracts.outgoing');
    Route::get('/contracts/outgoing/create', [UnderwriterContractController::class, 'showCreateContract'])
         ->name('contracts.showCreate');
    Route::post('/contracts/outgoing/store', [UnderwriterContractController::class, 'storeContract'])
         ->name('contracts.store');
    Route::post('/contracts/{contract}/message', [UnderwriterContractController::class, 'addMessage'])
        ->name('contract.message.add');
    Route::put('/contracts/{contract}/status', [UnderwriterContractController::class, 'updateStatus'])
        ->name('contracts.update-status');


             Route::get('/companies/list', [UnderwriterCompanyController::class, 'index'])
         ->name('companies.index');



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

    Route::prefix('specialist')->name('specialist.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('client.dashboard');
        Route::resource('contracts', \App\Http\Controllers\Client\ContractController::class);
        Route::resource('claims', \App\Http\Controllers\Client\ClaimController::class);
        Route::get('reports', [\App\Http\Controllers\Client\ReportController::class, 'index'])->name('client.reports');
        Route::get('settings', [\App\Http\Controllers\Client\SettingController::class, 'index'])->name('client.settings');


    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.updateStatus');

    Route::get('/payments/{payment}/details', [PaymentController::class, 'getDetails'])->name('payments.details');
    Route::post('/payments/{payment}/status', [PaymentController::class, 'updateStatusAjax'])->name('payments.updateStatus.ajax');




        Route::prefix('claims')->name('claims.')->group(function () {
            Route::get('/', [SpecialistClaimController::class, 'index'])->name('index');
            Route::get('/create', [SpecialistClaimController::class, 'create'])->name('create');
            Route::post('/', [SpecialistClaimController::class, 'store'])->name('store');
            Route::get('/{claim}', [SpecialistClaimController::class, 'show'])->name('show');
            Route::get('/{claim}/edit', [SpecialistClaimController::class, 'edit'])->name('edit');
            Route::put('/{claim}', [SpecialistClaimController::class, 'update'])->name('update');
            Route::delete('/{claim}', [SpecialistClaimController::class, 'destroy'])->name('destroy');
        
        // Статусы убытков
        Route::put('/{claim}/status', [SpecialistClaimController::class, 'updateStatus'])->name('update-status');
    });
    
    // Договоры (только просмотр для specialist)
    // Route::prefix('contracts')->name('contracts.')->group(function () {
    //     Route::get('/', [ContractController::class, 'index'])->name('index');
    //     Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
    // });
    
    // // Выплаты
    // Route::prefix('payments')->name('payments.')->group(function () {
    //     Route::get('/', [PaymentController::class, 'index'])->name('index');
    //     Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    //     Route::put('/{payment}/status', [PaymentController::class, 'updateStatus'])->name('update-status');
    // });


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
