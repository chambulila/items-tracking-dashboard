<?php

use Illuminate\Support\Facades\Route;
use App\Models\Device;
use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\ItemClaim;
use App\Models\ItemMatch;
use App\Models\LostItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

Route::get('/', fn () => view('admin.dashboard', [
    'lostCount' => Schema::hasTable('lost_items') ? LostItem::query()->count() : 0,
    'foundCount' => Schema::hasTable('found_items') ? FoundItem::query()->count() : 0,
    'incidentCount' => Schema::hasTable('incidents') ? Incident::query()->count() : 0,
    'recoveredCount' => Schema::hasTable('lost_items') ? LostItem::query()->where('status', 'recovered')->count() : 0,
]));

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => view('admin.dashboard', [
        'lostCount' => LostItem::query()->count(),
        'foundCount' => FoundItem::query()->count(),
        'incidentCount' => Incident::query()->count(),
        'recoveredCount' => LostItem::query()->where('status', 'recovered')->count(),
    ]))->name('dashboard');

    Route::get('/users', fn () => view('admin.users', ['users' => User::query()->with('roles')->latest()->paginate(25)]))->name('users');
    Route::get('/lost-items', fn () => view('admin.items', ['title' => 'Lost Items', 'items' => LostItem::query()->with(['category', 'campus'])->latest()->paginate(25)]))->name('lost-items');
    Route::get('/found-items', fn () => view('admin.items', ['title' => 'Found Items', 'items' => FoundItem::query()->with(['category', 'campus'])->latest()->paginate(25)]))->name('found-items');
    Route::get('/claims', fn () => view('admin.claims', ['claims' => ItemClaim::query()->with(['foundItem', 'claimant'])->latest()->paginate(25)]))->name('claims');
    Route::get('/matches', fn () => view('admin.matches', ['matches' => ItemMatch::query()->with(['lostItem', 'foundItem'])->latest('score')->paginate(25)]))->name('matches');
    Route::get('/notifications', fn () => view('admin.notifications', ['notifications' => Notification::query()->with('user')->latest()->paginate(25)]))->name('notifications');
    Route::get('/devices', fn () => view('admin.devices', ['devices' => Device::query()->with('latestLocation')->latest()->paginate(25)]))->name('devices');
    Route::get('/devices/{device}', fn (Device $device) => view('admin.device-detail', ['device' => $device->load(['latestLocation', 'locations' => fn ($query) => $query->latest('recorded_at')->limit(100)])]))->name('devices.show');
    Route::get('/incidents', fn () => view('admin.incidents', ['incidents' => Incident::query()->with(['category', 'campus', 'assignee'])->latest()->paginate(25)]))->name('incidents');
    Route::get('/reports', fn () => view('admin.reports'))->name('reports');
});
