(() => {
    const modal = document.getElementById('journalBatchModal');
    if (!modal) return;
    const frame = document.getElementById('journalBatchModalFrame');
    const title = document.getElementById('journalBatchModalTitle');
    const status = document.getElementById('journalBatchModalStatus');
    const closeButton = modal.querySelector('[data-close-journal-batch]');
    const backButton = modal.querySelector('[data-back-journal-list]');
    let trigger, previousOverflow, changed = false, listUrl = null, listPath = null;

    document.addEventListener('click', event => {
        const link = event.target.closest('[data-open-journal-batch]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        trigger = link;
        changed = false;
        const assigning = link.dataset.batchMode === 'assignment';
        const listing = link.dataset.batchMode === 'list';
        modal.classList.toggle('jbm-assignment', assigning);
        if (backButton) backButton.hidden = true;
        title.textContent = link.dataset.batchTitle || 'Journal Batch';
        frame.hidden = true;
        status.hidden = false;
        status.textContent = assigning ? 'Loading assignment form…' : (listing ? 'Loading list…' : 'Loading batch…');
        modal.setAttribute('aria-busy', 'true');
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        url.searchParams.set('embedded', '1');
        listUrl = listing ? url.href : null;
        listPath = listing ? url.pathname : null;
        modal.showModal();
        closeButton.focus();
        frame.src = url.href;
    });

    frame.addEventListener('load', () => {
        if (!modal.open) return;
        modal.removeAttribute('aria-busy');
        try {
            const body = frame.contentDocument?.body;
            if (!body?.hasAttribute('data-journal-batch-embedded')) throw new Error('Batch unavailable');
            title.textContent = body.dataset.batchTitle || title.textContent;
            if (listUrl) {
                const current = new URL(frame.contentWindow.location.href);
                if (current.origin !== window.location.origin) throw new Error('List unavailable');
                const atList = current.pathname === listPath;
                if (backButton) backButton.hidden = atList;
                if (atList) {
                    listUrl = current.href;
                    // Keep the list's latest filters when opening a batch after an AJAX update.
                    frame.contentDocument.addEventListener('click', () => {
                        const latest = new URL(frame.contentWindow.location.href);
                        if (latest.origin === window.location.origin && latest.pathname === listPath) listUrl = latest.href;
                    });
                }
            }
            status.hidden = true;
            frame.hidden = false;
        } catch {
            status.hidden = false;
            frame.hidden = true;
            status.textContent = modal.classList.contains('jbm-assignment')
                ? 'Unable to open the assignment form. Close the dialog and try again.'
                : (listUrl ? 'Unable to open this list. Close the dialog and try again.'
                    : 'Unable to open this batch. Close the dialog and try again.');
        }
    });

    backButton?.addEventListener('click', () => {
        if (!listUrl) return;
        frame.hidden = true;
        status.hidden = false;
        status.textContent = 'Loading list…';
        modal.setAttribute('aria-busy', 'true');
        frame.src = listUrl;
    });
    closeButton.addEventListener('click', () => modal.close());
    modal.addEventListener('cancel', event => { event.preventDefault(); modal.close(); });
    modal.addEventListener('click', event => {
        if (event.target !== modal) return;
        const bounds = modal.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) modal.close();
    });
    modal.addEventListener('close', () => {
        frame.src = 'about:blank';
        frame.hidden = true;
        listUrl = listPath = null;
        if (backButton) backButton.hidden = true;
        document.body.style.overflow = previousOverflow ?? '';
        modal.removeAttribute('aria-busy');
        if (trigger?.isConnected) trigger.focus();
        if (changed) window.location.reload();
    });

    window.addEventListener('message', event => {
        if (!modal.open || event.origin !== window.location.origin || event.source !== frame.contentWindow) return;
        if (event.data?.type === 'journal-batch-updated') changed = true;
        if (event.data?.type === 'journal-batch-close') modal.close();
    });
})();
