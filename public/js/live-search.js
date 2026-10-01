// Suggestions use the existing GET results, including their authorization and filters.
export function searchUrl(action, entries, name, value, base) {
    const url = new URL(action || base, base);
    if (url.origin !== new URL(base).origin) throw new Error('Search must stay on this site.');
    const fields = Array.from(entries);
    if (action) url.search = '';
    for (const key of new Set(fields.map(([key]) => key))) url.searchParams.delete(key);
    for (const key of ['page', 'export', '_token']) url.searchParams.delete(key);
    for (const [key, entry] of fields) {
        if (typeof entry === 'string' && !['page', 'export', '_token'].includes(key)) url.searchParams.append(key, entry);
    }
    url.searchParams.set(name, value);
    return url;
}

export function suggestions(root) {
    const seen = new Set();
    return Array.from(root.querySelectorAll('[data-search-label]')).map(node => ({
        label: node.dataset.searchLabel,
        detail: node.dataset.searchDetail || '',
        url: node.dataset.searchUrl || '',
        node,
    })).filter(item => {
        const key = item.label + '|' + item.detail;
        if (!item.label || seen.has(key)) return false;
        seen.add(key);
        return true;
    }).slice(0, 8);
}

function attach(input, index) {
    if (input.getAttribute('data-auto-filter') !== null) return;
    const form = input.form;
    const local = input.id === 'srSearchInput' || input.id === 'savedSearchInput';
    if (!local && (!form || form.method.toLowerCase() !== 'get')) return;
    const menu = document.createElement('div');
    menu.className = 'ube-search-suggestions';
    menu.id = `ube-search-${index}`;
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-label', 'Search suggestions');
    menu.hidden = true;
    document.body.append(menu);
    const status = document.createElement('span');
    status.className = 'ube-search-status';
    status.setAttribute('role', 'status');
    input.insertAdjacentElement('afterend', status);
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', menu.id);
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('autocomplete', 'off');
    let timer, controller, revision = 0, selected = -1, items = [];
    function close() {
        revision++;
        clearTimeout(timer);
        controller?.abort();
        menu.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        selected = -1;
    }
    function position() {
        const box = input.getBoundingClientRect();
        menu.style.left = `${Math.max(8, Math.min(box.left, innerWidth - Math.min(box.width, innerWidth - 16) - 8))}px`;
        menu.style.width = `${Math.min(box.width, innerWidth - 16)}px`;
        const below = innerHeight - box.bottom - 10;
        const above = box.top - 10;
        const upward = below < 180 && above > below;
        menu.style.maxHeight = `${Math.max(60, Math.min(330, upward ? above : below))}px`;
        menu.style.top = upward ? 'auto' : `${box.bottom + 5}px`;
        menu.style.bottom = upward ? `${innerHeight - box.top + 5}px` : 'auto';
    }
    function choose(item) {
        close();
        if (item.url) {
            const target = new URL(item.url, location.href);
            if (target.origin === location.origin && ['http:', 'https:'].includes(target.protocol)) location.assign(target.href);
            return;
        }
        input.value = item.label;
        if (local) input.dispatchEvent(new Event('input', { bubbles: true }));
        else form.requestSubmit();
        close();
    }
    function show(results, message) {
        items = results;
        selected = -1;
        menu.replaceChildren();
        input.removeAttribute('aria-activedescendant');
        for (const [i, item] of results.entries()) {
            const option = document.createElement('div');
            option.id = `${menu.id}-${i}`;
            option.className = 'ube-search-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            const title = document.createElement('strong');
            title.textContent = item.label;
            const detail = document.createElement('small');
            detail.textContent = item.detail;
            option.append(title, detail);
            option.addEventListener('mousedown', event => event.preventDefault());
            option.addEventListener('click', () => choose(item));
            menu.append(option);
        }
        if (!results.length) {
            const empty = document.createElement('div');
            empty.className = 'ube-search-empty';
            empty.textContent = message || 'No matching results.';
            menu.append(empty);
        }
        status.textContent = message || `${results.length} suggestions available.`;
        position();
        menu.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }
    async function run(version) {
        const query = input.value.trim();
        if (!query || document.activeElement !== input) return;
        if (local) {
            const selector = input.id === 'srSearchInput' ? '[data-sr-card]' : '[data-saved-card]';
            const candidates = Array.from(document.querySelectorAll(selector)).filter(card => {
                const text = ['title', 'author', 'keywords', 'dept', 'type'].map(key => card.dataset[key] || '').join(' ');
                if (!text.toLowerCase().includes(query.toLowerCase())) return false;
                if (input.id === 'savedSearchInput') {
                    const dept = document.getElementById('savedDeptFilter')?.value?.toLowerCase() || '';
                    const year = document.getElementById('savedYearFilter')?.value || '';
                    if (dept && !(card.dataset.dept || '').includes(dept)) return false;
                    if (year && card.dataset.year !== year) return false;
                }
                return true;
            });
            show(candidates.slice(0, 8).map(card => ({label: card.querySelector('h3')?.textContent.trim() || card.dataset.title, detail: card.dataset.author, url: card.querySelector('h3 a')?.href || ''})));
            return;
        }
        controller = new AbortController();
        try {
            const url = searchUrl(form.getAttribute('action'), new FormData(form), input.name, query, location.href);
            const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Search unavailable');
            const html = await response.text();
            if (version !== revision || document.activeElement !== input) return;
            show(suggestions(new DOMParser().parseFromString(html, 'text/html')));
        } catch (error) {
            if (error.name !== 'AbortError' && version === revision && document.activeElement === input) show([], 'Suggestions unavailable. Use the existing Search or Filter button.');
        }
    }
    function schedule(event) {
        close();
        if (event?.isComposing || !input.value.trim()) { status.textContent = ''; return; }
        const version = revision;
        timer = setTimeout(() => run(version), 250);
    }
    input.addEventListener('input', schedule);
    input.addEventListener('compositionend', schedule);
    input.addEventListener('focus', schedule);
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); return; }
        if (menu.hidden || !items.length) return;
        if (event.key === 'Enter' && selected >= 0) { event.preventDefault(); choose(items[selected]); }
        if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            selected = selected < 0
                ? (event.key === 'ArrowDown' ? 0 : items.length - 1)
                : (selected + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            Array.from(menu.children).forEach((option, i) => option.setAttribute('aria-selected', String(i === selected)));
            input.setAttribute('aria-activedescendant', menu.children[selected].id);
            menu.children[selected].scrollIntoView({ block: 'nearest' });
        }
    });
    input.addEventListener('blur', () => { setTimeout(() => { if (document.activeElement !== input) close(); }, 150); });
    document.addEventListener('pointerdown', event => { if (event.target !== input && !menu.contains(event.target)) close(); });
    form?.addEventListener('submit', close);
    form?.addEventListener('change', close);
    for (const id of ['savedDeptFilter', 'savedYearFilter']) document.getElementById(id)?.addEventListener('change', close);
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('input[name="search"], #srSearchInput, #savedSearchInput').forEach(attach);
}
