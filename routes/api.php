<?php

use App\Http\Controllers\Api\Client\ClaimController;
use App\Http\Controllers\Api\Client\ContractController;
use App\Http\Controllers\Api\Client\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts', [ContractController::class, 'store']);
    Route::get('contracts/{contract}', [ContractController::class, 'show']);
    Route::put('contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy']);
    Route::get('contracts/stats', [ContractController::class, 'stats']);

    Route::get('claims', [ClaimController::class, 'index']);
    Route::post('claims', [ClaimController::class, 'store']);
    Route::get('claims/{claim}', [ClaimController::class, 'show']);
    Route::put('claims/{claim}', [ClaimController::class, 'update']);
    Route::delete('claims/{claim}', [ClaimController::class, 'destroy']);
    Route::put('/client/claims/{claim}', [ClaimController::class, 'update'])->name('client.claims.update');

    Route::post('reports', [ReportController::class, 'fetch']);
    Route::get('reports/claims', [ReportController::class, 'claims']);
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Неверные учетные данные'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('api-token', ['*'])->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
});

Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
