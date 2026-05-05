<?php

namespace App\Http\Controllers;

use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\LostItem;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        $canViewAll = $request->user()->hasPermission('view-whole-dashboard');
        return view('admin.dashboard', [
            'lostCount' => LostItem::query()->when(!$canViewAll, fn ($query) => $query->where('user_id', $request->user()->id))->count(),
            'foundCount' => FoundItem::query()->when(!$canViewAll, fn ($query) => $query->where('finder_id', $request->user()->id))->count(),
            'incidentCount' => Incident::query()->when(!$canViewAll, fn ($query) => $query->where('reporter_id', $request->user()->id))->count(),
            'recoveredCount' => LostItem::query()->when(!$canViewAll, fn ($query) => $query->where('user_id', $request->user()->id))->where('status', 'recovered')->count(),
        ]);
        
    }
}
