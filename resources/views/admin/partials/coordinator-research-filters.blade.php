<form method="GET" class="coord-card coord-filter">
    <div class="coord-field">
        <label for="coord-search">Search</label>
        <input id="coord-search" type="text" name="search" value="{{ request('search') }}" placeholder="Title, author, or keyword">
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

    <div class="coord-field">
        <label for="coord-publication-year">Publication Year</label>
        <select id="coord-publication-year" name="year">
            <option value="">All Publication Years</option>
            @foreach(range(2022, 2026) as $year)
                <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
            @endforeach
        </select>
    </div>
    <div class="coord-field">
        <label for="coord-academic-year">Academic Year</label>
        <select id="coord-academic-year" name="school_year">
            <option value="">All Academic Years</option>
            @foreach(['2022-2023', '2023-2024', '2024-2025', '2025-2026'] as $schoolYear)
                <option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>
            @endforeach
        </select>
    </div>

    <div class="coord-actions">
        <button type="submit" class="coord-btn coord-btn-primary">Filter</button>
        <a href="{{ url()->current() }}" class="coord-btn coord-btn-soft">Clear</a>
    </div>
</form>
