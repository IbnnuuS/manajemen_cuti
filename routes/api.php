<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveInfoController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- LEAVE TYPES & BALANCES ---
    Route::get('/leave-types', [LeaveInfoController::class, 'getLeaveTypes']);
    Route::get('/my-leave-balances', [LeaveInfoController::class, 'getMyBalances']);

    // --- USER ENDPOINTS ---
    Route::get('/my-leave-requests', [LeaveRequestController::class, 'myRequests']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'submit']);
    Route::post('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel']);

    // --- ADMIN ENDPOINTS ---
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);

    // --- SHARED ENDPOINTS ---
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy']);
});
