<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveInfoController;

use App\Http\Controllers\AdminController;

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
    Route::get('/admin/dashboard-stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/admin/users', [AdminController::class, 'getUsersPaginated']);
    Route::post('/admin/users', [AdminController::class, 'storeUser']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser']);
    
    Route::get('/admin/leave-requests', [LeaveRequestController::class, 'index']); // Specific for Admin History
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']); // Legacy backward compat
    
    Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);

    // --- SHARED ENDPOINTS ---
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy']);
});
