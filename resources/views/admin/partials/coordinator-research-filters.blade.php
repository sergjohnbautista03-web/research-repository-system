<form method="GET" class="coord-card coord-filter">
    <div class="coord-field">
        <label for="coord-search">Search</label>
        <input id="coord-search" type="text" name="search" value="{{ request('search') }}" placeholder="Title, author, keyword, or academic year">
    </div>

    <div class="coord-field">
        <label for="coord-type">Type</label>
        <select id="coord-type" name="type">
            <option value="">All</option>
            @foreach($researchTypes ?? [] as $type)
                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>

    @if($showStatusFilter ?? true)
        <div class="coord-field">
            <label for="coord-status">Status</label>
            <select id="coord-status" name="status">
                <option value="">All</option>
                @foreach($statusOptions ?? [] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="coord-actions">
        <button type="submit" class="coord-btn coord-btn-primary">Filter</button>
        <a href="{{ url()->current() }}" class="coord-btn coord-btn-soft">Clear</a>
    </div>
</form>
