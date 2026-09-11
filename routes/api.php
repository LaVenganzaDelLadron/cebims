<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BorrowRequestController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::get('equipment', [EquipmentController::class, 'index']);
Route::get('equipment/{equipment}', [EquipmentController::class, 'show']);
Route::get('home-equipment', [EquipmentController::class, 'homeEquipment']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('email/verification-notification', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::post('email/verification-notification', [AuthController::class, 'sendVerification'])->middleware('throttle:6,1');
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::get('borrow-requests/{borrowRequest}', [BorrowRequestController::class, 'show']);

    Route::middleware('admin')->group(function (): void {
        Route::prefix('admin')->group(function (): void {
            Route::get('dashboard', [AdminController::class, 'dashboard']);
            Route::get('users', [AdminController::class, 'users']);
            Route::get('users/{user}', [AdminController::class, 'showUser']);
            Route::put('users/{user}', [AdminController::class, 'updateUser']);
            Route::delete('users/{user}', [AdminController::class, 'destroyUser']);
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show'])->names('admin.categories');
            Route::apiResource('equipment', EquipmentController::class)->except(['index', 'show'])->names('admin.equipment');
            Route::get('borrow-requests', [BorrowRequestController::class, 'index']);
            Route::put('borrow-requests/{borrowRequest}/approve', [BorrowRequestController::class, 'approve']);
            Route::put('borrow-requests/{borrowRequest}/reject', [BorrowRequestController::class, 'reject']);
            Route::get('transactions', [TransactionController::class, 'borrowedItems']);
            Route::put('transactions/{transaction}/return', [TransactionController::class, 'returnEquipment']);
            Route::get('history', [TransactionController::class, 'history']);
            Route::get('audit-logs', [AdminController::class, 'auditLogs']);
        });
    });

    Route::middleware('role:user')->group(function (): void {
        Route::get('borrow-requests', [BorrowRequestController::class, 'index']);
        Route::get('my-borrowings', [BorrowRequestController::class, 'myBorrowings']);
        Route::post('borrow-requests', [BorrowRequestController::class, 'store']);
    });
});
