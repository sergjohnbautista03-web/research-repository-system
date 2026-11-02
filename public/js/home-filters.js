(() => {
    const form = document.getElementById('home-filters');
    if (!form || !window.fetch || !window.AbortController) return;

    const results = document.getElementById('home-results');
    const content = document.getElementById('home-results-content');
    const status = document.getElementById('home-results-status');
    const clear = document.getElementById('home-clear-filters');
    const submit = form.querySelector('button[type="submit"]');
    const submitLabel = submit.textContent;
    let controller, revision = 0;

    function filterUrl() {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        return url;
    }

    function syncFilters(url) {
        for (const field of form.querySelectorAll('input[name], select[name]')) {
            field.value = url.searchParams.get(field.name) ?? (field.name === 'field' ? 'all' : '');
        }
        // Close any search suggestions after restoring or clearing the form.
        form.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function refresh(url, { navigation = false, restore = false } = {}) {
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Updating research results...';
        submit.disabled = true;
        submit.textContent = 'Applying...';
        // Retain the current list's height while the new results are loading.
        content.style.minHeight = `${content.getBoundingClientRect().height}px`;

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'text/html' },
            });
            if (!response.ok || response.redirected) throw new Error('Results unavailable');
            const html = await response.text();
            if (current !== revision) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const nextContent = page.getElementById('home-results-content');
            const nextClear = page.getElementById('home-clear-filters');
            if (!nextContent || !nextClear) throw new Error('Invalid results');

            if (!restore && url.href !== window.location.href) {
                history.pushState(null, '', url);
            }
            content.innerHTML = nextContent.innerHTML;
            clear.hidden = nextClear.hidden;
            if (navigation || restore) syncFilters(url);
            status.textContent = nextContent.querySelector('.home-section-head > p')?.textContent.trim()
                || 'Research results updated.';
            content.style.minHeight = '';
            if (navigation || restore) {
                results.focus({ preventScroll: true });
                results.scrollIntoView({ block: 'start', behavior: 'instant' });
            }
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) {
                if (restore) {
                    // Let the server restore the URL if Back/Forward cannot load via AJAX.
                    window.location.reload();
                    return;
                }
                status.textContent = 'Could not update results. Please press Apply Filters to try again.';
            }
        } finally {
            if (current === revision) {
                results.removeAttribute('aria-busy');
                content.style.minHeight = '';
                submit.disabled = false;
                submit.textContent = submitLabel;
            }
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        refresh(filterUrl());
    });

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('#home-clear-filters a, #home-results [data-home-clear], #home-results .pagination-wrap a');
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
        const url = new URL(link.href);
        if (url.origin !== window.location.origin || url.pathname !== new URL(form.action).pathname) return;
        event.preventDefault();
        refresh(url, { navigation: true });
    });

    window.addEventListener('popstate', () => refresh(new URL(window.location.href), { restore: true }));
})();
