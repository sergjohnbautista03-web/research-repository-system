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

    function cancel() {
        clearTimeout(timer);
        controller?.abort();
        return ++revision;
    }

    async function refresh(target = filterUrl(), current = cancel()) {
        controller = new AbortController();
        document.getElementById('dh-results').setAttribute('aria-busy', 'true');
        feedback.textContent = 'Updating handoff records…';
        try {
            const response = await fetch(target, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('dh-results');
            if (current !== revision) return;
            if (!next) throw new Error('Missing results');
            document.getElementById('dh-results').replaceWith(next);
            history.replaceState(null, '', target);
            feedback.textContent = 'Handoff records updated.';
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) feedback.textContent = 'Could not update handoff records.';
        } finally {
            if (current === revision) document.getElementById('dh-results').removeAttribute('aria-busy');
        }
    }

    function scheduleSearch(event) {
        if (event.target.name !== 'search') return;
        const current = cancel();
        if (event.isComposing) return;
        timer = setTimeout(() => refresh(filterUrl(), current), 350);
    }

    form.addEventListener('input', scheduleSearch);
    form.addEventListener('compositionend', scheduleSearch);
    form.addEventListener('change', event => { if (event.target?.name !== 'search') refresh(); });
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('dh-clear').addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        form.querySelectorAll('input[name="search"], select').forEach(field => { field.value = ''; });
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#dh-results .dh-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
})();
