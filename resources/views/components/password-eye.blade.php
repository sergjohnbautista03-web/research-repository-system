@props(['target'])
<button type="button" class="ud-password-eye" onclick="togglePassword('{{ $target }}', this)" aria-label="Show password" aria-controls="{{ $target }}" aria-pressed="false">
    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><path d="m3 3 18 18"/></svg>
</button>
