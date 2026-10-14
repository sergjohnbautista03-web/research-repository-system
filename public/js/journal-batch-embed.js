(() => {
    if (!document.body.hasAttribute('data-journal-batch-embedded') || window.parent === window) return;
    const send = type => window.parent.postMessage({ type }, window.location.origin);
    const closeOnEscape = event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            send('journal-batch-close');
        }
    };
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('click', event => {
        if (event.target.closest('[data-close-embedded]')) {
            event.preventDefault();
            send('journal-batch-close');
        }
    });
    document.querySelectorAll('[data-journal-review-frame]').forEach(frame => {
        const listenForEscape = () => {
            try { frame.contentDocument?.addEventListener('keydown', closeOnEscape); } catch { /* Viewer unavailable. */ }
        };
        frame.addEventListener('load', listenForEscape);
        listenForEscape();
    });
    if (document.body.hasAttribute('data-batch-updated')) {
        send('journal-batch-updated');
        if (document.body.hasAttribute('data-close-on-success')) send('journal-batch-close');
    }
})();
