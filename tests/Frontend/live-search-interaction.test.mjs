import test from 'node:test';
import assert from 'node:assert/strict';

test('typing debounces requests, ignores stale responses, and supports keyboard selection and Escape', async () => {
    class Element {
        constructor() { this.events = {}; this.attrs = {}; this.children = []; this.style = {}; this.value = ''; }
        addEventListener(type, fn) { (this.events[type] ||= []).push(fn); }
        emit(type, detail = {}) { for (const fn of this.events[type] || []) fn({ preventDefault() {}, ...detail }); }
        setAttribute(key, value) { this.attrs[key] = value; }
        getAttribute(key) { return this.attrs[key] || null; }
        removeAttribute(key) { delete this.attrs[key]; }
        append(...children) { this.children.push(...children); }
        replaceChildren() { this.children = []; }
        insertAdjacentElement() {}
        getBoundingClientRect() { return { left: 10, top: 20, bottom: 60, width: 320 }; }
        scrollIntoView() {}
    }
    const previous = new Map(['document', 'window', 'location', 'innerWidth', 'innerHeight', 'FormData', 'DOMParser', 'fetch'].map(key => [key, Object.getOwnPropertyDescriptor(globalThis, key)]));
    const input = new Element();
    input.name = 'search';
    input.form = new Element();
    input.form.method = 'get';
    let submitted = 0;
    input.form.requestSubmit = () => submitted++;
    const body = new Element();
    const requests = [];
    const doc = new Element();
    doc.body = body;
    doc.activeElement = input;
    doc.createElement = () => new Element();
    doc.getElementById = () => null;
    doc.querySelectorAll = () => [input];
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    try {
        Object.assign(globalThis, {
            document: doc, window: new Element(), location: new URL('https://ube.test/admin/researches'), innerWidth: 1200, innerHeight: 800,
            FormData: class { *[Symbol.iterator]() { yield ['search', input.value]; yield ['department', 'BSIT']; } },
            DOMParser: class { parseFromString(text) { return { querySelectorAll: () => [{ dataset: { searchLabel: text } }] }; } },
            fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
        });
        await import('../../public/js/live-search.js?interaction-test');
        const menu = body.children[0];
        input.value = 'r'; input.emit('input');
        input.value = 'robot'; input.emit('input');
        await pause(300);
        assert.equal(requests.length, 1);
        assert.equal(requests[0].url.searchParams.get('search'), 'robot');
        assert.equal(requests[0].url.searchParams.get('department'), 'BSIT');
        input.value = 'marine'; input.emit('input');
        await pause(300);
        assert.equal(requests[0].options.signal.aborted, true);
        requests[1].resolve({ ok: true, redirected: false, text: async () => 'Marine research' });
        await pause(0);
        assert.equal(menu.children[0].children[0].textContent, 'Marine research');
        requests[0].resolve({ ok: true, redirected: false, text: async () => 'Old robot research' });
        await pause(0);
        assert.equal(menu.children[0].children[0].textContent, 'Marine research');
        input.emit('keydown', { key: 'ArrowDown' });
        assert.equal(input.attrs['aria-activedescendant'], menu.children[0].id);
        input.emit('keydown', { key: 'Enter' });
        assert.equal(input.value, 'Marine research');
        assert.equal(submitted, 1);
        assert.equal(menu.hidden, true);
        input.value = 'again'; input.emit('input');
        input.emit('keydown', { key: 'Escape' });
        await pause(300);
        assert.equal(requests.length, 2);
        assert.equal(input.attrs['aria-expanded'], 'false');
    } finally {
        for (const [key, descriptor] of previous) {
            if (descriptor) Object.defineProperty(globalThis, key, descriptor);
            else delete globalThis[key];
        }
    }
});
