import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/document-protection.js', import.meta.url), 'utf8');
function element(props = {}) {
    const events = new Map();
    const classes = new Set();
    return {
        ...props, events, attributes: {},
        classList: { toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
            contains: name => classes.has(name) },
        addEventListener(type, fn) {
            if (!events.has(type)) events.set(type, new Set());
            events.get(type).add(fn);
        },
        removeEventListener(type, fn) { events.get(type)?.delete(fn); },
        emit(type, props = {}) {
            const event = { type, prevented: false, stopped: false,
                preventDefault() { this.prevented = true; },
                stopPropagation() { this.stopped = true; }, ...props };
            [...(events.get(type) || [])].forEach(fn => fn(event));
            return event;
        },
        setAttribute(name, value) { this.attributes[name] = value; },
    };
}
function viewer(topWindow = null, platform = '') {
    const guard = element(), shell = element(), title = element(), description = element(), resume = element();
    let focused = true;
    const document = element({ hidden: false, hasFocus: () => focused,
        getElementById: id => ({ screenGuard: guard, screenGuardTitle: title,
            screenGuardDescription: description, resumeViewing: resume })[id],
        querySelector: () => shell });
    const logs = [];
    const window = element({ document, navigator: { platform }, logCaptureAttempt: (type, details) => {
        assert.ok(guard.classList.contains('is-visible') || type === 'window_blur' || type === 'tab_hidden');
        logs.push({ type, details });
    } });
    window.top = window.parent = topWindow || window;
    vm.runInNewContext(source, { window, document });
    return { guard, shell, title, resume, logs, window, document, focus: value => { focused = value; } };
}
function detail(origin = 'https://repository.philcst.edu.ph') {
    let focused = true;
    const container = element({ style: { display: 'none' }, scrollIntoView() {} });
    const frame = element({ dataset: { src: `${origin}/research/35/view-file` } });
    const button = element({ innerHTML: 'View Full Document' });
    const document = element({ hasFocus: () => focused,
        getElementById: id => ({ pdfFrame: frame, viewerBtn: button })[id],
        querySelector: () => container });
    const window = element({ location: { href: `${origin}/research/35`, origin }, document });
    vm.runInNewContext(source, { window, document, URL });
    return { window, document, container, frame, button, focus: value => { focused = value; } };
}

test('capture shortcuts immediately hide the document before logging, with no PDF renderer dependency', () => {
    const shortcuts = [
        { key: 'PrintScreen' }, { code: 'PrintScreen' }, { key: 'SnapShot' }, { keyCode: 44 },
        { key: 'S', shiftKey: true, metaKey: true }, { code: 'KeyS', shiftKey: true, metaKey: true },
        { key: '#', code: 'Digit3', shiftKey: true, metaKey: true },
        { key: '4', shiftKey: true, metaKey: true }, { key: '5', shiftKey: true, metaKey: true },
    ];
    for (const shortcut of shortcuts) {
        for (const phase of ['keydown', 'keyup']) {
            const app = viewer();
            const event = app.document.emit(phase, shortcut);
            assert.equal(event.prevented, true);
            assert.equal(app.title.textContent, 'Content Protected by PHILCST.');
            assert.equal(app.guard.attributes['aria-hidden'], 'false');
            assert.ok(app.shell.classList.contains('is-obscured'));
            assert.equal(app.logs[0].type, 'printscreen');
            assert.equal(app.resume.hidden, false);
        }
    }
});

test('platform-specific shortcuts distinguish Mac save-as from a Windows screenshot', () => {
    const mac = viewer(null, 'MacIntel');
    mac.document.emit('keydown', { key: 'S', metaKey: true, shiftKey: true });
    assert.equal(mac.logs.at(-1).type, 'save_blocked');
    const windows = viewer(null, 'Win32');
    windows.document.emit('keydown', { key: '#', code: 'Digit3', metaKey: true, shiftKey: true });
    assert.equal(windows.logs.length, 0);
    windows.document.emit('keydown', { key: 's', metaKey: true, shiftKey: true });
    assert.equal(windows.logs.at(-1).type, 'printscreen');
    mac.document.emit('keydown', { key: '$', code: 'Digit4', metaKey: true, shiftKey: true });
    assert.equal(mac.logs.at(-1).type, 'printscreen');
});

test('network failures cannot disable the warning; focus alone does not dismiss a screenshot warning', () => {
    const app = viewer();
    app.window.logCaptureAttempt = () => { throw new Error('network unavailable'); };
    app.document.emit('keydown', { key: 'PrintScreen' });
    app.focus(false);
    app.window.emit('blur');
    assert.equal(app.resume.disabled, true);
    app.resume.emit('click');
    assert.ok(app.guard.classList.contains('is-visible'));
    app.focus(true);
    app.window.emit('focus');
    assert.ok(app.guard.classList.contains('is-visible'));
    app.resume.emit('click');
    assert.equal(app.guard.classList.contains('is-visible'), false);
});

test('inactive windows/tabs hide the viewer without claiming a screenshot; returning and browser cache restores work', () => {
    const app = viewer();
    app.focus(false);
    app.window.emit('blur');
    assert.ok(app.guard.classList.contains('is-visible'));
    assert.equal(app.logs[0].type, 'window_blur');
    assert.equal(app.resume.hidden, true);
    app.focus(true);
    app.window.emit('focus');
    assert.equal(app.guard.classList.contains('is-visible'), false);
    app.document.hidden = true;
    app.document.emit('visibilitychange');
    assert.equal(app.logs.at(-1).type, 'tab_hidden');
    app.document.hidden = false;
    app.document.emit('visibilitychange');
    assert.equal(app.guard.classList.contains('is-visible'), false);
    app.window.emit('pagehide');
    app.window.emit('pageshow');
    assert.equal(app.guard.classList.contains('is-visible'), false);
    app.document.emit('keyup', { key: 'PrintScreen' });
    assert.equal(app.logs.at(-1).type, 'printscreen');
});

test('only the embedded viewer is protected, including keyboard events focused on its parent', () => {
    const page = detail();
    assert.equal(page.frame.attributes.src, undefined);
    assert.equal(page.document.emit('keydown', { key: 'PrintScreen' }).prevented, false);
    page.window.toggleProtectedDocument();
    assert.equal(page.frame.attributes.src, 'https://repository.philcst.edu.ph/research/35/view-file');
    const app = viewer(page.window);
    page.frame.contentWindow = app.window;
    assert.equal(page.document.emit('keydown', { key: 'c', ctrlKey: true }).prevented, false);
    assert.equal(page.document.emit('contextmenu').prevented, false);
    assert.equal(page.document.emit('keyup', { key: 'PrintScreen' }).prevented, true);
    assert.ok(app.guard.classList.contains('is-visible'));
    assert.equal(page.container.style.display, 'block');
    assert.equal(page.document.querySelector('anything').classList.contains('is-obscured'), false);
});

test('focus moving into/out of the iframe does not hide it; OS window blur hides only the document', () => {
    const page = detail();
    page.window.toggleProtectedDocument();
    const app = viewer(page.window);
    page.frame.contentWindow = app.window;
    app.focus(false);
    app.window.emit('blur');
    page.window.emit('blur');
    assert.equal(app.guard.classList.contains('is-visible'), false);
    assert.equal(app.logs.length, 0);
    page.focus(false);
    page.window.emit('blur');
    assert.ok(app.guard.classList.contains('is-visible'));
    assert.equal(app.logs.at(-1).type, 'window_blur');
    page.focus(true);
    page.window.emit('focus');
    assert.equal(app.guard.classList.contains('is-visible'), false);
});

test('closing disposes listeners and unloaded document logs; reopening loads a fresh document', () => {
    const page = detail();
    page.window.toggleProtectedDocument();
    const app = viewer(page.window);
    page.frame.contentWindow = app.window;
    page.window.toggleProtectedDocument();
    assert.equal(page.frame.attributes.src, 'about:blank');
    assert.equal(page.container.style.display, 'none');
    assert.equal(page.button.innerHTML, 'View Full Document');
    assert.equal(page.button.attributes['aria-expanded'], 'false');
    assert.equal(page.document.emit('keyup', { key: 'PrintScreen' }).prevented, false);
    assert.equal(app.document.emit('keyup', { key: 'PrintScreen' }).prevented, false);
    app.window.logCaptureAttempt('protected_view_opened');
    page.focus(false);
    page.window.emit('blur');
    assert.equal(app.logs.length, 0);
    page.window.toggleProtectedDocument();
    assert.equal(page.frame.attributes.src, 'https://repository.philcst.edu.ph/research/35/view-file');
    page.window.emit('pagehide');
    assert.equal(page.frame.attributes.src, 'about:blank');
});

test('local and hosted pages load same-origin viewer routes and reject foreign frame URLs', () => {
    for (const origin of ['http://127.0.0.1:8000', 'https://repository.philcst.edu.ph']) {
        const page = detail(origin);
        page.window.toggleProtectedDocument();
        assert.equal(page.frame.attributes.src, `${origin}/research/35/view-file`);
    }
    const page = detail();
    page.frame.dataset.src = 'https://untrusted.example/document';
    page.window.toggleProtectedDocument();
    assert.equal(page.frame.attributes.src, undefined);
    assert.equal(page.container.style.display, 'none');
});

test('ordinary pages have no protection listeners and document print/copy blocking stays inside viewer', () => {
    const document = element({ getElementById: () => null, querySelector: () => null });
    const window = element();
    vm.runInNewContext(source, { document, window });
    assert.equal(document.events.size, 0);
    assert.equal(window.events.size, 0);
    const app = viewer();
    assert.equal(app.document.emit('copy').prevented, true);
    assert.equal(app.document.emit('contextmenu').prevented, true);
    app.window.emit('beforeprint');
    assert.equal(app.logs.at(-1).type, 'print_blocked');
    assert.ok(app.guard.classList.contains('is-visible'));
});
