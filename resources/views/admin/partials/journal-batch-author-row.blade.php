<div class="jb-author-row" data-author-row>
    <input type="text" name="authors[]" value="{{ $author }}" placeholder="{{ $student ? 'Enter researcher name' : 'Enter co-author name' }}" aria-label="{{ $student ? 'Researcher name' : 'Co-author name' }}" required maxlength="150">
    <button type="button" class="jb-remove-author" data-remove-author aria-label="Remove author" @disabled($single)>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
    </button>
</div>
