(() => {
    const form = document.getElementById('mu-user-filters');
    if (!form) return;
    const search = form.elements.search;
    const status = document.getElementById('mu-filter-status');
    let timer, controller, revision = 0;

    function syncYearLevel() {
        const yearLevel = form.elements.year_level;
        const isStudent = form.elements.role.value === 'student';
        yearLevel.closest('[data-year-level-filter]').hidden = !isStudent;
        yearLevel.disabled = !isStudent;
        if (!isStudent) yearLevel.value = '';
    }

    syncYearLevel();

    function filterUrl() {
        syncYearLevel();
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
        const results = document.getElementById('mu-user-results');
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Updating users…';
        try {
            const response = await fetch(url, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { Accept: 'text/html' },
                cache: 'no-store',
            });
            if (!response.ok || response.redirected) throw new Error('Unable to load users');
            const html = await response.text();
            if (version !== revision) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const nextResults = page.getElementById('mu-user-results');
            const nextData = page.getElementById('mu-user-data');
            if (!nextResults || !nextData) throw new Error('Invalid results');
            const data = JSON.parse(nextData.textContent);
            results.replaceWith(nextResults);
            userModalData = data;
            document.getElementById('mu-user-data').textContent = nextData.textContent;
            const addUser = form.querySelector('a:not([data-clear-filters])');
            const nextAddUser = page.querySelector('#mu-user-filters a:not([data-clear-filters])');
            if (addUser && nextAddUser) addUser.href = nextAddUser.href;
            history.replaceState(null, '', url);
            status.textContent = `${nextResults.querySelector('.mu-count-badge').textContent.trim()} found.`;
        } catch (error) {
            if (error.name !== 'AbortError' && version === revision) {
                status.textContent = 'Could not update users. Change a filter or press Enter to retry.';
            }
        } finally {
            if (version === revision) document.getElementById('mu-user-results').removeAttribute('aria-busy');
        }
    }

    function schedule(event) {
        const version = cancel();
        if (event.isComposing) return;
        timer = setTimeout(() => refresh(filterUrl(), version), 250);
    }
    search.addEventListener('input', schedule);
    search.addEventListener('compositionend', schedule);
    form.addEventListener('change', event => {
        if (event.target.matches('select')) refresh();
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        refresh();
    });
    form.querySelector('[data-clear-filters]').addEventListener('click', event => {
        event.preventDefault();
        search.value = '';
        form.querySelectorAll('select').forEach(select => { select.value = ''; });
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#mu-user-results .mu-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
    document.addEventListener('semester-users-activated', () => refresh(new URL(window.location.href)));
})();
