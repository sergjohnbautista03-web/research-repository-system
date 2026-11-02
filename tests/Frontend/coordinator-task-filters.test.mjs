import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup({ embedded = false } = {}) {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; }
        addEventListener(type, callback) { this.events[type] = callback; }
        emit(type, extra = {}) { this.events[type]?.({ preventDefault() {}, ...extra }); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const fields = [
        ['program', 'Information Technology'], ['academic_year', '2026-2027'],
        ['submission_category', 'student_research_journal'], ['status', 'submitted'],
        ['search', ''],
    ].map(([name, value]) => ({ name, value }));
    const form = Object.assign(new Element(), {
        action: 'http://localhost:8000/admin/coordinator/dean-submissions', querySelectorAll: () => fields,
    });
    const clear = new Element(), feedback = new Element(), document = new Element();
    const nodes = { 'ct-filters': form, 'ct-clear': clear, 'ct-feedback': feedback };
    const results = label => Object.assign(new Element(), {
        label, replaceWith(next) { nodes['ct-results'] = next; },
    });
    nodes['ct-results'] = results('initial');
    document.getElementById = id => nodes[id];
    const requests = [], urls = [], timers = new Map();
    let timerId = 0;
    const context = vm.createContext({
        URL, AbortController, document,
        setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
        clearTimeout: id => timers.delete(id),
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() {
            for (const field of fields) yield [field.name, field.value];
            if (embedded) yield ['embedded', '1'];
        } },
        DOMParser: class { parseFromString(label) { return { getElementById: () => results(label) }; } },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/coordinator-task-filters.js', import.meta.url), 'utf8'), context);
    return { fields, form, clear, feedback, document, nodes, requests, urls, timers,
        search: fields.find(field => field.name === 'search'),
        flushTimers() { for (const [id, callback] of timers) { timers.delete(id); callback(); } },
    };
}

const tick = () => new Promise(resolve => setTimeout(resolve, 0));

test('assigned task dropdowns refresh automatically, reject stale results and Clear resets every selection', async () => {
    const { fields, form, clear, feedback, document, nodes, requests, urls } = setup();
    form.emit('change');
    assert.deepEqual([...requests[0].url.searchParams.entries()], fields.filter(field => field.value).map(field => [field.name, field.value]));
    assert.equal(nodes['ct-results'].attrs['aria-busy'], 'true');
    clear.emit('click');
    assert.equal(requests[0].options.signal.aborted, true);
    assert.ok(fields.every(field => field.value === ''));
    assert.equal(requests[1].url.search, '');
    requests[1].resolve({ ok: true, text: async () => 'all tasks' });
    await tick();
    requests[0].resolve({ ok: true, text: async () => 'stale tasks' });
    await tick();
    assert.equal(nodes['ct-results'].label, 'all tasks');
    assert.equal(urls.length, 1);
    const link = { href: form.action + '?program=Computer+Science&page=2' };
    document.emit('click', { target: { closest: () => link } });
    assert.equal(requests[2].url.searchParams.get('page'), '2');
    requests[2].reject(new Error('Offline'));
    await tick();
    assert.equal(nodes['ct-results'].label, 'all tasks');
    assert.equal(nodes['ct-results'].attrs['aria-busy'], undefined);
    assert.match(feedback.textContent, /Could not update/);
    fields[0].value = 'Computer Science';
    form.emit('change');
    assert.equal(requests[3].url.searchParams.get('program'), 'Computer Science');
    assert.equal(requests[3].url.searchParams.has('page'), false);
    requests[3].resolve({ ok: true, text: async () => 'CS tasks' });
    await tick();
    assert.equal(nodes['ct-results'].label, 'CS tasks');
});

test('typing searches after a pause, preserves dropdowns and ignores responses from an older search', async () => {
    const state = setup();
    state.search.value = 'Tech';
    state.form.emit('input', { target: state.search });
    state.search.value = 'Technology';
    state.form.emit('input', { target: state.search });
    assert.equal(state.timers.size, 1);
    assert.equal(state.requests.length, 0);
    state.flushTimers();
    assert.equal(state.requests[0].url.searchParams.get('search'), 'Technology');
    assert.equal(state.requests[0].url.searchParams.get('academic_year'), '2026-2027');
    assert.equal(state.requests[0].url.searchParams.get('program'), 'Information Technology');
    assert.equal(state.requests[0].url.searchParams.has('page'), false);
    state.search.value = 'Faculty';
    state.form.emit('input', { target: state.search });
    assert.equal(state.requests[0].options.signal.aborted, true);
    state.requests[0].resolve({ ok: true, text: async () => 'outdated tasks' });
    await tick();
    assert.equal(state.nodes['ct-results'].label, 'initial');
    state.flushTimers();
    state.requests[1].resolve({ ok: true, text: async () => 'faculty tasks' });
    await tick();
    assert.equal(state.nodes['ct-results'].label, 'faculty tasks');
    assert.equal(state.urls[0].searchParams.get('search'), 'Faculty');
    state.form.emit('change', { target: state.search });
    assert.equal(state.requests.length, 2);
});

test('Enter and Clear cancel pending searches while preserving the embedded view', () => {
    const state = setup({ embedded: true });
    state.search.value = 'Faculty';
    state.form.emit('input', { target: state.search });
    state.form.emit('submit');
    assert.equal(state.timers.size, 0);
    assert.equal(state.requests[0].url.searchParams.get('search'), 'Faculty');
    state.search.value = 'Old search';
    state.form.emit('input', { target: state.search });
    state.clear.emit('click');
    assert.equal(state.timers.size, 0);
    assert.equal(state.search.value, '');
    assert.equal(state.requests[1].url.search, '?embedded=1');
    state.flushTimers();
    assert.equal(state.requests.length, 2);
});

test('composition waits until completion and native search clear restores the filtered list', () => {
    const state = setup();
    state.search.value = 'Journal';
    state.form.emit('input', { target: state.search, isComposing: true });
    assert.equal(state.timers.size, 0);
    state.form.emit('compositionend', { target: state.search });
    state.flushTimers();
    assert.equal(state.requests[0].url.searchParams.get('search'), 'Journal');
    state.search.value = '';
    state.form.emit('input', { target: state.search });
    state.flushTimers();
    assert.equal(state.requests[1].url.searchParams.has('search'), false);
    assert.equal(state.requests[1].url.searchParams.get('program'), 'Information Technology');
});

test('search pagination retains its keyword and selecting a filter cancels pending typing', () => {
    const state = setup();
    const link = { href: state.form.action + '?search=Faculty&program=Information+Technology&page=2' };
    state.document.emit('click', { target: { closest: () => link } });
    assert.equal(state.requests[0].url.searchParams.get('search'), 'Faculty');
    assert.equal(state.requests[0].url.searchParams.get('page'), '2');
    state.search.value = 'Faculty';
    state.form.emit('input', { target: state.search });
    state.fields[0].value = 'Computer Science';
    state.form.emit('change', { target: state.fields[0] });
    assert.equal(state.timers.size, 0);
    assert.equal(state.requests[1].url.searchParams.get('search'), 'Faculty');
    assert.equal(state.requests[1].url.searchParams.get('program'), 'Computer Science');
    assert.equal(state.requests[1].url.searchParams.has('page'), false);
});
