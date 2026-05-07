<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Services\DeviceTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $devices = Device::query()
            ->with(['user', 'lostItem', 'latestLocation'])
            ->when(! $request->user()->hasPermission('manage-devices'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->boolean('lost'), fn ($query) => $query->where('is_lost', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.devices', [
            'devices' => $devices,
            'showingLostOnly' => $request->boolean('lost'),
        ]);
    }

    public function show(Device $device): View
    {
        return view('admin.device-detail', [
            'device' => $device->load([
                'user',
                'lostItem.category',
                'latestLocation',
                'locations' => fn ($query) => $query->latest('recorded_at')->limit(100),
            ]),
            'auditLogs' => AuditLog::query()
                ->with('user')
                ->where('auditable_type', $device->getMorphClass())
                ->where('auditable_id', $device->id)
                ->latest()
                ->limit(25)
                ->get(),
        ]);
    }

    public function locations(Device $device): JsonResponse
    {
        $query = $device->locations()
            ->oldest('recorded_at')
            ->oldest('id');

        if (request()->filled('since_id')) {
            $query->where('id', '>', request()->integer('since_id'));
        }

        return response()->json([
            'data' => $query
                ->limit(100)
                ->get()
                ->values(),
        ]);
    }

    public function latestLocation(Device $device): JsonResponse
    {
        return response()->json([
            'device' => $device->fresh(['latestLocation']),
            'latest_location' => $device->latestLocation,
        ]);
    }

    public function enableTracking(Request $request, Device $device, DeviceTrackingService $tracking): RedirectResponse
    {
        $tracking->enableActiveSearch($device, $request->user());

        return back()->with('status', 'Active Search Mode enabled.');
    }

    public function disableTracking(Request $request, Device $device, DeviceTrackingService $tracking): RedirectResponse
    {
        $tracking->disableTracking($device, $request->user());

        return back()->with('status', 'Device tracking disabled.');
    }

    public function markRecovered(Request $request, Device $device, DeviceTrackingService $tracking): RedirectResponse
    {
        $tracking->markRecovered($device, $request->user());

        return back()->with('status', 'Device marked as recovered.');
    }
}
