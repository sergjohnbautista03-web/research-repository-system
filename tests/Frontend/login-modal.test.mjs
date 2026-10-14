import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup(fetchResponse) {
    class Element {
        constructor() {
            this.events = {};
            this.attrs = {};
            this.dataset = {};
            this.children = {};
            this.hidden = false;
            this.disabled = false;
            this.textContent = '';
            const classes = new Set();
            this.classList = {
                add: name => classes.add(name), remove: name => classes.delete(name),
                contains: name => classes.has(name),
            };
        }
        addEventListener(name, handler) { this.events[name] = handler; }
        emit(name) {
            const event = { preventDefault() { this.prevented = true; } };
            return { event, done: this.events[name]?.(event) };
        }
        querySelector(selector) { return this.children[selector] || null; }
        querySelectorAll(selector) { return this.children[selector] || []; }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
        focus() { document.activeElement = this; }
    }
    const modal = new Element(), login = new Element(), forgot = new Element();
    const username = new Element(), email = new Element(), trigger = new Element();
    const back = new Element(), close = new Element(), open = new Element();
    const form = new Element(), submit = new Element(), label = new Element();
    const error = new Element(), status = new Element();
    forgot.hidden = true;
    error.hidden = true;
    status.hidden = true;
    username.value = 'student123';
    form.action = 'http://localhost/forgot-password';
    form.fields = { _token: 'csrf-token', email: 'student@example.com' };
    form.children = {
        '[type="submit"]': submit, '[data-forgot-password-submit-label]': label,
        '[data-forgot-password-error]': error, '[data-forgot-password-status]': status,
    };
    modal.children = {
        '#modal-login': username, '#modal-reset-email': email,
        '[data-auth-view="login"]': login, '[data-auth-view="forgot"]': forgot,
        '[data-forgot-password-form]': form, '[data-forgot-password-trigger]': trigger,
        '[data-back-to-login]': back, '[data-login-modal-close]': [close],
    };
    const document = new Element();
    document.body = new Element();
    document.children = { '[data-login-modal]': modal, '[data-login-modal-trigger]': [open] };
    const window = {
        location: { href: 'http://localhost/?department=CCS' },
        setTimeout: callback => callback(),
    };
    const requests = [];
    const context = vm.createContext({
        document, window,
        FormData: class { constructor(form) { this.fields = form.fields; } },
        fetch: async (url, options) => {
            requests.push({ url, options });
            return fetchResponse();
        },
    });
    const source = readFileSync(new URL('../../public/js/app.js', import.meta.url), 'utf8');
    const start = source.indexOf('function initializeLoginModal()');
    const end = source.indexOf('\nif (document.readyState', start);
    vm.runInContext(source.slice(start, end) + '\ninitializeLoginModal();', context);
    return { modal, login, forgot, username, email, trigger, back, close, open,
        form, submit, label, error, status, requests, document, window };
}

test('forgot password switches views in place and back preserves the entered username', () => {
    const ui = setup();
    ui.open.emit('click');
    assert.equal(ui.trigger.emit('click').event.prevented, true);
    assert.equal(ui.login.hidden, true);
    assert.equal(ui.forgot.hidden, false);
    assert.equal(ui.modal.attrs['aria-labelledby'], 'forgotModalTitle');
    assert.equal(ui.document.activeElement, ui.email);
    assert.equal(ui.window.location.href, 'http://localhost/?department=CCS');
    ui.back.emit('click');
    assert.equal(ui.login.hidden, false);
    assert.equal(ui.forgot.hidden, true);
    assert.equal(ui.username.value, 'student123');
    assert.equal(ui.document.activeElement, ui.username);
    assert.equal(ui.modal.attrs['aria-labelledby'], 'loginModalTitle');
    ui.trigger.emit('click');
    ui.close.emit('click');
    assert.equal(ui.modal.classList.contains('is-open'), false);
    ui.open.emit('click');
    assert.equal(ui.login.hidden, false);
    assert.equal(ui.forgot.hidden, true);
});

test('reset link submission stays on the page, includes CSRF and prevents duplicate requests', async () => {
    let resolve;
    const ui = setup(() => new Promise(done => { resolve = done; }));
    const submission = ui.form.emit('submit');
    assert.equal(submission.event.prevented, true);
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.label.textContent, 'Sending...');
    await ui.form.emit('submit').done;
    assert.equal(ui.requests.length, 1);
    const request = ui.requests[0];
    assert.equal(request.url, ui.form.action);
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.credentials, 'same-origin');
    assert.equal(request.options.headers.Accept, 'application/json');
    assert.equal(request.options.body.fields._token, 'csrf-token');
    assert.equal(request.options.body.fields.email, 'student@example.com');
    resolve({ ok: true, json: async () => ({ message: 'Reset link requested.' }) });
    await submission.done;
    assert.equal(ui.status.textContent, 'Reset link requested.');
    assert.equal(ui.status.hidden, false);
    assert.equal(ui.error.hidden, true);
    assert.equal(ui.submit.disabled, false);
    assert.equal(ui.form.attrs['aria-busy'], undefined);
    assert.equal(ui.window.location.href, 'http://localhost/?department=CCS');
});

test('validation errors appear in the modal and a successful retry clears them', async () => {
    let valid = false;
    const ui = setup(async () => valid
        ? { ok: true, json: async () => ({ message: 'Reset link requested.' }) }
        : { ok: false, status: 422, json: async () => ({ errors: { email: ['Enter a valid email.'] } }) });
    await ui.form.emit('submit').done;
    assert.equal(ui.error.hidden, false);
    assert.equal(ui.error.textContent, 'Enter a valid email.');
    assert.equal(ui.email.attrs['aria-invalid'], 'true');
    assert.equal(ui.submit.disabled, false);
    valid = true;
    await ui.form.emit('submit').done;
    assert.equal(ui.error.hidden, true);
    assert.equal(ui.status.hidden, false);
    assert.equal(ui.email.attrs['aria-invalid'], undefined);
});

test('expired sessions and unavailable mail show feedback and allow retry', async () => {
    for (const response of [
        { ok: false, status: 419 },
        { ok: false, status: 503, json: async () => ({ message: 'Please try again later.' }) },
        { ok: false, status: 429, json: async () => ({ message: 'Please wait before trying again.' }) },
    ]) {
        const ui = setup(async () => response);
        await ui.form.emit('submit').done;
        assert.equal(ui.error.hidden, false);
        assert.ok(ui.error.textContent.length > 0);
        assert.equal(ui.submit.disabled, false);
        assert.equal(ui.status.hidden, true);
    }
});
