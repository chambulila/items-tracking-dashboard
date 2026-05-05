<form method="GET" action="{{ $route }}" class="row g-3">
    <div class="col-md-3">
        <label for="keyword" class="form-label">Keyword</label>
        <input id="keyword" type="search" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Name, color, serial, description">
    </div>
    <div class="col-md-2">
        <label for="category_id" class="form-label">Category</label>
        <select id="category_id" name="category_id" class="form-select">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $category->id === request('category_id'))>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label for="campus_id" class="form-label">Campus</label>
        <select id="campus_id" name="campus_id" class="form-select">
            <option value="">All campuses</option>
            @foreach ($campuses as $campus)
                <option value="{{ $campus->id }}" @selected((string) $campus->id === request('campus_id'))>{{ $campus->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected($status === request('status'))>{{ str($status)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1">
        <label for="from" class="form-label">From</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}" class="form-control">
    </div>
    <div class="col-md-1">
        <label for="to" class="form-label">To</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}" class="form-control">
    </div>
    <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</form>
