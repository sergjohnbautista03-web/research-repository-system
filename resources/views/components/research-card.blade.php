<div class="research-card">
    <div class="card-type-badge type-{{ $research->type }}">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</div>
    <h4 class="card-title">
        <a href="{{ route('research.show', $research) }}">{{ Str::limit($research->title, 80) }}</a>
    </h4>
    <p class="card-author">{{ $research->author_name }}</p>
    <p class="card-dept">{{ Str::limit($research->department, 45) }}</p>
    <p class="card-abstract">{{ Str::limit($research->abstract, 120) }}</p>
    <div class="card-footer">
        <span class="card-year">{{ $research->year_published }}</span>
        <div class="card-stats">
            <span title="Views">Views {{ number_format($research->view_count) }}</span>
        </div>
        <a href="{{ route('research.show', $research) }}" class="card-details-btn">View Details</a>
    </div>
</div>
