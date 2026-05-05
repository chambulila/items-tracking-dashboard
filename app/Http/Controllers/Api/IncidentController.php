<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    private const TRANSITIONS = [
        'submitted' => ['under_review', 'escalated', 'closed'],
        'under_review' => ['escalated', 'in_progress', 'closed'],
        'escalated' => ['in_progress', 'resolved', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved' => ['closed'],
        'closed' => [],
    ];

    public function index(Request $request): JsonResponse
    {
        $incidents = Incident::query()
            ->with(['category', 'campus', 'assignee'])
            ->when(! $request->user()->hasPermission('manage-incidents'), fn ($query) => $query->where('reporter_id', $request->user()->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('campus_id'), fn ($query) => $query->where('campus_id', $request->integer('campus_id')))
            ->latest()
            ->paginate(20);

        return response()->json($incidents);
    }

    public function store(Request $request, AttachmentService $attachments, AuditLogger $auditLogger, NotificationService $notifications): JsonResponse
    {
        $data = $request->validate([
            'incident_category_id' => ['required', 'exists:incident_categories,id'],
            'campus_id' => ['required', 'exists:campuses,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'description' => ['required', 'string', 'min:10'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $incident = DB::transaction(function () use ($data, $request, $attachments, $auditLogger, $notifications): Incident {
            $incident = Incident::query()->create([
                ...$data,
                'reporter_id' => $request->user()->id,
                'status' => 'submitted',
            ]);
            $incident->updates()->create(['user_id' => $request->user()->id, 'to_status' => 'submitted', 'notes' => 'Incident submitted.']);
            $attachments->storeMany($incident, $request->file('attachments', []), $request->user());
            $auditLogger->log('incident.created', $request->user(), $incident);

            $notifications->notifyPermissionHolders(
                permissions: ['view-incidents', 'manage-incidents'],
                title: 'New incident reported',
                message: 'A new incident has been reported: '.str($incident->description)->limit(80).'.',
                category: 'incident',
                type: in_array($incident->severity, ['high', 'critical'], true) ? 'danger' : 'warning',
                createdBy: $request->user(),
                entityType: Incident::class,
                entityId: $incident->id,
                actionUrl: route('admin.incidents.show', $incident),
            );

            return $incident;
        });

        return response()->json($incident->load(['updates', 'attachments']), 201);
    }

    public function show(Request $request, Incident $incident): JsonResponse
    {
        $this->authorizeIncident($request, $incident);

        return response()->json($incident->load(['category', 'campus', 'assignee', 'updates', 'attachments']));
    }

    public function updateStatus(Request $request, Incident $incident, AuditLogger $auditLogger): JsonResponse
    {
        abort_if(! $request->user()->hasPermission('manage-incidents'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(Incident::STATUSES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! in_array($data['status'], self::TRANSITIONS[$incident->status], true) && $data['status'] !== $incident->status) {
            return response()->json(['message' => 'Invalid status transition.'], 422);
        }

        $fromStatus = $incident->status;
        $incident->update([
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? $incident->assigned_to,
            'resolved_at' => $data['status'] === 'resolved' ? now() : $incident->resolved_at,
        ]);

        $incident->updates()->create([
            'user_id' => $request->user()->id,
            'from_status' => $fromStatus,
            'to_status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        $auditLogger->log('incident.status_changed', $request->user(), $incident, ['from' => $fromStatus, 'to' => $data['status']]);

        return response()->json($incident->fresh(['updates', 'assignee']));
    }

    private function authorizeIncident(Request $request, Incident $incident): void
    {
        abort_if(! $request->user()->hasPermission('manage-incidents') && $incident->reporter_id !== $request->user()->id, 403);
    }
}
