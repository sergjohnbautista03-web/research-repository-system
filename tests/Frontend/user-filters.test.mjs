import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('live filters debounce, reject stale responses, update modal data and clear every filter', async () => {
    class Element {
        constructor() { this.events = {}; this.value = ''; this.attrs = {}; }
        addEventListener(name, fn) { this.events[name] = fn; }
        emit(name, extra = {}) { this.events[name]({ preventDefault() {}, ...extra }); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const search = new Element(), clear = new Element(), status = new Element();
    const selects = ['role', 'school_year', 'semester', 'year_level'].map(name => Object.assign(new Element(), { name, value: '2' }));
    selects[0].value = 'student';
    const yearFilter = {};
    selects[3].closest = () => yearFilter;
    const form = Object.assign(new Element(), { action: 'https://ube.test/admin/users',
        elements: { search, ...Object.fromEntries(selects.map(select => [select.name, select])) } });
    form.querySelector = selector => selector === '[data-clear-filters]' ? clear : null;
    form.querySelectorAll = () => selects;
    const nodes = { 'mu-user-filters': form, 'mu-filter-status': status, 'mu-user-data': new Element() };
    const result = label => Object.assign(new Element(), {
        label,
        replaceWith(next) { nodes['mu-user-results'] = next; },
        querySelector() { return { textContent: label }; },
    });
    nodes['mu-user-results'] = result('initial');
    const requests = [], urls = [];
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, setTimeout, clearTimeout,
        window: { location: { href: 'https://ube.test/admin/users?role=student&page=2' } },
        document: Object.assign(new Element(), { getElementById: id => nodes[id] }),
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() { yield ['search', search.value]; for (const select of selects) yield [select.name, select.value]; } },
        DOMParser: class { parseFromString(label) { return {
            getElementById: id => id === 'mu-user-results' ? result(label) : { textContent: JSON.stringify({ label }) },
            querySelector: () => null,
        }; } },
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
        userModalData: {},
    });
    vm.runInContext(readFileSync(new URL('../../public/js/user-filters.js', import.meta.url), 'utf8'), context);
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    search.value = 'A'; search.emit('input');
    search.value = 'Alex'; search.emit('input');
    await pause(300);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url.searchParams.get('search'), 'Alex');
    assert.equal(requests[0].url.searchParams.get('year_level'), '2');
    selects[3].value = '3';
    form.emit('change', { target: { matches: () => true } });
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.signal.aborted, true);
    requests[1].resolve({ ok: true, text: async () => 'new' });
    await pause(0);
    requests[0].resolve({ ok: true, text: async () => 'stale' });
    await pause(0);
    assert.equal(nodes['mu-user-results'].label, 'new');
    assert.equal(context.userModalData.label, 'new');
    assert.equal(urls.length, 1);
    clear.emit('click');
    assert.equal(search.value, '');
    assert.ok(selects.every(select => select.value === ''));
    assert.equal(requests[2].url.searchParams.get('semester'), '');
    assert.equal(requests[2].url.searchParams.has('page'), false);
    requests[2].resolve({ ok: false });
    await pause(0);
    assert.match(status.textContent, /Could not update/);
    assert.equal(nodes['mu-user-results'].attrs['aria-busy'], undefined);
    context.document.emit('semester-users-activated');
    assert.equal(requests[3].url.searchParams.get('page'), '2');
    requests[3].resolve({ ok: true, text: async () => 'activated users' });
    await pause(0);
    assert.equal(nodes['mu-user-results'].label, 'activated users');
    assert.equal(context.userModalData.label, 'activated users');
});
