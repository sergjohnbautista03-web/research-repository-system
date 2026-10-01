(() => {
    const form = document.getElementById('dh-filters');
    if (!form) return;
    const feedback = document.getElementById('dh-feedback');
    let timer, controller, revision = 0;

    function filterUrl() {
        const target = new URL(form.action);
        target.search = new URLSearchParams(new FormData(form));
        return target;
    }

    async function refresh(target = filterUrl()) {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        document.getElementById('dh-results').setAttribute('aria-busy', 'true');
        feedback.textContent = 'Updating handoff records…';
        try {
            const response = await fetch(target, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('dh-results');
            if (!next || current !== revision) return;
            document.getElementById('dh-results').replaceWith(next);
            history.replaceState(null, '', target);
            feedback.textContent = 'Handoff records updated.';
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) feedback.textContent = 'Could not update handoff records.';
        } finally {
            if (current === revision) document.getElementById('dh-results').removeAttribute('aria-busy');
        }
    }

    form.addEventListener('input', event => {
        if (event.target.name !== 'search' || event.isComposing) return;
        clearTimeout(timer);
        timer = setTimeout(() => refresh(), 250);
    });
    form.addEventListener('change', event => { if (event.target.name !== 'search') refresh(); });
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('dh-clear').addEventListener('click', event => { event.preventDefault(); form.reset(); refresh(); });
    document.addEventListener('click', event => {
        const link = event.target.closest('#dh-results .dh-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
})();
