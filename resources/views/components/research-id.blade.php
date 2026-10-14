@if(auth()->user()?->isGlobalAdmin() && request()->routeIs('admin.*') && $research->research_id)
    <span class="research-id">Research ID: {{ $research->research_id }}</span>
@endif
