<div class="research-card">
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
        <span class="card-year">{{ $research->year_published }}</span>
        <a href="{{ route('research.show', $research) }}" class="card-details-btn">View Details</a>
    </div>
</div>
