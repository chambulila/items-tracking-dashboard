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
    Route::get('/references', [ReferenceController::class, 'index']);

    Route::get('/lost-items', [LostFoundController::class, 'lostIndex']);
    Route::post('/lost-items', [LostFoundController::class, 'storeLost']);
    Route::get('/found-items', [LostFoundController::class, 'foundIndex']);
    Route::post('/found-items', [LostFoundController::class, 'storeFound']);
    Route::post('/found-items/{foundItem}/claims', [LostFoundController::class, 'claim']);

    Route::get('/devices', [DeviceController::class, 'index']);
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::get('/devices/{device}', [DeviceController::class, 'show']);
    Route::patch('/devices/{device}', [DeviceController::class, 'updateStatus']);
    Route::get('/devices/{device}/status', [DeviceController::class, 'status']);
    Route::post('/devices/{device}/location', [DeviceController::class, 'location']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::middleware('role:administrator,security_officer')->group(function (): void {
        Route::patch('/claims/{claim}/verify', [LostFoundController::class, 'verifyClaim']);
        Route::get('/matches', [LostFoundController::class, 'matches']);
        Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
        Route::get('/analytics', [AnalyticsController::class, 'index']);
    });

    Route::middleware('role:administrator')->group(function (): void {
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::patch('/admin/users/{user}/roles', [AdminController::class, 'updateUserRoles']);
    });
});
