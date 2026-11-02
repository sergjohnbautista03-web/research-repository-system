import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

test('semester search debounces, rejects stale responses, updates modal data, paginates and clears filters', async () => {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; this.value = ''; }
        addEventListener(name, callback) { this.events[name] = callback; }
        emit(name, extra = {}) {
            const event = {target: this, button: 0, preventDefault() { this.prevented = true; }, ...extra};
            this.events[name]?.(event);
            return event;
        }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const search = new Element(), clear = new Element(), status = new Element();
    const selects = ['school_year', 'semester', 'status'].map(name => Object.assign(new Element(), {name}));
    const form = Object.assign(new Element(), {action: 'https://ube.test/admin/semesters', elements: {search}});
    form.querySelectorAll = () => selects;
    const result = label => Object.assign(new Element(), {
        label, replaceWith(next) { nodes['sem-record-results'] = next; },
        querySelector: () => ({textContent: `${label} terms`}),
    });
    const filters = () => ({replaceWith(next) { nodes['sem-active-filters'] = next; }});
    const nodes = {'sem-record-filters': form, 'sem-record-clear': clear, 'sem-search-status': status,
        'sem-record-results': result('initial'), 'semester-record-data': {textContent: '{}'}, 'sem-active-filters': filters()};
    const requests = [], urls = [], events = [];
    const document = Object.assign(new Element(), {getElementById: id => nodes[id], dispatchEvent: event => events.push(event.type)});
    vm.runInNewContext(readFileSync(new URL('../../public/js/semester-record-search.js', import.meta.url), 'utf8'), {
        document, URL, URLSearchParams, AbortController, Event, setTimeout, clearTimeout,
        history: {replaceState: (_state, _title, url) => urls.push(url)},
        FormData: class { *[Symbol.iterator]() { yield ['search', search.value]; for (const field of selects) yield [field.name, field.value]; } },
        DOMParser: class { parseFromString(label) { return {getElementById: id => ({
            'sem-record-results': result(label), 'semester-record-data': {textContent: JSON.stringify({label})},
            'sem-active-filters': filters(),
        })[id]}; } },
        fetch: (url, options) => new Promise(resolve => requests.push({url, options, resolve})),
    });
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    search.value = '1st'; search.emit('input');
    search.value = '1st Sem'; search.emit('input');
    await pause(300);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url.searchParams.get('search'), '1st Sem');
    search.value = '2026-2027'; search.emit('input');
    await pause(300);
    assert.equal(requests[0].options.signal.aborted, true);
    requests[1].resolve({ok: true, text: async () => '2'});
    await pause(0);
    requests[0].resolve({ok: true, text: async () => 'stale'});
    await pause(0);
    assert.equal(nodes['sem-record-results'].label, '2');
    assert.deepEqual(JSON.parse(nodes['semester-record-data'].textContent), {label: '2'});
    assert.deepEqual(events, ['semester-records-updated']);
    assert.equal(urls.length, 1);
    const page = 'https://ube.test/admin/semesters?search=1st+Sem&page=2';
    document.emit('click', {target: {closest: () => ({href: page})}});
    assert.equal(requests[2].url.href, page);
    requests[2].resolve({ok: true, text: async () => '3'});
    await pause(0);
    selects[0].value = '2026-2027';
    form.emit('change', {target: {matches: () => true}});
    assert.equal(requests[3].url.searchParams.get('school_year'), '2026-2027');
    assert.equal(requests[3].url.searchParams.has('page'), false);
    clear.emit('click');
    assert.equal(search.value, '');
    assert.ok(selects.every(field => field.value === ''));
    assert.equal(requests[4].url.searchParams.get('search'), '');
    requests[4].resolve({ok: false});
    await pause(0);
    assert.match(status.textContent, /Could not load/);
    assert.equal(nodes['sem-record-results'].attrs['aria-busy'], undefined);
});
