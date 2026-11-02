import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('journal preparation search and dropdowns update results, cancel stale requests and Clear resets every field', async () => {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; }
        addEventListener(type, callback) { this.events[type] = callback; }
        emit(type, extra = {}) { this.events[type]?.({ preventDefault() {}, ...extra }); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const fields = [
        ['school_year', '2026-2027'], ['program', 'Information Technology'],
        ['submission_category', 'student_research_journal'], ['status', 'pending'],
        ['search', 'Initial search'],
    ].map(([name, value]) => ({ name, value }));
    const form = Object.assign(new Element(), {
        action: 'http://localhost:8000/admin/research-handoffs', querySelectorAll: () => fields,
    });
    const clear = new Element(), feedback = new Element(), document = new Element();
    const nodes = { 'dh-filters': form, 'dh-clear': clear, 'dh-feedback': feedback };
    const results = label => Object.assign(new Element(), {
        label, replaceWith(next) { nodes['dh-results'] = next; },
    });
    nodes['dh-results'] = results('initial');
    document.getElementById = id => nodes[id];
    const requests = [], urls = [], timers = new Map();
    let timerId = 0;
    const flushTimers = () => { for (const [id, callback] of timers) { timers.delete(id); callback(); } };
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, document,
        setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
        clearTimeout: id => timers.delete(id),
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() { for (const field of fields) yield [field.name, field.value]; } },
        DOMParser: class { parseFromString(label) { return { getElementById: () => results(label) }; } },
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/dean-handoffs.js', import.meta.url), 'utf8'), context);
    form.emit('change');
    assert.equal(requests[0].url.searchParams.get('program'), 'Information Technology');
    assert.deepEqual([...requests[0].url.searchParams.keys()], fields.map(field => field.name));
    clear.emit('click');
    assert.equal(requests[0].options.signal.aborted, true);
    assert.ok(fields.every(field => field.value === ''));
    assert.ok([...requests[1].url.searchParams.values()].every(value => value === ''));
    requests[1].resolve({ ok: true, text: async () => 'all tasks' });
    await new Promise(resolve => setTimeout(resolve, 0));
    requests[0].resolve({ ok: true, text: async () => 'stale tasks' });
    await new Promise(resolve => setTimeout(resolve, 0));
    assert.equal(nodes['dh-results'].label, 'all tasks');
    assert.equal(nodes['dh-results'].attrs['aria-busy'], undefined);
    assert.equal(urls.length, 1);
    assert.equal(urls[0].searchParams.has('page'), false);

    const search = fields.find(field => field.name === 'search');
    fields[0].value = '2026-2027';
    search.value = 'Tech';
    form.emit('input', { target: search });
    search.value = 'Technology';
    form.emit('input', { target: search });
    assert.equal(timers.size, 1);
    assert.equal(requests.length, 2);
    flushTimers();
    assert.equal(requests[2].url.searchParams.get('search'), 'Technology');
    assert.equal(requests[2].url.searchParams.get('school_year'), '2026-2027');
    search.value = 'Climate';
    form.emit('input', { target: search });
    assert.equal(requests[2].options.signal.aborted, true);
    requests[2].resolve({ ok: true, text: async () => 'old search' });
    await new Promise(resolve => setTimeout(resolve, 0));
    assert.equal(nodes['dh-results'].label, 'all tasks');
    form.emit('submit');
    assert.equal(timers.size, 0);
    assert.equal(requests[3].url.searchParams.get('search'), 'Climate');
    form.emit('change', { target: search });
    assert.equal(requests.length, 4);
    search.value = 'Pending search';
    form.emit('input', { target: search });
    clear.emit('click');
    assert.equal(timers.size, 0);
    assert.ok(fields.every(field => field.value === ''));
    flushTimers();
    assert.equal(requests.length, 5);
});
