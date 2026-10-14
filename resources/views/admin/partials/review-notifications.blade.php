<section class="notification-group">
    <h3>Journals Forwarded by the Dean Ready to Publish</h3>
    @forelse($researches as $research)
        <a class="notification-item" href="{{ route('admin.research.show', $research) }}">
            <strong>{{ $research->title }}</strong>
            @include('components.research-id', ['research' => $research])
            <span>The Department Dean has forwarded this journal for publishing.</span>
            <span>{{ $research->department }} &middot; {{ $research->updated_at->format('M d, Y h:i A') }}</span>
            <small>Publish Journal &rarr;</small>
        </a>
    @empty
        <p class="notification-empty">No journals forwarded by the Dean waiting for publishing.</p>
    @endforelse
</section>
