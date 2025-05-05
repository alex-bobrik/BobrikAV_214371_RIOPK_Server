<?php

use App\Http\Controllers\Client\ClaimController;
use App\Http\Controllers\Client\ContractController;
use App\Http\Controllers\Client\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::prefix('v1')->middleware(['auth:sanctum', 'role:client'])->group(function () {
//     // Contracts API
//     Route::get('contracts', [ContractController::class, 'index']);
//     Route::post('contracts', [ContractController::class, 'store']);
//     Route::get('contracts/{contract}', [ContractController::class, 'show']);
//     Route::put('contracts/{contract}', [ContractController::class, 'update']);
//     Route::delete('contracts/{contract}', [ContractController::class, 'destroy']);
//     Route::get('contracts/stats', [ContractController::class, 'stats']);

//     // Claims API
//     Route::get('claims', [ClaimController::class, 'index']);
//     Route::post('claims', [ClaimController::class, 'store']);
//     Route::get('claims/{claim}', [ClaimController::class, 'show']);
//     Route::put('claims/{claim}', [ClaimController::class, 'update']);
//     Route::delete('claims/{claim}', [ClaimController::class, 'destroy']);

//     // Reports API
//     Route::get('reports/contracts', [ReportController::class, 'contracts']);
//     Route::get('reports/claims', [ReportController::class, 'claims']);
// });

Route::prefix('v1')->group(function() {
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts', [ContractController::class, 'store']);
    Route::get('contracts/{contract}', [ContractController::class, 'show']);
    Route::put('contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy']);
    Route::get('contracts/stats', [ContractController::class, 'stats']);

    // Claims API
    Route::get('claims', [ClaimController::class, 'index']);
    Route::post('claims', [ClaimController::class, 'store']);
    Route::get('claims/{claim}', [ClaimController::class, 'show']);
    Route::put('claims/{claim}', [ClaimController::class, 'update']);
    Route::delete('claims/{claim}', [ClaimController::class, 'destroy']);

    // Reports API
    Route::get('reports/contracts', [ReportController::class, 'contracts']);
    Route::get('reports/claims', [ReportController::class, 'claims']);
});

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
