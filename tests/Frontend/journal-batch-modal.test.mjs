import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup() {
    class Element {
        constructor() {
            this.events = {}; this.attrs = {}; this.dataset = {};
            const classes = new Set();
            this.classList = { toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name), contains: name => classes.has(name) };
        }
        addEventListener(type, callback) { this.events[type] = callback; }
        emit(type, event = {}) { this.events[type]?.(event); }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
        focus() { this.focused = true; }
    }
    const modal = new Element(), frame = new Element(), title = new Element(), status = new Element(), close = new Element();
    modal.querySelector = () => close;
    modal.showModal = () => { modal.open = true; };
    modal.close = () => { if (modal.open) { modal.open = false; modal.emit('close'); } };
    modal.getBoundingClientRect = () => ({ left: 20, right: 1000, top: 20, bottom: 800 });
    frame.contentWindow = {};
    frame.contentDocument = { body: { hasAttribute: name => name === 'data-journal-batch-embedded', dataset: { batchTitle: 'Journal Batch — AY 2025-2026' } } };
    const link = Object.assign(new Element(), {
        href: 'http://localhost:8000/admin/journal-batches/3', isConnected: true,
        dataset: { batchTitle: 'Journal Batch — AY 2025-2026' },
    });
    const document = Object.assign(new Element(), {
        body: { style: { overflow: 'auto' } },
        getElementById: id => ({ journalBatchModal: modal, journalBatchModalFrame: frame,
            journalBatchModalTitle: title, journalBatchModalStatus: status })[id],
    });
    const window = Object.assign(new Element(), {
        location: { origin: 'http://localhost:8000', href: 'http://localhost:8000/admin/journal-batches?program=IT', reload: () => { window.reloaded = true; } },
    });
    const context = vm.createContext({ URL, document, window });
    vm.runInContext(readFileSync(new URL('../../public/js/journal-batch-modal.js', import.meta.url), 'utf8'), context);
    const click = (extra = {}) => {
        const event = { target: { closest: () => link }, button: 0, preventDefault() { this.prevented = true; }, ...extra };
        document.emit('click', event);
        return event;
    };
    const message = (type, extra = {}) => window.emit('message', {
        origin: window.location.origin, source: frame.contentWindow, data: { type }, ...extra,
    });
    return { modal, frame, title, status, close, link, document, window, click, message };
}

test('Open Batch keeps the list URL, loads embedded content and restores focus and scrolling on close', () => {
    const ui = setup();
    assert.equal(ui.click({ ctrlKey: true }).prevented, undefined);
    assert.equal(ui.modal.open, undefined);
    assert.equal(ui.click().prevented, true);
    assert.equal(ui.modal.open, true);
    assert.equal(ui.document.body.style.overflow, 'hidden');
    assert.equal(ui.frame.src, 'http://localhost:8000/admin/journal-batches/3?embedded=1');
    assert.equal(ui.window.location.href, 'http://localhost:8000/admin/journal-batches?program=IT');
    assert.equal(ui.frame.hidden, true);
    ui.frame.emit('load');
    assert.equal(ui.frame.hidden, false);
    assert.equal(ui.status.hidden, true);
    ui.close.emit('click');
    assert.equal(ui.frame.src, 'about:blank');
    assert.equal(ui.document.body.style.overflow, 'auto');
    assert.equal(ui.link.focused, true);
    assert.equal(ui.window.reloaded, undefined);

    ui.click();
    ui.modal.emit('cancel', { preventDefault() {} });
    assert.equal(ui.modal.open, false);
    ui.click();
    ui.modal.emit('click', { target: ui.modal, clientX: 200, clientY: 200 });
    assert.equal(ui.modal.open, true);
    ui.modal.emit('click', { target: ui.modal, clientX: 10, clientY: 10 });
    assert.equal(ui.modal.open, false);
});

test('only the current same-origin batch frame can close or mark the list for refresh', () => {
    const ui = setup();
    ui.click();
    ui.message('journal-batch-updated', { origin: 'https://unrelated.test' });
    ui.message('journal-batch-close', { source: {} });
    assert.equal(ui.modal.open, true);
    ui.close.emit('click');
    assert.equal(ui.window.reloaded, undefined);
    ui.click();
    ui.message('journal-batch-updated');
    ui.message('journal-batch-close');
    assert.equal(ui.modal.open, false);
    assert.equal(ui.window.reloaded, true);
});

test('an expired session or inaccessible batch shows an error in the modal', () => {
    const ui = setup();
    ui.click();
    ui.frame.contentDocument.body.hasAttribute = () => false;
    ui.frame.emit('load');
    assert.equal(ui.frame.hidden, true);
    assert.equal(ui.status.hidden, false);
    assert.match(ui.status.textContent, /Unable to open this batch/);
    assert.equal(ui.modal.attrs['aria-busy'], undefined);
});

test('embedded review relays Escape and completed actions to its parent', () => {
    const events = {}, pdfEvents = {}, messages = [];
    const pdf = { contentDocument: { addEventListener: (type, callback) => { pdfEvents[type] = callback; } }, addEventListener() {} };
    const context = vm.createContext({
        document: { body: { hasAttribute: name => ['data-journal-batch-embedded', 'data-batch-updated'].includes(name) }, addEventListener: (type, callback) => { events[type] = callback; }, querySelectorAll: () => [pdf] },
        window: { parent: { postMessage: (message, origin) => messages.push({ message, origin }) }, location: { origin: 'http://localhost:8000' } },
    });
    vm.runInContext(readFileSync(new URL('../../public/js/journal-batch-embed.js', import.meta.url), 'utf8'), context);
    assert.equal(messages[0].message.type, 'journal-batch-updated');
    events.keydown({ key: 'Escape', preventDefault() {} });
    assert.equal(messages[1].message.type, 'journal-batch-close');
    assert.equal(messages[1].origin, 'http://localhost:8000');
    pdfEvents.keydown({ key: 'Escape', preventDefault() {} });
    assert.equal(messages[2].message.type, 'journal-batch-close');
});

test('Assign Journal Task opens a smaller assignment dialog and cancel keeps the list unchanged', () => {
    const ui = setup();
    ui.link.href = 'http://localhost:8000/admin/research-handoffs/create';
    ui.link.dataset = { batchTitle: 'Assign Journal Task', batchMode: 'assignment' };
    ui.frame.contentDocument.body.dataset.batchTitle = 'Assign Journal Task';
    ui.click();
    assert.equal(ui.modal.classList.contains('jbm-assignment'), true);
    assert.equal(ui.frame.src, 'http://localhost:8000/admin/research-handoffs/create?embedded=1');
    assert.equal(ui.title.textContent, 'Assign Journal Task');
    assert.match(ui.status.textContent, /Loading assignment form/);
    ui.frame.emit('load');
    assert.equal(ui.frame.hidden, false);
    ui.message('journal-batch-close');
    assert.equal(ui.modal.open, false);
    assert.equal(ui.window.reloaded, undefined);
});

test('an assignment success closes the modal after marking the parent list for refresh', () => {
    const events = {}, messages = [];
    const context = vm.createContext({
        document: { body: { hasAttribute: () => true }, querySelectorAll: () => [], addEventListener: (type, callback) => { events[type] = callback; } },
        window: { parent: { postMessage: (message, origin) => messages.push({ message, origin }) }, location: { origin: 'http://localhost:8000' } },
    });
    vm.runInContext(readFileSync(new URL('../../public/js/journal-batch-embed.js', import.meta.url), 'utf8'), context);
    assert.deepEqual(messages.map(item => item.message.type), ['journal-batch-updated', 'journal-batch-close']);
    events.click({ target: { closest: () => ({}) }, preventDefault() {} });
    assert.equal(messages[2].message.type, 'journal-batch-close');
});
