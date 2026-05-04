<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\IncidentCategory;
use App\Models\ItemCategory;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'roles' => Role::query()->orderBy('name')->get(),
            'campuses' => Campus::query()->with('buildings')->orderBy('name')->get(),
            'item_categories' => ItemCategory::query()->orderBy('name')->get(),
            'incident_categories' => IncidentCategory::query()->orderBy('name')->get(),
        ]);
    }
}
