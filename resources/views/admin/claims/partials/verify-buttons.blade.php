<div class="d-flex gap-2">
    <form method="POST" action="{{ route('admin.claims.verify', $claim) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="approved">
        <button type="submit" class="btn btn-success btn-sm">Approve</button>
    </form>
    <form method="POST" action="{{ route('admin.claims.verify', $claim) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="rejected">
        <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
    </form>
</div>
