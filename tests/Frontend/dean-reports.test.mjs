import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('report filters refresh immediately, reject stale responses, and keep printing synchronized', async () => {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; }
        addEventListener(name, fn) { this.events[name] = fn; }
        emit(name, extra = {}) { this.events[name]({ preventDefault() {}, ...extra }); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const form = Object.assign(new Element(), { action: 'https://ube.test/admin/reports' });
    const fields = ['school_year', 'semester', 'program', 'submission_category', 'type', 'status'].map(name => ({ name, value: 'selected' }));
    form.querySelectorAll = () => fields;
    const nodes = { 'dr-filters': form, 'dr-feedback': new Element(), 'dr-print': new Element(), 'dr-clear': new Element() };
    const result = label => Object.assign(new Element(), { label, replaceWith(next) { nodes['dr-results'] = next; } });
    nodes['dr-results'] = result('initial');
    nodes['dr-summary'] = new Element();
    nodes['dr-summary'].replaceWith = next => { nodes['dr-summary'].label = next.label; };
    const requests = [], urls = [];
    let printed = 0;
    const context = vm.createContext({
        URL, URLSearchParams, AbortController,
        document: Object.assign(new Element(), { getElementById: id => nodes[id], querySelector: () => ({ innerHTML: 'Approved report' }) }),
        window: { ReportPrint: { printHtml: () => printed++ } },
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() { for (const field of fields) yield [field.name, field.value]; } },
        DOMParser: class { parseFromString(label) { return { getElementById: () => result(label) }; } },
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/dean-reports.js', import.meta.url), 'utf8'), context);
    const tick = () => new Promise(resolve => setTimeout(resolve, 0));
    form.emit('change');
    assert.equal(requests.length, 1);
    assert.equal(nodes['dr-print'].disabled, true);
    assert.equal(requests[0].url.searchParams.get('status'), 'selected');
    fields[5].value = 'approved';
    form.emit('change');
    assert.equal(requests[0].options.signal.aborted, true);
    requests[1].resolve({ ok: true, text: async () => 'approved results with summary and print rows' });
    await tick();
    requests[0].resolve({ ok: true, text: async () => 'stale' });
    await tick();
    assert.match(nodes['dr-results'].label, /^approved/);
    assert.match(nodes['dr-summary'].label, /^approved/);
    assert.equal(nodes['dr-print'].disabled, false);
    nodes['dr-print'].emit('click');
    assert.equal(printed, 1);
    assert.equal(urls.length, 1);
    nodes['dr-clear'].emit('click');
    assert.ok(fields.every(field => field.value === ''));
    assert.equal(requests[2].url.searchParams.has('page'), false);
    requests[2].resolve({ ok: false });
    await tick();
    assert.match(nodes['dr-feedback'].textContent, /Could not update/);
    assert.equal(nodes['dr-print'].disabled, true);
});
