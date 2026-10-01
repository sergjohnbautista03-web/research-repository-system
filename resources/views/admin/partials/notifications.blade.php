<dialog id="notificationPanel" class="notification-panel" aria-labelledby="notificationPanelTitle"
    data-notification-panel data-feed-url="{{ route('admin.notifications.feed') }}"
    data-read-url="{{ route('admin.notifications.read') }}" data-read-all-url="{{ route('admin.notifications.read-all') }}"
    data-notification-view>
    <header class="notification-panel-header">
        <h2 id="notificationPanelTitle">Notifications</h2>
        <button type="button" id="notificationClose" aria-label="Close notifications" autofocus>&times;</button>
    </header>
    @include('admin.partials.notification-controls')
</dialog>
<script src="{{ asset('js/notifications.js') }}?v={{ filemtime(public_path('js/notifications.js')) }}" defer></script>
