<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// API JSON consommée par l'app Flutter (auth par jeton Sanctum, sans session/cookie).
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verifier-code', [AuthController::class, 'verifierCode']);
    Route::post('/appareil/connecter', [AuthController::class, 'connecterAppareil']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/moi', [AuthController::class, 'moi']);
    });
});
