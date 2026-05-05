<div class="card content-card">
    <div class="card-body table-responsive p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Name</th><th>Category</th><th>Campus</th><th>Date</th><th>Status</th><th>Reported By</th><th></th></tr></thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->category->name }}</td>
                        <td>{{ $item->campus->name }}</td>
                        <td>{{ ($type === 'lost' ? $item->lost_date : $item->found_date)->toFormattedDateString() }}</td>
                        <td><span class="badge text-bg-secondary">{{ str($item->status)->replace('_', ' ')->title() }}</span></td>
                        <td>{{ $type === 'lost' ? $item->user->name : $item->finder->name }}</td>
                        <td class="text-end">
                            <a href="{{ $type === 'lost' ? route('admin.lost-items.show', $item) : route('admin.found-items.show', $item) }}" class="btn btn-primary btn-sm">Track</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>
