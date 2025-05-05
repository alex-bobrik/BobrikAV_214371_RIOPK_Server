<?php

use App\Http\Controllers\Client\SettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Client\ClaimController;
use App\Http\Controllers\Client\ContractController;
use App\Http\Controllers\Client\ReportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\HomeController;

// Главная страница
Route::get('/', [HomeController::class, 'index'])->name('home');

// Аутентификация
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Регистрация (только для клиентов)
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Защищенные маршруты
Route::middleware(['auth'])->group(function () {
    // Админ
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
    });

    // Андеррайтер
    Route::prefix('underwriter')->middleware('role:underwriter')->group(function () {
        Route::get('/dashboard', function () {
            return view('underwriter.dashboard');
        })->name('underwriter.dashboard');
    });

    // Клиент
    Route::prefix('client')->middleware('role:client')->group(function () {
        Route::get('/dashboard', function () {
            return view('client.dashboard');
        })->name('client.dashboard');
    });

    Route::prefix('client')->middleware(['auth', 'role:client'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('client.dashboard');
        
        // Другие маршруты для клиента...
        Route::resource('contracts', \App\Http\Controllers\Client\ContractController::class);
        Route::resource('claims', \App\Http\Controllers\Client\ClaimController::class);
        Route::get('reports', [\App\Http\Controllers\Client\ReportController::class, 'index'])->name('client.reports');
        Route::get('settings', [\App\Http\Controllers\Client\SettingController::class, 'index'])->name('client.settings');
    });

    Route::prefix('client')->name('client.')->middleware(['auth', 'role:client'])->group(function () {
        // Dashboard        
        // Contracts
        Route::resource('contracts', ContractController::class)->except(['show']);
        Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
        
        // Claims
        Route::resource('claims', ClaimController::class);
        
        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports');
        
        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings');
        Route::put('settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('settings/password', [SettingController::class, 'updatePassword'])->name('settings.password.update');
    });

    Route::get('contracts/create', [ContractController::class, 'create'])->name('client.contracts.create');
});