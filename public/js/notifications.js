(() => {
    const panel = document.querySelector('[data-notification-panel]');
    const toggle = document.getElementById('notificationToggle');
    if (!panel || !toggle) return;
    const badge = toggle.querySelector('.topbar-notification-count');
    const views = Array.from(document.querySelectorAll('[data-notification-view]'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let items = [], unreadCount = 0, totalCount = 0, pages = 1;
    let busy = false, loading = false, revision = 0, loaded = false;
    let requestController;

    function updateControls() {
        badge.hidden = unreadCount === 0;
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        toggle.setAttribute('aria-label', unreadCount ? `Notifications (${unreadCount} unread)` : 'Notifications');
        views.forEach(view => {
            view.querySelector('[data-unread-label]').textContent = loaded
                ? `${unreadCount} unread notification${unreadCount === 1 ? '' : 's'}` : 'Loading notifications…';
            view.querySelector('[data-mark-all]').disabled = busy || !loaded || unreadCount === 0;
            const more = view.querySelector('[data-notification-more]');
            more.hidden = !loaded || totalCount <= pages * 50;
            more.disabled = busy || loading;
            view.querySelectorAll('[data-mark-read]').forEach(button => { button.disabled = busy; });
        });
    }

    function showError(message) {
        views.forEach(view => {
            const error = view.querySelector('[data-notification-error]');
            error.textContent = message;
            error.hidden = !message;
        });
    }

    function render() {
        views.forEach(view => {
            const content = view.querySelector('[data-notification-items]');
            content.replaceChildren();
            if (!items.length && loaded) {
                const empty = document.createElement('p');
                empty.className = 'notification-empty';
                empty.textContent = 'No notifications yet.';
                content.append(empty);
            }
            items.forEach(item => {
                const article = document.createElement('article');
                article.className = `notification-item ${item.is_read ? 'is-read' : 'is-unread'}`;
                const link = document.createElement('a');
                link.className = 'notification-item-link';
                link.href = item.url;
                const message = document.createElement('strong');
                message.textContent = item.message;
                const date = document.createElement('small');
                date.textContent = new Date(item.date).toLocaleString('en-PH', { timeZone: 'Asia/Manila' });
                link.append(message, date);
                const actions = document.createElement('div');
                actions.className = 'notification-item-actions';
                const status = document.createElement('span');
                status.className = item.is_read ? 'notification-read-label' : 'notification-unread-label';
                status.textContent = item.is_read ? 'Read' : 'Unread';
                actions.append(status);
                if (!item.is_read) {
                    const readButton = document.createElement('button');
                    readButton.type = 'button';
                    readButton.className = 'notification-mark-read';
                    readButton.dataset.markRead = item.id;
                    readButton.textContent = 'Mark as Read';
                    readButton.setAttribute('aria-label', `Mark as read: ${item.message}`);
                    readButton.addEventListener('click', () => markRead(item.id));
                    actions.append(readButton);
                }
                article.append(link, actions);
                content.append(article);
            });
        });
        updateControls();
    }

    async function fetchPage(page, signal) {
        const url = new URL(panel.dataset.feedUrl, window.location.href);
        url.searchParams.set('page', page);
        const response = await fetch(url, {
            headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal,
        });
        if (!response.ok || response.redirected) throw new Error('Unable to load notifications. Please try again.');
        return response.json();
    }

    async function refresh(nextPages = pages) {
        if (busy) return;
        requestController?.abort();
        requestController = new AbortController();
        const currentRevision = ++revision;
        loading = true;
        updateControls();
        try {
            const data = await Promise.all(Array.from({ length: nextPages }, (_, index) => fetchPage(index + 1, requestController.signal)));
            if (currentRevision !== revision) return;
            const snapshot = data[0];
            items = [...new Map(data.flatMap(page => page.notifications).map(item => [item.id, item])).values()];
            unreadCount = snapshot.unread_count;
            totalCount = snapshot.total_count;
            pages = Math.min(nextPages, Math.max(1, Math.ceil(totalCount / 50)));
            loaded = true;
            showError('');
            render();
        } catch (error) {
            if (currentRevision !== revision || error.name === 'AbortError') return;
            showError(error.message || 'Unable to load notifications. Please try again.');
        } finally {
            if (currentRevision === revision) { loading = false; updateControls(); }
        }
    }

    async function markRead(id = null) {
        if (busy) return;
        busy = true;
        loading = false;
        ++revision;
        requestController?.abort();
        updateControls();
        showError('');
        try {
            const response = await fetch(id ? panel.dataset.readUrl : panel.dataset.readAllUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(id ? { id } : {}),
            });
            if (!response.ok || response.redirected) throw new Error('Unable to mark notifications as read. Please try again.');
            const data = await response.json();
            const updated = new Map(data.notifications.map(item => [item.id, item]));
            items = items.map(item => updated.get(item.id) ?? (!id || item.id === id
                ? { ...item, is_read: true } : item));
            unreadCount = data.unread_count;
            totalCount = data.total_count;
            loaded = true;
            render();
        } catch (error) {
            showError(error.message || 'Unable to mark notifications as read. Please try again.');
        } finally {
            busy = false;
            updateControls();
        }
    }

    views.forEach(view => {
        view.querySelector('[data-mark-all]').addEventListener('click', () => markRead());
        view.querySelector('[data-notification-more]').addEventListener('click', () => refresh(pages + 1));
    });
    toggle.addEventListener('click', () => {
        panel.showModal();
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('notifications-open');
        refresh();
    });
    document.getElementById('notificationClose').addEventListener('click', () => panel.close());
    panel.addEventListener('click', event => {
        const bounds = panel.getBoundingClientRect();
        if (event.target === panel && (event.clientX < bounds.left || event.clientY < bounds.top || event.clientX > bounds.right || event.clientY > bounds.bottom)) panel.close();
    });
    panel.addEventListener('close', () => {
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('notifications-open');
        toggle.focus();
    });
    refresh();
    setInterval(() => { if (!document.hidden && !loading) refresh(); }, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
