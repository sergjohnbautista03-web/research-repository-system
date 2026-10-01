(() => {
    const form = document.getElementById('ual-filters');
    if (!form) return;
    const feedback = document.getElementById('ual-feedback');
    let timer, controller, revision = 0;
    function syncYearLevel() {
        const year = form.elements.year_level;
        if (!year) return;
        const isStudent = form.elements.role.value === 'student';
        year.closest('[data-year-level-filter]').hidden = !isStudent;
        year.disabled = !isStudent;
        if (!isStudent) year.value = '';
    }
    syncYearLevel();
    const url = () => { syncYearLevel(); const target = new URL(form.action); target.search = new URLSearchParams(new FormData(form)); return target; };
    async function refresh(target = url()) {
        clearTimeout(timer); controller?.abort(); controller = new AbortController(); const current = ++revision;
        document.getElementById('ual-results').setAttribute('aria-busy', 'true'); feedback.textContent = 'Updating activity logs…';
        try {
            const response = await fetch(target, {signal:controller.signal, credentials:'same-origin', cache:'no-store', headers:{Accept:'text/html'}});
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('ual-results');
            if (!next || current !== revision) return;
            document.getElementById('ual-results').replaceWith(next); history.replaceState(null, '', target); feedback.textContent = 'Activity logs updated.';
        } catch (error) { if (error.name !== 'AbortError' && current === revision) feedback.textContent = 'Could not update the activity logs.'; }
        finally { if (current === revision) document.getElementById('ual-results').removeAttribute('aria-busy'); }
    }
    form.addEventListener('input', event => { if (event.target.name === 'search') { clearTimeout(timer); timer = setTimeout(() => refresh(), 250); } });
    form.addEventListener('change', event => { if (event.target.name !== 'search') refresh(); });
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('ual-clear').addEventListener('click', event => { event.preventDefault(); form.querySelectorAll('input, select').forEach(field => { field.value = ''; }); refresh(); });
    document.addEventListener('click', event => { const link = event.target.closest('#ual-results .ual-pagination a'); if (!link) return; event.preventDefault(); refresh(new URL(link.href)); });
})();
