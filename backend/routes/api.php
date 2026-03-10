<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CupboardController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::prefix('users')->group(function (): void {
            Route::get('/', [UserManagementController::class, 'index'])->middleware('permission:user.view');
            Route::post('/', [UserManagementController::class, 'store'])->middleware('permission:user.create');
            Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('permission:user.update');
            Route::put('/{user}/assign-role', [UserManagementController::class, 'assignRole'])->middleware('permission:user.assign-role');
        });

        Route::prefix('cupboards')->group(function (): void {
            Route::get('/', [CupboardController::class, 'index'])->middleware('permission:cupboard.view');
            Route::post('/', [CupboardController::class, 'store'])->middleware('permission:cupboard.create');
            Route::get('/{cupboard}', [CupboardController::class, 'show'])->middleware('permission:cupboard.view');
            Route::put('/{cupboard}', [CupboardController::class, 'update'])->middleware('permission:cupboard.update');
            Route::delete('/{cupboard}', [CupboardController::class, 'destroy'])->middleware('permission:cupboard.delete');

            Route::get('/{cupboard}/places', [PlaceController::class, 'byCupboard'])->middleware('permission:place.view');
        });

        Route::prefix('places')->group(function (): void {
            Route::get('/', [PlaceController::class, 'index'])->middleware('permission:place.view');
            Route::post('/', [PlaceController::class, 'store'])->middleware('permission:place.create');
            Route::get('/{place}', [PlaceController::class, 'show'])->middleware('permission:place.view');
            Route::put('/{place}', [PlaceController::class, 'update'])->middleware('permission:place.update');
            Route::delete('/{place}', [PlaceController::class, 'destroy'])->middleware('permission:place.delete');
        });
    });
});
