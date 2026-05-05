<?php

use App\Http\Controllers\Admin\FoundItemController;
use App\Http\Controllers\Admin\ItemClaimController;
use App\Http\Controllers\Admin\LostItemController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Models\Device;
use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\ItemMatch;
use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.dashboard' : 'login'));

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function (): void {
    Route::get('/', [HomeController::class, 'dashboard'])->middleware('permission:view-dashboard')->name('dashboard');

    Route::resource('users', UserController::class)->middleware(['permission:manage-users', 'permission:assign-roles']);
    Route::get('/role-permissions', [RolePermissionController::class, 'index'])
        ->middleware('permission:manage-role-permissions')
        ->name('role-permissions.index');
    Route::put('/role-permissions/{role}', [RolePermissionController::class, 'update'])
        ->middleware('permission:manage-role-permissions')
        ->name('role-permissions.update');

    Route::get('/lost-items', [LostItemController::class, 'index'])->middleware('permission:view-lost-items')->name('lost-items');
    Route::get('/lost-items/create', [LostItemController::class, 'create'])->middleware('permission:create-lost-items')->name('lost-items.create');
    Route::post('/lost-items', [LostItemController::class, 'store'])->middleware('permission:create-lost-items')->name('lost-items.store');
    Route::get('/lost-items/{lostItem}', [LostItemController::class, 'show'])->middleware('permission:view-lost-items')->name('lost-items.show');
    Route::patch('/lost-items/{lostItem}/recovered', [LostItemController::class, 'markRecovered'])->middleware('permission:update-lost-items,manage-lost-found')->name('lost-items.recovered');

    Route::get('/found-items', [FoundItemController::class, 'index'])->middleware('permission:view-found-items')->name('found-items');
    Route::get('/found-items/create', [FoundItemController::class, 'create'])->middleware('permission:create-found-items')->name('found-items.create');
    Route::post('/found-items', [FoundItemController::class, 'store'])->middleware('permission:create-found-items')->name('found-items.store');
    Route::get('/found-items/{foundItem}', [FoundItemController::class, 'show'])->middleware('permission:view-found-items')->name('found-items.show');
    Route::post('/found-items/{foundItem}/claims', [ItemClaimController::class, 'store'])->middleware('permission:claim-found-items')->name('found-items.claims.store');

    Route::get('/claims', [ItemClaimController::class, 'index'])->middleware('permission:view-claims,verify-claims,manage-lost-found')->name('claims');
    Route::patch('/claims/{claim}/verify', [ItemClaimController::class, 'verify'])
        ->middleware('permission:verify-claims,manage-lost-found')
        ->name('claims.verify');
    Route::get('/matches', fn () => view('admin.matches', ['matches' => ItemMatch::query()->with(['lostItem', 'foundItem'])->latest('score')->paginate(25)]))->middleware('permission:view-matches,manage-lost-found')->name('matches');
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:view-notifications')->name('notifications');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->middleware('permission:view-notifications')->name('notifications.feed');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->middleware('permission:view-notifications')->name('notifications.unread-count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->middleware('permission:manage-notifications')->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->middleware('permission:manage-notifications')->name('notifications.read');
    Route::get('/devices', fn () => view('admin.devices', ['devices' => Device::query()->with('latestLocation')->latest()->paginate(25)]))->middleware('permission:view-devices')->name('devices');
    Route::get('/devices/{device}', fn (Device $device) => view('admin.device-detail', ['device' => $device->load(['latestLocation', 'locations' => fn ($query) => $query->latest('recorded_at')->limit(100)])]))->middleware('permission:view-devices')->name('devices.show');
    Route::get('/incidents', fn () => view('admin.incidents', ['incidents' => Incident::query()->with(['category', 'campus', 'assignee'])->latest()->paginate(25)]))->middleware('permission:view-incidents')->name('incidents');
    Route::get('/incidents/{incident}', function (Request $request, Incident $incident) {
        abort_if(! $request->user()->hasPermission('view-incidents', 'manage-incidents') && $incident->reporter_id !== $request->user()->id, 403);

        return view('admin.incident-detail', [
            'incident' => $incident->load(['category', 'campus', 'building', 'assignee', 'reporter', 'updates.user', 'attachments']),
        ]);
    })->middleware('permission:view-incidents')->name('incidents.show');
    Route::get('/reports', fn () => view('admin.reports'))->middleware('permission:view-analytics')->name('reports');
});
