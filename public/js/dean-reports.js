(() => {
    const form = document.getElementById('dr-filters');
    if (!form) return;
    const feedback = document.getElementById('dr-feedback');
    const print = document.getElementById('dr-print');
    let controller, revision = 0;
    async function refresh(url) {
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        url ||= new URL(form.action);
        if (!url.search) url.search = new URLSearchParams(new FormData(form));
        print.disabled = true;
        document.getElementById('dr-results').setAttribute('aria-busy', 'true');
        feedback.textContent = 'Updating report…';
        try {
            const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Report unavailable');
            const html = await response.text();
            if (current !== revision) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const next = page.getElementById('dr-results');
            const summary = page.getElementById('dr-summary');
            if (!next || !summary) throw new Error('Invalid report');
            document.getElementById('dr-summary').replaceWith(summary);
            document.getElementById('dr-results').replaceWith(next);
            history.replaceState(null, '', url);
            feedback.textContent = 'Report updated.';
            print.disabled = false;
        } catch (error) {
            if (error.name !== 'AbortError' && current === revision) feedback.textContent = 'Could not update the report. Change a filter or press Enter to retry.';
        } finally {
            if (current === revision) document.getElementById('dr-results').removeAttribute('aria-busy');
        }
    }
    form.addEventListener('change', () => refresh());
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    document.getElementById('dr-clear').addEventListener('click', event => {
        event.preventDefault();
        form.querySelectorAll('select').forEach(select => { select.value = ''; });
        refresh();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('.dr-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        refresh(new URL(link.href));
    });
    print.addEventListener('click', () => {
        const content = document.querySelector('.dr-print-report').innerHTML;
        window.ReportPrint.printHtml('<html><head><title>Research Reports</title></head><body>' + content + '</body></html>');
    });
})();
