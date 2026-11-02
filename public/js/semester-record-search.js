(() => {
    const form = document.getElementById('sem-record-filters');
    if (!form) return;
    const search = form.elements.search;
    const status = document.getElementById('sem-search-status');
    let timer, controller, revision = 0;

    function filterUrl() {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        return url;
    }

    function cancel() {
        clearTimeout(timer);
        controller?.abort();
        return ++revision;
    }

    async function refresh(url = filterUrl(), version = cancel()) {
        controller = new AbortController();
        document.getElementById('sem-record-results').setAttribute('aria-busy', 'true');
        status.textContent = 'Searching semester records...';
        try {
            const response = await fetch(url, {signal: controller.signal, credentials: 'same-origin',
                cache: 'no-store', headers: {Accept: 'text/html'}});
            if (!response.ok || response.redirected) throw new Error('Search unavailable');
            const html = await response.text();
            if (version !== revision) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const results = page.getElementById('sem-record-results');
            const data = page.getElementById('semester-record-data');
            const filters = page.getElementById('sem-active-filters');
            if (!results || !data || !filters) throw new Error('Missing search results');
            JSON.parse(data.textContent);
            document.getElementById('sem-record-results').replaceWith(results);
            document.getElementById('semester-record-data').textContent = data.textContent;
            document.getElementById('sem-active-filters').replaceWith(filters);
            document.dispatchEvent(new Event('semester-records-updated'));
            history.replaceState(null, '', url);
            status.textContent = `${results.querySelector('.sem-count-pill').textContent.trim()} found.`;
        } catch (error) {
            if (error.name !== 'AbortError' && version === revision) {
                status.textContent = 'Could not load semester records. Press Enter or Filter to retry.';
            }
        } finally {
            if (version === revision) document.getElementById('sem-record-results').removeAttribute('aria-busy');
        }
    }

    function schedule(event) {
        const version = cancel();
        if (!event.isComposing) timer = setTimeout(() => refresh(filterUrl(), version), 250);
    }
    search.addEventListener('input', schedule);
    search.addEventListener('compositionend', schedule);
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    form.addEventListener('change', event => { if (event.target.matches('select')) refresh(); });
    document.getElementById('sem-record-clear').addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        search.value = '';
        form.querySelectorAll('select').forEach(field => { field.value = ''; });
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#sem-record-results .sem-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
})();
