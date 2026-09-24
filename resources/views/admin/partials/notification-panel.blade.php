

    <section class="notification-group">
        <h3>New Research Received</h3>
        @forelse($newHandoffs as $item)
            <a class="notification-item" href="{{ route('admin.coordinator.dean-submissions') }}">
                <strong>{{ $item->title }}</strong>
                <span>The Dean sent an approved research entitled “{{ $item->title }}”.</span>
                <span>{{ $item->dean?->name ?? 'Department Dean' }} / {{ $item->created_at->format('M d, Y h:i A') }}</span>
                <small>Open Submission &rarr;</small>
            </a>
        @empty
            <p class="notification-empty">No new Dean submissions.</p>
        @endforelse
    </section>
