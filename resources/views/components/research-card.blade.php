<div class="research-card" data-search-label="{{ $research->title }}" data-search-detail="{{ $research->cardAuthorName() }}" data-search-url="{{ route('research.show', $research) }}">
    <div class="card-top-row">
        <div class="card-type-badge type-{{ Str::slug($research->getTypeLabel()) }}">Research: {{ $research->getTypeLabel() }}</div>
        <span class="card-view-counter" title="Views">Views {{ number_format($research->view_count) }}</span>
    </div>
    <h4 class="card-title">
        <a href="{{ route('research.show', $research) }}">{{ Str::limit($research->title, 80) }}</a>
    </h4>
    <p class="card-author">{{ $research->cardAuthorName() }}</p>
    <p class="card-dept">{{ Str::limit($research->department, 45) }}</p>
    <p class="card-abstract">{{ Str::limit($research->abstract, 120) }}</p>
    <div class="card-footer">
        <div class="card-years">
            <span class="card-year">Publication Year: {{ $research->year_published }}</span>
            @if($research->school_year)
                <span class="card-year">Academic Year: {{ $research->school_year }}</span>
            @endif
        </div>
        <a href="{{ route('research.show', $research) }}" class="card-details-btn">View Details</a>
    </div>
</div>
