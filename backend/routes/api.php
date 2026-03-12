<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\CupboardController;
use App\Http\Controllers\Api\ItemController;
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

        Route::prefix('items')->group(function (): void {
            Route::get('/', [ItemController::class, 'index'])->middleware('permission:item.view');
            Route::post('/', [ItemController::class, 'store'])->middleware('permission:item.create');
            Route::get('/{item}', [ItemController::class, 'show'])->middleware('permission:item.view');
            Route::put('/{item}', [ItemController::class, 'update'])->middleware('permission:item.update');
            Route::delete('/{item}', [ItemController::class, 'destroy'])->middleware('permission:item.delete');

            // Step 10: Quantity Management
            Route::prefix('{item}/quantity')->group(function (): void {
                Route::post('/increase', [ItemController::class, 'increaseQuantity'])->middleware('permission:item.adjust-quantity');
                Route::post('/decrease', [ItemController::class, 'decreaseQuantity'])->middleware('permission:item.adjust-quantity');
            });

            // Step 10: Status Management
            Route::prefix('{item}/status')->group(function (): void {
                Route::post('/damaged', [ItemController::class, 'markAsDamaged'])->middleware('permission:status.update');
                Route::post('/missing', [ItemController::class, 'markAsMissing'])->middleware('permission:status.update');
                Route::post('/restore-damaged', [ItemController::class, 'restoreFromDamaged'])->middleware('permission:status.update');
                Route::post('/restore-missing', [ItemController::class, 'restoreFromMissing'])->middleware('permission:status.update');
            });
        });

        Route::prefix('borrows')->group(function (): void {
            Route::get('/', [BorrowController::class, 'index'])->middleware('permission:borrow.view');
            Route::post('/', [BorrowController::class, 'store'])->middleware('permission:borrow.create');
            Route::get('/{borrow}', [BorrowController::class, 'show'])->middleware('permission:borrow.view');
            Route::post('/{borrow}/return', [BorrowController::class, 'returnItems'])->middleware('permission:borrow.return');
        });

        Route::prefix('activity-logs')->group(function (): void {
            Route::get('/', [ActivityLogController::class, 'index'])->middleware('permission:audit.view');
        });
    });
});
