<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BorrowRequestController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::get('equipment', [EquipmentController::class, 'index']);
Route::get('equipment/{equipment}', [EquipmentController::class, 'show']);
Route::get('home-equipment', [EquipmentController::class, 'homeEquipment']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::get('borrow-requests', [BorrowRequestController::class, 'index']);
    Route::post('borrow-requests', [BorrowRequestController::class, 'store']);
    Route::get('borrow-requests/{borrowRequest}', [BorrowRequestController::class, 'show']);

    Route::middleware('admin')->group(function (): void {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('equipment', EquipmentController::class)->except(['index', 'show']);
        Route::prefix('admin')->group(function (): void {
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show'])->names('admin.categories');
            Route::apiResource('equipment', EquipmentController::class)->except(['index', 'show'])->names('admin.equipment');
            Route::get('borrow-requests', [BorrowRequestController::class, 'index']);
            Route::put('borrow-requests/{borrowRequest}/approve', [BorrowRequestController::class, 'approve']);
            Route::put('borrow-requests/{borrowRequest}/reject', [BorrowRequestController::class, 'reject']);
            Route::get('transactions', [TransactionController::class, 'borrowedItems']);
            Route::put('transactions/{transaction}/return', [TransactionController::class, 'returnEquipment']);
            Route::get('history', [TransactionController::class, 'history']);
        });
    });
});
