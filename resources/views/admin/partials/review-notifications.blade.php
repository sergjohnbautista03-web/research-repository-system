<section class="notification-group">
    <h3>Research Ready for Review</h3>
    @forelse($researches as $research)
        <a class="notification-item" href="{{ route('admin.research.show', $research) }}">
            <strong>{{ $research->title }}</strong>
            <span>Research submitted by the Research Coordinator is ready for final checking.</span>
            <span>{{ $research->department }} &middot; {{ $research->updated_at->format('M d, Y h:i A') }}</span>
            <small>Review Research &rarr;</small>
        </a>
    @empty
        <p class="notification-empty">No research waiting for final checking.</p>
    @endforelse
</section>
