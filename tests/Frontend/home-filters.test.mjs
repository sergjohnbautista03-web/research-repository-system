import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup(initial = 'http://localhost:8000/?search=technology&page=2') {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; this.style = {}; this.textContent = ''; }
        addEventListener(type, callback) { (this.events[type] ||= []).push(callback); }
        dispatchEvent(event) { for (const callback of this.events[event.type] || []) callback(event); }
        emit(type, extra = {}) {
            const event = { type, button: 0, preventDefault() { this.defaultPrevented = true; }, ...extra };
            this.dispatchEvent(event);
            return event;
        }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
        getBoundingClientRect() { return { height: 600 }; }
        focus(options) { this.focusOptions = options; }
        scrollIntoView(options) { (this.scrolls ||= []).push(options); }
    }
    const fields = ['search', 'field', 'department', 'year_from', 'year_to'].map(name => ({
        name, value: name === 'search' ? 'technology' : name === 'field' ? 'all' : '',
    }));
    const submit = Object.assign(new Element(), { textContent: 'Apply Filters' });
    const form = Object.assign(new Element(), {
        action: 'http://localhost:8000/',
        querySelector: () => submit,
        querySelectorAll: () => fields,
    });
    const results = new Element(), content = new Element(), status = new Element(), clear = new Element();
    content.innerHTML = 'initial results';
    clear.hidden = false;
    const document = Object.assign(new Element(), {
        getElementById: id => ({ 'home-filters': form, 'home-results': results,
            'home-results-content': content, 'home-results-status': status, 'home-clear-filters': clear })[id],
    });
    const requests = [], pushed = [];
    const window = Object.assign(new Element(), { location: new URL(initial), reloads: 0, fetch: true, AbortController });
    window.location.reload = () => window.reloads++;
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, document, window,
        Event: class { constructor(type) { this.type = type; } },
        FormData: class { *[Symbol.iterator]() { for (const field of fields) yield [field.name, field.value]; } },
        history: { pushState(_state, _title, url) { pushed.push(url.href); window.location.href = url.href; } },
        DOMParser: class {
            parseFromString(label) {
                return { getElementById(id) {
                    if (label === 'invalid') return null;
                    if (id === 'home-clear-filters') return { hidden: label === 'featured' };
                    return { innerHTML: label, querySelector: () => ({ textContent: '8 papers found' }) };
                } };
            }
        },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
    });
    vm.runInContext(readFileSync(new URL('../../public/js/home-filters.js', import.meta.url), 'utf8'), context);
    return { form, fields, submit, results, content, status, clear, document, window, requests, pushed,
        click(href, extra = {}) {
            const link = { href, hasAttribute: () => false };
            return document.emit('click', { target: { closest: () => link }, ...extra });
        },
        back(url) { window.location.href = url; window.emit('popstate'); },
    };
}

const tick = () => new Promise(resolve => setTimeout(resolve, 0));
async function complete(state, label, index = state.requests.length - 1) {
    state.requests[index].resolve({ ok: true, redirected: false, text: async () => label });
    await tick();
}

test('Apply filters updates only results, resets pagination and keeps the viewport in place', async () => {
    const state = setup();
    state.fields[0].value = 'robotics';
    state.fields[2].value = 'College of Computer Studies';
    state.fields[3].value = '2022';
    assert.equal(state.form.emit('submit').defaultPrevented, true);
    const request = state.requests[0];
    assert.equal(request.url.searchParams.get('search'), 'robotics');
    assert.equal(request.url.searchParams.get('department'), 'College of Computer Studies');
    assert.equal(request.url.searchParams.get('year_from'), '2022');
    assert.equal(request.url.searchParams.has('page'), false);
    assert.equal(state.results.attrs['aria-busy'], 'true');
    assert.equal(state.submit.disabled, true);
    assert.match(state.status.textContent, /Updating/);
    assert.equal(state.content.innerHTML, 'initial results');
    assert.equal(state.pushed.length, 0);
    await complete(state, 'robotics results');
    assert.equal(state.content.innerHTML, 'robotics results');
    assert.equal(state.results.scrolls, undefined);
    assert.equal(state.pushed.length, 1);
    assert.equal(state.window.location.searchParams.get('search'), 'robotics');
    assert.equal(state.results.attrs['aria-busy'], undefined);
    assert.equal(state.content.style.minHeight, '');
    assert.equal(state.submit.disabled, false);
    assert.equal(state.submit.textContent, 'Apply Filters');
    assert.equal(state.status.textContent, '8 papers found');
});

test('Pagination preserves applied filters and brings the next page into view', async () => {
    const state = setup();
    const url = 'http://localhost:8000/?search=technology&field=title&year_from=2022&page=3';
    assert.equal(state.click(url).defaultPrevented, true);
    assert.equal(state.requests[0].url.searchParams.get('page'), '3');
    assert.equal(state.requests[0].url.searchParams.get('search'), 'technology');
    await complete(state, 'page three');
    assert.equal(state.content.innerHTML, 'page three');
    assert.equal(state.fields[1].value, 'title');
    assert.equal(state.results.scrolls[0].block, 'start');
    assert.equal(state.results.focusOptions.preventScroll, true);
});

test('Clear restores the featured list and clears filters without reloading', async () => {
    const state = setup();
    state.click('http://localhost:8000/');
    await complete(state, 'featured');
    assert.equal(state.content.innerHTML, 'featured');
    assert.equal(state.clear.hidden, true);
    assert.equal(state.fields[0].value, '');
    assert.equal(state.fields[1].value, 'all');
    assert.equal(state.window.location.search, '');
    assert.equal(state.window.reloads, 0);
});

test('Back and Forward restore results and form values without adding history entries', async () => {
    const state = setup();
    state.form.emit('submit');
    await complete(state, 'applied');
    state.back('http://localhost:8000/?search=older&department=Engineering&page=2');
    assert.equal(state.requests[1].url.searchParams.get('page'), '2');
    await complete(state, 'older results');
    assert.equal(state.content.innerHTML, 'older results');
    assert.equal(state.fields[0].value, 'older');
    assert.equal(state.fields[2].value, 'Engineering');
    state.back('http://localhost:8000/?search=newer&field=author');
    await complete(state, 'newer results');
    assert.equal(state.content.innerHTML, 'newer results');
    assert.equal(state.fields[0].value, 'newer');
    assert.equal(state.fields[1].value, 'author');
    assert.equal(state.fields[2].value, '');
    assert.equal(state.pushed.length, 1);
});

test('A late response cannot overwrite results from a newer navigation', async () => {
    const state = setup();
    state.form.emit('submit');
    state.click('http://localhost:8000/');
    assert.equal(state.requests[0].options.signal.aborted, true);
    await complete(state, 'featured', 1);
    await complete(state, 'outdated', 0);
    assert.equal(state.content.innerHTML, 'featured');
    assert.equal(state.clear.hidden, true);
    assert.equal(state.submit.disabled, false);
    assert.equal(state.pushed.length, 1);
});

test('Failed, invalid and redirected responses keep the existing list and allow retry', async () => {
    const state = setup();
    state.form.emit('submit');
    state.requests[0].reject(new Error('Offline'));
    await tick();
    assert.equal(state.content.innerHTML, 'initial results');
    assert.match(state.status.textContent, /Could not update/);
    assert.equal(state.submit.disabled, false);
    assert.equal(state.results.attrs['aria-busy'], undefined);
    state.form.emit('submit');
    await complete(state, 'invalid');
    assert.equal(state.content.innerHTML, 'initial results');
    state.form.emit('submit');
    state.requests[2].resolve({ ok: true, redirected: true });
    await tick();
    assert.equal(state.pushed.length, 0);
    state.form.emit('submit');
    await complete(state, 'retried');
    assert.equal(state.content.innerHTML, 'retried');
});

test('Failed history restoration falls back to loading the selected URL from the server', async () => {
    const state = setup();
    state.back('http://localhost:8000/?search=previous');
    state.requests[0].reject(new Error('Offline'));
    await tick();
    assert.equal(state.window.reloads, 1);
    assert.equal(state.pushed.length, 0);
});

test('Modified clicks and unrelated destinations retain normal browser navigation', () => {
    const state = setup();
    const url = 'http://localhost:8000/?search=technology&page=3';
    assert.equal(state.click(url, { ctrlKey: true }).defaultPrevented, undefined);
    assert.equal(state.click(url, { button: 1 }).defaultPrevented, undefined);
    assert.equal(state.click('http://elsewhere.test/').defaultPrevented, undefined);
    assert.equal(state.click('http://localhost:8000/research/1').defaultPrevented, undefined);
    assert.equal(state.requests.length, 0);
});
