(() => {
    const frames = Array.from(document.querySelectorAll('[data-journal-review-frame]'));
    if (!frames.length) return;

    function requestHeight(frame) {
        frame.contentWindow?.postMessage({ type: 'journal-review-measure' }, window.location.origin);
    }

    window.addEventListener('message', event => {
        if (event.origin !== window.location.origin || event.data?.type !== 'journal-review-height') return;
        const frame = frames.find(candidate => candidate.contentWindow === event.source);
        const height = event.data.height;
        if (!frame || typeof height !== 'number' || !Number.isFinite(height) || height <= 0) return;
        frame.style.height = Math.ceil(Math.max(320, height)) + 'px';
    });

    frames.forEach(frame => {
        frame.addEventListener('load', () => requestHeight(frame));
        requestHeight(frame);
    });
})();
