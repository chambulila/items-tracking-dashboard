<?php

use App\Http\Controllers\Admin\FoundItemController;
use App\Http\Controllers\Admin\ItemClaimController;
use App\Http\Controllers\Admin\LostItemController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
    Route::get('/', fn () => view('admin.dashboard', [
        'lostCount' => LostItem::query()->count(),
        'foundCount' => FoundItem::query()->count(),
        'incidentCount' => Incident::query()->count(),
        'recoveredCount' => LostItem::query()->where('status', 'recovered')->count(),
    ]))->name('dashboard');

    Route::resource('users', UserController::class)->middleware('role:administrator');

    Route::get('/lost-items', [LostItemController::class, 'index'])->name('lost-items');
    Route::get('/lost-items/create', [LostItemController::class, 'create'])->name('lost-items.create');
    Route::post('/lost-items', [LostItemController::class, 'store'])->name('lost-items.store');
    Route::get('/lost-items/{lostItem}', [LostItemController::class, 'show'])->name('lost-items.show');
    Route::patch('/lost-items/{lostItem}/recovered', [LostItemController::class, 'markRecovered'])->name('lost-items.recovered');

    Route::get('/found-items', [FoundItemController::class, 'index'])->name('found-items');
    Route::get('/found-items/create', [FoundItemController::class, 'create'])->name('found-items.create');
    Route::post('/found-items', [FoundItemController::class, 'store'])->name('found-items.store');
    Route::get('/found-items/{foundItem}', [FoundItemController::class, 'show'])->name('found-items.show');
    Route::post('/found-items/{foundItem}/claims', [ItemClaimController::class, 'store'])->name('found-items.claims.store');

    Route::get('/claims', [ItemClaimController::class, 'index'])->name('claims');
    Route::patch('/claims/{claim}/verify', [ItemClaimController::class, 'verify'])
        ->middleware('role:administrator,security_officer')
        ->name('claims.verify');
    Route::get('/matches', fn () => view('admin.matches', ['matches' => ItemMatch::query()->with(['lostItem', 'foundItem'])->latest('score')->paginate(25)]))->name('matches');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/devices', fn () => view('admin.devices', ['devices' => Device::query()->with('latestLocation')->latest()->paginate(25)]))->name('devices');
    Route::get('/devices/{device}', fn (Device $device) => view('admin.device-detail', ['device' => $device->load(['latestLocation', 'locations' => fn ($query) => $query->latest('recorded_at')->limit(100)])]))->name('devices.show');
    Route::get('/incidents', fn () => view('admin.incidents', ['incidents' => Incident::query()->with(['category', 'campus', 'assignee'])->latest()->paginate(25)]))->name('incidents');
    Route::get('/incidents/{incident}', function (Request $request, Incident $incident) {
        abort_if(! $request->user()->hasPermission('view-incidents', 'manage-incidents') && $incident->reporter_id !== $request->user()->id, 403);

        return view('admin.incident-detail', [
            'incident' => $incident->load(['category', 'campus', 'building', 'assignee', 'reporter', 'updates.user', 'attachments']),
        ]);
    })->name('incidents.show');
    Route::get('/reports', fn () => view('admin.reports'))->name('reports');
});
