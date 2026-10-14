(() => {
    const form = document.getElementById('ct-filters');
    if (!form) return;
    const feedback = document.getElementById('ct-feedback');
    let controller, revision = 0;

    function filterUrl() {
        const target = new URL(form.action);
        for (const [name, value] of new FormData(form)) {
            if (value) target.searchParams.set(name, value);
        }
        return target;
    }

    async function refresh(target = filterUrl()) {
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        document.getElementById('ct-results').setAttribute('aria-busy', 'true');
        feedback.textContent = 'Updating journal tasks…';
        try {
            const response = await fetch(target, { signal: controller.signal, credentials: 'same-origin',
                cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('ct-results');
            if (current !== revision) return;
            if (!next) throw new Error('Missing results');
            document.getElementById('ct-results').replaceWith(next);
            history.replaceState(null, '', target);
            feedback.textContent = 'Journal tasks updated.';
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) {
                feedback.textContent = 'Could not update journal tasks. Select a filter again to retry.';
            }
        } finally {
            if (current === revision) document.getElementById('ct-results').removeAttribute('aria-busy');
        }
    }

    form.addEventListener('change', () => refresh());
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('ct-clear').addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        form.querySelectorAll('select').forEach(select => { select.value = ''; });
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#ct-results .coord-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
})();
