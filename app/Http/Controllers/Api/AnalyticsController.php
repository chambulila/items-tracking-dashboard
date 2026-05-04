<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\LostItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lostItems = $this->applyFilters(LostItem::query(), $request, 'lost_date');
        $foundItems = $this->applyFilters(FoundItem::query(), $request, 'found_date');
        $incidents = $this->applyFilters(Incident::query(), $request, 'created_at');

        $totalLost = (clone $lostItems)->count();
        $recovered = (clone $lostItems)->where('status', 'recovered')->count();

        return response()->json([
            'metrics' => [
                'total_lost_items' => $totalLost,
                'total_found_items' => (clone $foundItems)->count(),
                'recovered_items' => $recovered,
                'unclaimed_items' => (clone $foundItems)->where('status', 'unclaimed')->count(),
                'incident_statistics' => (clone $incidents)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
                'recovery_rate' => $totalLost === 0 ? 0 : round(($recovered / $totalLost) * 100, 2),
            ],
            'reports' => [
                'common_locations' => (clone $lostItems)->select('campus_id', DB::raw('count(*) as total'))->with('campus')->groupBy('campus_id')->orderByDesc('total')->limit(5)->get(),
                'average_resolution_hours' => $this->averageResolutionHours(clone $incidents),
            ],
        ]);
    }

    private function applyFilters($query, Request $request, string $dateColumn)
    {
        return $query
            ->when($request->filled('campus_id'), fn ($query) => $query->where('campus_id', $request->integer('campus_id')))
            ->when($request->filled('category_id') && $query->getModel() instanceof Incident === false, fn ($query) => $query->where('item_category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate($dateColumn, '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate($dateColumn, '<=', $request->date('to')));
    }

    private function averageResolutionHours($incidents): float
    {
        $resolved = $incidents->whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);

        if ($resolved->isEmpty()) {
            return 0.0;
        }

        return round($resolved->avg(fn (Incident $incident) => $incident->created_at->diffInMinutes($incident->resolved_at) / 60), 2);
    }
}
