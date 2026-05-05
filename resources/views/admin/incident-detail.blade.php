@extends('layouts.admin')

@section('title', 'Incident Details')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card content-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">{{ $incident->category->name }}</h3>
                    <span class="badge text-bg-{{ in_array($incident->severity, ['high', 'critical'], true) ? 'danger' : 'warning' }}">
                        {{ str($incident->severity)->title() }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="lead">{{ $incident->description }}</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9"><span class="badge text-bg-secondary">{{ str($incident->status)->replace('_', ' ')->title() }}</span></dd>
                        <dt class="col-sm-3">Reporter</dt>
                        <dd class="col-sm-9">{{ $incident->reporter->name }}</dd>
                        <dt class="col-sm-3">Campus</dt>
                        <dd class="col-sm-9">{{ $incident->campus->name }}</dd>
                        <dt class="col-sm-3">Building</dt>
                        <dd class="col-sm-9">{{ $incident->building?->name ?? 'Not specified' }}</dd>
                        <dt class="col-sm-3">Location</dt>
                        <dd class="col-sm-9">{{ $incident->latitude }}, {{ $incident->longitude }}</dd>
                        <dt class="col-sm-3">Assignee</dt>
                        <dd class="col-sm-9">{{ $incident->assignee?->name ?? 'Unassigned' }}</dd>
                        <dt class="col-sm-3">Reported</dt>
                        <dd class="col-sm-9">{{ $incident->created_at->toDayDateTimeString() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card content-card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Status History</h3></div>
                <div class="list-group list-group-flush">
                    @forelse ($incident->updates as $update)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ str($update->to_status)->replace('_', ' ')->title() }}</div>
                            <div class="small text-muted">{{ $update->user?->name ?? 'System' }} · {{ $update->created_at->diffForHumans() }}</div>
                            @if ($update->notes)
                                <p class="mb-0 mt-1 small">{{ $update->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-muted">No updates recorded.</div>
                    @endforelse
                </div>
            </div>

            <div class="card content-card">
                <div class="card-header"><h3 class="card-title mb-0">Attachments</h3></div>
                <div class="list-group list-group-flush">
                    @forelse ($incident->attachments as $attachment)
                        <a href="{{ Storage::disk($attachment->disk)->url($attachment->path) }}" class="list-group-item list-group-item-action" target="_blank">
                            {{ $attachment->original_name }}
                        </a>
                    @empty
                        <div class="list-group-item text-muted">No attachments uploaded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
