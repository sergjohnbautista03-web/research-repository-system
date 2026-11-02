(() => {
    const form = document.getElementById('jbl-filters');
    if (!form) return;
    const feedback = document.getElementById('jbl-feedback');
    const department = document.getElementById('batch-department');
    const program = document.getElementById('batch-program');
    const search = document.getElementById('batch-search');
    const programsByDepartment = JSON.parse(document.getElementById('jbl-programs').textContent);
    let controller, timer, revision = 0;

    function updatePrograms() {
        const selected = program.value;
        const programs = department?.value
            ? (programsByDepartment[department.value] ?? [])
            : [...new Set(Object.values(programsByDepartment).flat())];
        program.replaceChildren(new Option('All Programs', ''), ...programs.map(value => new Option(value, value)));
        program.value = programs.includes(selected) ? selected : '';
    }
    updatePrograms();

    function filterUrl() {
        const target = new URL(form.action);
        for (const [name, value] of new FormData(form)) {
            if (value) target.searchParams.set(name, value);
        }
        return target;
    }

    async function refresh(target = filterUrl()) {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        document.getElementById('jbl-results').setAttribute('aria-busy', 'true');
        feedback.textContent = 'Updating journal batches…';
        try {
            const response = await fetch(target, {
                signal: controller.signal, credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'text/html' },
            });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('jbl-results');
            if (current !== revision) return;
            if (!next) throw new Error('Missing results');
            document.getElementById('jbl-results').replaceWith(next);
            history.replaceState(null, '', target);
            feedback.textContent = 'Journal batches updated.';
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) {
                feedback.textContent = 'Could not update journal batches. Try searching or selecting a filter again.';
            }
        } finally {
            if (current === revision) document.getElementById('jbl-results').removeAttribute('aria-busy');
        }
    }

    form.addEventListener('change', event => {
        if (search && event.target === search) return;
        if (department && event.target === department) {
            program.value = '';
            updatePrograms();
        }
        refresh();
    });
    search?.addEventListener('input', event => {
        if (event.isComposing) return;
        clearTimeout(timer);
        controller?.abort();
        revision++;
        timer = setTimeout(() => refresh(), 300);
    });
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('jbl-clear').addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        form.querySelectorAll('select').forEach(select => { select.value = ''; });
        if (search) search.value = '';
        updatePrograms();
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('#jbl-results .jbl-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
})();
