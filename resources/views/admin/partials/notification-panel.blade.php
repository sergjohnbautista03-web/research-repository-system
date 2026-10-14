

    <section class="notification-group">
        <h3>New Journal Tasks</h3>
        @forelse($newHandoffs as $item)
            <a class="notification-item" href="{{ route('admin.coordinator.dean-submissions') }}">
                <strong>{{ $item->title }}</strong>
                <span>The Dean assigned “{{ $item->title }}” for AY {{ $item->school_year }}. Use the approved research papers already available to prepare the journal.</span>
                <span>{{ $item->dean?->name ?? 'Department Dean' }} / {{ $item->created_at->format('M d, Y h:i A') }}</span>
                <small>Open Task &rarr;</small>
            </a>
        @empty
            <p class="notification-empty">No new journal tasks.</p>
        @endforelse
    </section>
