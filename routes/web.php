<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Frontend Auth Route
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// Protected Frontend Routes (Layout handles auth logic visually via Pinia, but Inertia pages should be accessible initially)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/requests/create', function () {
    return Inertia::render('LeaveRequestPage');
})->name('requests.create');

// Admin Frontend Routes
Route::get('/admin/requests', function () {
    return Inertia::render('AdminLeavePage');
})->name('admin.requests');

Route::get('/admin/users', function () {
    return Inertia::render('AdminUserPage');
})->name('admin.users');

