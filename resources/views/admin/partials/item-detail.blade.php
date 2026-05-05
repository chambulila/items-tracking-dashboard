<div class="row g-3">
    <div class="col-lg-8">
        <div class="card content-card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">{{ $item->name }}</h3>
                <span class="badge text-bg-secondary ms-auto">{{ str($item->status)->replace('_', ' ')->title() }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $item->category->name }}</dd>
                    <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $item->description }}</dd>
                    <dt class="col-sm-3">Color</dt><dd class="col-sm-9">{{ $item->color ?? '-' }}</dd>
                    <dt class="col-sm-3">Brand / Model</dt><dd class="col-sm-9">{{ $item->brand_model ?? '-' }}</dd>
                    <dt class="col-sm-3">Serial / IMEI</dt><dd class="col-sm-9">{{ $item->serial_imei ?? '-' }}</dd>
                    <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ ($type === 'lost' ? $item->lost_date : $item->found_date)->toFormattedDateString() }}</dd>
                    <dt class="col-sm-3">Campus</dt><dd class="col-sm-9">{{ $item->campus->name }}</dd>
                    <dt class="col-sm-3">Building</dt><dd class="col-sm-9">{{ $item->building?->name ?? 'Manual / outdoor location' }}</dd>
                    <dt class="col-sm-3">Coordinates</dt><dd class="col-sm-9">{{ $item->latitude !== null && $item->longitude !== null ? $item->latitude.', '.$item->longitude : 'Not captured' }}</dd>
                    <dt class="col-sm-3">Reporter</dt><dd class="col-sm-9">{{ $type === 'lost' ? $item->user->name : $item->finder->name }}</dd>
                </dl>
            </div>
            @if ($type === 'lost' && $item->status === 'open' && (auth()->user()->isPrivileged() || auth()->id() === $item->user_id))
                <div class="card-footer">
                    <form method="POST" action="{{ route('admin.lost-items.recovered', $item) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">Mark Recovered</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="card content-card mt-3">
            <div class="card-header"><h3 class="card-title">Attachments</h3></div>
            <div class="card-body">
                @forelse ($item->attachments as $attachment)
                    <a class="btn btn-outline-secondary btn-sm me-2 mb-2" href="{{ asset('storage/'.$attachment->path) }}" target="_blank">
                        <i class="bi bi-paperclip"></i> {{ $attachment->original_name }}
                    </a>
                @empty
                    <p class="text-muted mb-0">No images or supporting evidence uploaded.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if ($type === 'found')
            <div class="card content-card">
                <div class="card-header"><h3 class="card-title">Submit Claim</h3></div>
                <div class="card-body">
                    @if ($item->status === 'unclaimed' && auth()->id() !== $item->finder_id)
                        <form method="POST" action="{{ route('admin.found-items.claims.store', $item) }}">
                            @csrf
                            <label for="proof_description" class="form-label">Ownership Proof</label>
                            <textarea id="proof_description" name="proof_description" rows="4" class="form-control @error('proof_description') is-invalid @enderror" required>{{ old('proof_description') }}</textarea>
                            @error('proof_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button type="submit" class="btn btn-success mt-3">Submit Claim</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">This item is not available for new claims.</p>
                    @endif
                </div>
            </div>

            <div class="card content-card mt-3">
                <div class="card-header"><h3 class="card-title">Claims</h3></div>
                <div class="card-body">
                    @forelse ($item->claims as $claim)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="fw-semibold">{{ $claim->claimant->name }} <span class="badge text-bg-secondary">{{ $claim->status }}</span></div>
                            <div class="small text-muted">{{ $claim->proof_description }}</div>
                            @if (auth()->user()->isPrivileged() && $claim->status === 'pending')
                                <div class="mt-2">@include('admin.claims.partials.verify-buttons', ['claim' => $claim])</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No claims submitted yet.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="card content-card">
            <div class="card-header"><h3 class="card-title">Possible Matches</h3></div>
            <div class="card-body">
                @forelse ($item->matches->sortByDesc('score') as $match)
                    @php($matchedItem = $type === 'lost' ? $match->foundItem : $match->lostItem)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">
                            <a href="{{ $type === 'lost' ? route('admin.found-items.show', $matchedItem) : route('admin.lost-items.show', $matchedItem) }}">{{ $matchedItem->name }}</a>
                            <span class="badge text-bg-success">{{ $match->score }}%</span>
                        </div>
                        <div class="small text-muted">{{ implode(', ', $match->reasons) }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No possible matches yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
