(() => {
    const modal = document.getElementById('activateExistingUsersModal');
    if (!modal) return;
    const trigger = document.querySelector('[data-open-semester-users]');
    const body = document.getElementById('semester-modal-body');
    const message = document.getElementById('semester-modal-message');
    let url = modal.dataset.url;
    let controller, revision = 0, submitting = false;

    function showMessage(text, error = false) {
        message.textContent = text;
        message.className = `su-alert ${error ? 'su-error' : 'su-success'}`;
        message.setAttribute('role', error ? 'alert' : 'status');
        message.hidden = !text;
    }

    async function load(nextUrl) {
        controller?.abort();
        controller = new AbortController();
        const version = ++revision;
        body.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(nextUrl, {
                credentials: 'same-origin', signal: controller.signal, cache: 'no-store',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok || response.redirected) throw new Error('Unable to load users. Refresh Manage Users and try again.');
            const html = await response.text();
            if (version !== revision || !modal.open) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const content = page.querySelector('.su-page');
            if (!content) throw new Error('Unable to load users. Refresh Manage Users and try again.');
            body.replaceChildren(content);
            url = nextUrl;
            window.SemesterUserSelection.initialize(body, activate);
        } catch (error) {
            if (error.name !== 'AbortError' && version === revision && modal.open) showMessage(error.message, true);
        } finally {
            if (version === revision) body.removeAttribute('aria-busy');
        }
    }

    async function activate(form) {
        if (submitting) return;
        const payload = new FormData(form);
        const controls = [...body.querySelectorAll('input,select,button')];
        const disabledStates = controls.map(control => control.disabled);
        submitting = true;
        controls.forEach(control => { control.disabled = true; });
        showMessage('');
        body.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin', body: payload,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors ?? {}).flat().join(' ');
                throw new Error(errors || result.message || 'Unable to activate users. Please try again.');
            }
            showMessage(result.message);
            document.dispatchEvent(new CustomEvent('semester-users-activated'));
            if (modal.open) await load(url);
        } catch (error) {
            showMessage(error.message || 'Unable to activate users. Please try again.', true);
        } finally {
            submitting = false;
            controls.forEach((control, index) => { control.disabled = disabledStates[index]; });
            body.removeAttribute('aria-busy');
        }
    }

    trigger?.addEventListener('click', () => {
        if (modal.open) return;
        showMessage('');
        modal.showModal();
        if (!submitting) load(modal.dataset.url);
    });
    modal.querySelector('[data-close-semester-users]').addEventListener('click', () => modal.close());
    modal.addEventListener('close', () => {
        revision++;
        controller?.abort();
        body.removeAttribute('aria-busy');
        trigger?.focus();
    });
    modal.addEventListener('click', event => {
        if (event.target !== modal) return;
        const rect = modal.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) modal.close();
    });
    body.addEventListener('submit', event => {
        if (!event.target.matches('.su-filters')) return;
        event.preventDefault();
        if (submitting) return;
        showMessage('');
        const nextUrl = new URL(event.target.action);
        nextUrl.search = new URLSearchParams(new FormData(event.target)).toString();
        load(nextUrl.href);
    });
    body.addEventListener('click', event => {
        const link = event.target.closest('.su-pagination a, .su-filters a');
        if (!link) return;
        event.preventDefault();
        if (!submitting) {
            showMessage('');
            load(link.href);
        }
    });
})();
