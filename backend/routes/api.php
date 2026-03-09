<?php

use App\Http\Controllers\Api\AuthController;
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
    });
});
