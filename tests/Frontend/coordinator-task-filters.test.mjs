import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('assigned task dropdowns refresh automatically, reject stale results and Clear resets every selection', async () => {
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
    const requests = [], urls = [];
    const context = vm.createContext({
        URL, AbortController, document,
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() { for (const field of fields) yield [field.name, field.value]; } },
        DOMParser: class { parseFromString(label) { return { getElementById: () => results(label) }; } },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/coordinator-task-filters.js', import.meta.url), 'utf8'), context);
    const tick = () => new Promise(resolve => setTimeout(resolve, 0));
    form.emit('change');
    assert.deepEqual([...requests[0].url.searchParams.entries()], fields.map(field => [field.name, field.value]));
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
