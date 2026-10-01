import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/dean-semester-users-modal.js', import.meta.url), 'utf8');
class Element {
    constructor() { this.events = {}; this.attrs = {}; }
    addEventListener(name, handler) { this.events[name] = handler; }
    setAttribute(name, value) { this.attrs[name] = value; }
    removeAttribute(name) { delete this.attrs[name]; }
}
function setup() {
    const trigger = new Element();
    trigger.focus = () => { trigger.focused = true; };
    const close = new Element();
    const modal = Object.assign(new Element(), {
        dataset: { url: 'https://ube.test/admin/users/activate-existing' }, open: false,
        showModal() { this.open = true; }, close() { this.open = false; this.events.close(); },
        querySelector: () => close,
    });
    const controls = [{ disabled: false }, { disabled: true }];
    const body = Object.assign(new Element(), {
        querySelectorAll: () => controls, replaceChildren(content) { this.content = content; },
    });
    const message = new Element();
    const nodes = { activateExistingUsersModal: modal, 'semester-modal-body': body, 'semester-modal-message': message };
    const requests = [], events = [];
    let activation;
    const context = {
        URL, URLSearchParams, AbortController,
        document: { getElementById: id => nodes[id], querySelector: () => trigger,
            dispatchEvent: event => events.push(event.type) },
        window: { SemesterUserSelection: { initialize: (_root, handler) => { activation = handler; } } },
        CustomEvent: class { constructor(type) { this.type = type; } },
        FormData: class { constructor(form) { this.fields = form.fields ?? []; } *[Symbol.iterator]() { yield* this.fields; } },
        DOMParser: class { parseFromString(label) { return { querySelector: () => ({ label }) }; } },
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
    };
    vm.runInNewContext(source, context);
    const respond = (index, value, ok = true) => requests[index].resolve({
        ok, redirected: false, text: async () => value, json: async () => value,
    });
    return { trigger, close, modal, body, message, requests, events, controls, respond, activate: form => activation(form) };
}
const tick = () => new Promise(resolve => setTimeout(resolve, 0));

test('opening, filtering, and pagination stay inside the modal and discard stale responses', async () => {
    const ui = setup();
    ui.trigger.events.click();
    assert.equal(ui.modal.open, true);
    let prevented = false;
    ui.body.events.submit({ preventDefault: () => { prevented = true; }, target: {
        matches: () => true, action: ui.modal.dataset.url, fields: [['search', 'Ana'], ['member_type', 'student'], ['year_level', '2']],
    } });
    assert.equal(prevented, true);
    assert.equal(ui.requests[0].options.signal.aborted, true);
    assert.match(ui.requests[1].url, /search=Ana/);
    assert.match(ui.requests[1].url, /year_level=2/);
    ui.respond(1, 'filtered users');
    await tick();
    ui.respond(0, 'stale users');
    await tick();
    assert.equal(ui.body.content.label, 'filtered users');
    ui.body.events.click({ preventDefault() {}, target: { closest: () => ({ href: ui.modal.dataset.url + '?page=2' }) } });
    ui.respond(2, 'page 2');
    await tick();
    assert.equal(ui.body.content.label, 'page 2');
    ui.close.events.click();
    assert.equal(ui.modal.open, false);
    assert.equal(ui.trigger.focused, true);
});

test('activation prevents duplicate submissions, displays JSON errors, and refreshes after success', async () => {
    const ui = setup();
    ui.trigger.events.click();
    ui.respond(0, 'initial users');
    await tick();
    const form = { action: 'https://ube.test/admin/users/activate-existing', fields: [['user_ids[]', '4']] };
    const rejected = ui.activate(form);
    await ui.activate(form);
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.controls[0].disabled, true);
    ui.respond(1, { errors: { semester: ['The active semester changed.'] } }, false);
    await rejected;
    assert.equal(ui.message.textContent, 'The active semester changed.');
    assert.equal(ui.message.attrs.role, 'alert');
    assert.equal(ui.modal.open, true);
    assert.equal(ui.controls[0].disabled, false);
    assert.equal(ui.controls[1].disabled, true);
    const accepted = ui.activate(form);
    ui.respond(2, { message: '1 user activated.' });
    await tick();
    assert.deepEqual(ui.events, ['semester-users-activated']);
    ui.respond(3, 'already active users');
    await accepted;
    assert.equal(ui.message.textContent, '1 user activated.');
    assert.equal(ui.body.content.label, 'already active users');
    assert.equal(ui.modal.open, true);
});
