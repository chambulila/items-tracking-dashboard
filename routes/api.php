<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\LostFoundController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReferenceController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function (): void {
    Route::get('/me', [AuthController::class, 'profile']);
    Route::patch('/me', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/references', [ReferenceController::class, 'index'])->middleware('permission:view-references');

    Route::get('/lost-items', [LostFoundController::class, 'lostIndex'])->middleware('permission:view-lost-items');
    Route::post('/lost-items', [LostFoundController::class, 'storeLost'])->middleware('permission:create-lost-items');
    Route::get('/found-items', [LostFoundController::class, 'foundIndex'])->middleware('permission:view-found-items');
    Route::post('/found-items', [LostFoundController::class, 'storeFound'])->middleware('permission:create-found-items');
    Route::post('/found-items/{foundItem}/claims', [LostFoundController::class, 'claim'])->middleware('permission:claim-found-items');

    Route::get('/devices', [DeviceController::class, 'index'])->middleware('permission:view-devices');
    Route::post('/devices', [DeviceController::class, 'store'])->middleware('permission:manage-devices');
    Route::get('/devices/{device}', [DeviceController::class, 'show'])->middleware('permission:view-devices');
    Route::patch('/devices/{device}', [DeviceController::class, 'updateStatus'])->middleware('permission:manage-devices');
    Route::get('/devices/{device}/status', [DeviceController::class, 'status'])->middleware('permission:view-devices');
    Route::post('/devices/{device}/location', [DeviceController::class, 'location'])->middleware('permission:manage-devices');

    Route::get('/incidents', [IncidentController::class, 'index'])->middleware('permission:view-incidents');
    Route::post('/incidents', [IncidentController::class, 'store'])->middleware('permission:create-incidents');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->middleware('permission:view-incidents');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:view-notifications');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->middleware('permission:manage-notifications');

    Route::middleware('permission:verify-claims,manage-lost-found')->group(function (): void {
        Route::patch('/claims/{claim}/verify', [LostFoundController::class, 'verifyClaim']);
    });

    Route::middleware('permission:view-matches,manage-lost-found')->group(function (): void {
        Route::get('/matches', [LostFoundController::class, 'matches']);
    });

    Route::middleware('permission:manage-incidents')->group(function (): void {
        Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    });

    Route::middleware('permission:view-analytics')->group(function (): void {
        Route::get('/analytics', [AnalyticsController::class, 'index']);
    });

    Route::middleware('permission:manage-users')->group(function (): void {
        Route::get('/admin/users', [AdminController::class, 'users']);
    });

    Route::middleware('permission:assign-roles')->group(function (): void {
        Route::patch('/admin/users/{user}/roles', [AdminController::class, 'updateUserRoles']);
    });
});
