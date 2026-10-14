import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup({ departmentFilter = true } = {}) {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; }
        addEventListener(type, callback) { this.events[type] = callback; }
        emit(type, extra = {}) { this.events[type]?.({ preventDefault() {}, ...extra }); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
    }
    const fields = [
        { name: 'program', value: 'Information Technology' },
        { name: 'school_year', value: '2026-2027' },
        ...(departmentFilter ? [{ name: 'department', value: '' }] : []),
    ];
    fields[0].replaceChildren = function (...options) { this.options = options; this.value = ''; };
    const programsByDepartment = {
        'College of Computer Studies': ['Information Technology', 'Computer Science'],
        ...(departmentFilter ? { 'College of Criminal Justice Education': ['Criminology'] } : {}),
    };
    const form = Object.assign(new Element(), {
        action: 'http://localhost:8000/admin/journal-batches', querySelectorAll: () => fields,
    });
    const clear = new Element(), feedback = new Element(), document = new Element();
    const nodes = { 'jbl-filters': form, 'jbl-clear': clear, 'jbl-feedback': feedback };
    nodes['batch-program'] = fields[0];
    nodes['batch-department'] = fields[2];
    nodes['jbl-programs'] = { textContent: JSON.stringify(programsByDepartment) };
    const results = label => Object.assign(new Element(), {
        label, replaceWith(next) { nodes['jbl-results'] = next; },
    });
    nodes['jbl-results'] = results('initial');
    document.getElementById = id => nodes[id];
    const requests = [], urls = [];
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, document,
        Option: class { constructor(text, value) { this.text = text; this.value = value; } },
        history: { replaceState: (_state, _title, url) => urls.push(url) },
        FormData: class { *[Symbol.iterator]() { for (const field of fields) yield [field.name, field.value]; } },
        DOMParser: class { parseFromString(label) { return { getElementById: () => label === 'invalid' ? null : results(label) }; } },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/journal-batch-list.js', import.meta.url), 'utf8'), context);
    return { fields, form, clear, feedback, document, nodes, requests, urls };
}
const tick = () => new Promise(resolve => setTimeout(resolve, 0));

test('Dean filters update and clear without a Department dropdown', async () => {
    const state = setup({ departmentFilter: false });
    assert.deepEqual(Array.from(state.fields[0].options, option => option.value), ['', 'Information Technology', 'Computer Science']);
    state.form.emit('change', { target: state.fields[0] });
    assert.equal(state.requests[0].url.searchParams.has('department'), false);
    assert.equal(state.requests[0].url.searchParams.get('program'), 'Information Technology');
    state.requests[0].resolve({ ok: true, text: async () => 'Dean batches' });
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'Dean batches');
    state.clear.emit('click');
    assert.ok(state.fields.every(field => field.value === ''));
    assert.equal(state.requests[1].url.search, '');
});

test('changing departments offers only their programs, clears the old program and updates results automatically', () => {
    const state = setup();
    const program = state.fields[0], department = state.fields[2];
    department.value = 'College of Computer Studies';
    state.form.emit('change', { target: department });
    assert.deepEqual(Array.from(program.options, option => option.value), ['', 'Information Technology', 'Computer Science']);
    assert.equal(program.value, '');
    assert.equal(state.requests[0].url.searchParams.get('department'), department.value);
    assert.equal(state.requests[0].url.searchParams.has('program'), false);
    program.value = 'Information Technology';
    department.value = 'College of Criminal Justice Education';
    state.form.emit('change', { target: department });
    assert.deepEqual(Array.from(program.options, option => option.value), ['', 'Criminology']);
    assert.equal(program.value, '');
    assert.equal(state.requests[1].url.searchParams.get('department'), department.value);
    assert.equal(state.requests[1].url.searchParams.has('program'), false);
    assert.equal(state.requests[1].url.searchParams.get('school_year'), '2026-2027');
    state.clear.emit('click');
    assert.equal(department.value, '');
    assert.deepEqual(Array.from(program.options, option => option.value), ['', 'Information Technology', 'Computer Science', 'Criminology']);
    assert.equal(state.requests[2].url.search, '');
});

test('batch dropdowns update automatically and Clear discards a stale response and selected filters', async () => {
    const state = setup();
    state.form.emit('change');
    assert.equal(state.requests[0].url.searchParams.get('program'), 'Information Technology');
    assert.equal(state.requests[0].url.searchParams.get('school_year'), '2026-2027');
    assert.equal(state.nodes['jbl-results'].attrs['aria-busy'], 'true');
    state.clear.emit('click');
    assert.equal(state.requests[0].options.signal.aborted, true);
    assert.ok(state.fields.every(field => field.value === ''));
    assert.equal(state.requests[1].url.search, '');
    state.requests[1].resolve({ ok: true, text: async () => 'all batches' });
    await tick();
    state.requests[0].resolve({ ok: true, text: async () => 'stale batches' });
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'all batches');
    assert.equal(state.nodes['jbl-results'].attrs['aria-busy'], undefined);
    assert.equal(state.urls.length, 1);
    assert.equal(state.urls[0].search, '');
});

test('batch pagination keeps the filters and a new dropdown selection returns to page one', async () => {
    const state = setup();
    const link = { href: 'http://localhost:8000/admin/journal-batches?program=Information+Technology&school_year=2026-2027&page=2' };
    const target = { closest: selector => selector === '#jbl-results .jbl-pagination a' ? link : null };
    state.document.emit('click', { target, ctrlKey: true });
    assert.equal(state.requests.length, 0);
    state.document.emit('click', { target });
    assert.equal(state.requests[0].url.searchParams.get('page'), '2');
    assert.equal(state.requests[0].url.searchParams.get('program'), 'Information Technology');
    state.requests[0].resolve({ ok: true, text: async () => 'page two' });
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'page two');
    state.fields[0].value = 'Computer Science';
    state.form.emit('change');
    assert.equal(state.requests[1].url.searchParams.get('program'), 'Computer Science');
    assert.equal(state.requests[1].url.searchParams.has('page'), false);
});

test('failed or invalid batch responses preserve the displayed list and allow retry', async () => {
    const state = setup();
    state.form.emit('change');
    state.requests[0].reject(new Error('Offline'));
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'initial');
    assert.match(state.feedback.textContent, /Could not update/);
    assert.equal(state.nodes['jbl-results'].attrs['aria-busy'], undefined);
    assert.equal(state.urls.length, 0);
    state.form.emit('submit');
    state.requests[1].resolve({ ok: true, text: async () => 'invalid' });
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'initial');
    state.form.emit('change');
    state.requests[2].resolve({ ok: true, text: async () => 'updated batches' });
    await tick();
    assert.equal(state.nodes['jbl-results'].label, 'updated batches');
    assert.equal(state.feedback.textContent, 'Journal batches updated.');
});
