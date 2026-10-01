import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

class Element {
    constructor() { this.events = {}; this.children = []; this.attrs = {}; this.dataset = {}; }
    addEventListener(name, callback) { this.events[name] = callback; }
    setAttribute(name, value) { this.attrs[name] = value; }
    append(...children) { this.children.push(...children); }
    replaceChildren() { this.children = []; }
    showModal() { this.open = true; }
    close() { this.open = false; this.events.close(); }
    focus() {}
    querySelector(selector) { return this.nodes?.[selector]; }
    querySelectorAll(selector) {
        const descendants = this.children.flatMap(child => [child, ...child.querySelectorAll(selector)]);
        return descendants.filter(child => selector === '[data-mark-read]' && child.dataset.markRead);
    }
}

const flush = () => new Promise(resolve => setImmediate(resolve));
function setup() {
    function makeView() {
        const view = new Element();
        view.nodes = Object.fromEntries(['[data-unread-label]', '[data-mark-all]', '[data-notification-error]', '[data-notification-items]', '[data-notification-more]'].map(key => [key, new Element()]));
        view.children = Object.values(view.nodes);
        return view;
    }
    const panel = makeView(), inline = makeView(), toggle = new Element(), badge = new Element(), close = new Element();
    panel.dataset = { feedUrl: '/admin/notifications/feed', readUrl: '/admin/notifications/read', readAllUrl: '/admin/notifications/read-all' };
    toggle.nodes = { '.topbar-notification-count': badge };
    const requests = [], timers = [];
    const document = new Element();
    document.hidden = false;
    document.body = { classList: { add() {}, remove() {} } };
    document.querySelector = selector => selector === '[data-notification-panel]' ? panel : { content: 'test-token' };
    document.querySelectorAll = () => [panel, inline];
    document.getElementById = id => id === 'notificationToggle' ? toggle : close;
    document.createElement = () => new Element();
    const context = vm.createContext({
        document, window: { location: { href: 'http://localhost/admin/users' } }, URL, AbortController,
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url: String(url), options, resolve, reject })),
        setInterval: (callback, delay) => { assert.equal(delay, 30000); timers.push(callback); },
    });
    vm.runInContext(readFileSync(new URL('../../public/js/notifications.js', import.meta.url), 'utf8'), context);
    return { panel, inline, toggle, badge, close, document, requests, poll: timers[0] };
}

function item(number, isRead = false) {
    return { id: `received:${number}`, message: `<script>Paper ${number}</script>`, date: '2026-10-02T12:00:00+08:00', url: '/handoffs', is_read: isRead, read_at: isRead ? '2026-10-02T12:01:00+08:00' : null };
}
function snapshot(items, unreadCount = items.filter(item => !item.is_read).length, totalCount = items.length) {
    return { notifications: items, unread_count: unreadCount, total_count: totalCount, page: 1, has_more: totalCount > 50 };
}
function respond(request, data) { request.resolve({ ok: true, json: async () => data }); }

test('all notification views highlight unread items, render titles safely, and opening the bell does not mark them read', async () => {
    const ui = setup();
    const initial = snapshot([item(1), item(2)]);
    respond(ui.requests.shift(), initial);
    await flush();
    assert.equal(ui.badge.textContent, '2');
    assert.equal(ui.badge.hidden, false);
    const article = ui.panel.nodes['[data-notification-items]'].children[0];
    assert.match(article.className, /is-unread/);
    assert.equal(article.children[0].children[0].textContent, item(1).message);
    assert.equal(ui.panel.querySelectorAll('[data-mark-read]').length, 2);
    ui.toggle.events.click();
    assert.equal(ui.panel.open, true);
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.requests[0].options.method, undefined);
    respond(ui.requests.shift(), initial);
    await flush();
    assert.equal(ui.badge.textContent, '2');
    assert.match(ui.toggle.attrs['aria-label'], /2 unread/);
    assert.equal(ui.inline.querySelectorAll('[data-mark-read]').length, 2);
});

test('individual and bulk reads wait for successful saves, decrease the badge, sync page and bell, and hide it at zero', async () => {
    const ui = setup();
    respond(ui.requests.shift(), snapshot([item(1), item(2)]));
    await flush();
    const readButton = ui.panel.querySelectorAll('[data-mark-read]')[0];
    readButton.events.click();
    readButton.events.click();
    assert.equal(ui.requests.length, 1);
    const request = ui.requests.shift();
    assert.equal(request.url, '/admin/notifications/read');
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.headers['X-CSRF-TOKEN'], 'test-token');
    assert.deepEqual(JSON.parse(request.options.body), { id: 'received:1' });
    assert.equal(ui.badge.textContent, '2');
    assert.equal(ui.panel.nodes['[data-mark-all]'].disabled, true);
    respond(request, snapshot([item(1, true), item(2)]));
    await flush();
    assert.equal(ui.badge.textContent, '1');
    assert.match(ui.panel.nodes['[data-notification-items]'].children[0].className, /is-read/);
    assert.equal(ui.inline.querySelectorAll('[data-mark-read]').length, 1);
    ui.inline.nodes['[data-mark-all]'].events.click();
    const allRequest = ui.requests.shift();
    assert.equal(allRequest.url, '/admin/notifications/read-all');
    respond(allRequest, snapshot([item(1, true), item(2, true)]));
    await flush();
    assert.equal(ui.badge.hidden, true);
    assert.equal(ui.panel.querySelectorAll('[data-mark-read]').length, 0);
    assert.equal(ui.inline.querySelectorAll('[data-mark-read]').length, 0);
    assert.equal(ui.panel.nodes['[data-mark-all]'].disabled, true);
});

test('failed reads leave counts and highlighting unchanged, and stale polling cannot undo a successful read', async () => {
    const ui = setup();
    respond(ui.requests.shift(), snapshot([item(1)]));
    await flush();
    ui.poll();
    const stale = ui.requests.shift();
    ui.panel.querySelectorAll('[data-mark-read]')[0].events.click();
    const read = ui.requests.shift();
    respond(read, snapshot([item(1, true)]));
    await flush();
    respond(stale, snapshot([item(1)]));
    await flush();
    assert.equal(ui.badge.hidden, true);
    assert.equal(ui.panel.querySelectorAll('[data-mark-read]').length, 0);
    ui.poll();
    respond(ui.requests.shift(), snapshot([item(1, true), item(2)]));
    await flush();
    ui.panel.nodes['[data-mark-all]'].events.click();
    ui.requests.shift().resolve({ ok: false });
    await flush();
    assert.equal(ui.badge.textContent, '1');
    assert.equal(ui.badge.hidden, false);
    assert.equal(ui.panel.querySelectorAll('[data-mark-read]').length, 1);
    assert.equal(ui.panel.nodes['[data-notification-error]'].hidden, false);
    assert.equal(ui.panel.nodes['[data-mark-all]'].disabled, false);
});

test('pagination uses the full unread count and items outside the first page can be read', async () => {
    const ui = setup();
    const firstPage = Array.from({ length: 50 }, (_, index) => item(index + 1));
    const secondPage = Array.from({ length: 30 }, (_, index) => item(index + 51));
    respond(ui.requests.shift(), snapshot(firstPage, 80, 80));
    await flush();
    assert.equal(ui.badge.textContent, '80');
    assert.equal(ui.panel.nodes['[data-notification-more]'].hidden, false);
    ui.panel.nodes['[data-notification-more]'].events.click();
    assert.equal(ui.requests.length, 2);
    respond(ui.requests.shift(), snapshot(firstPage, 80, 80));
    respond(ui.requests.shift(), { ...snapshot(secondPage, 80, 80), page: 2, has_more: false });
    await flush();
    assert.equal(ui.panel.nodes['[data-notification-items]'].children.length, 80);
    assert.equal(ui.panel.nodes['[data-notification-more]'].hidden, true);
    ui.panel.querySelectorAll('[data-mark-read]')[79].events.click();
    respond(ui.requests.shift(), snapshot(firstPage, 79, 80));
    await flush();
    assert.equal(ui.badge.textContent, '79');
    assert.match(ui.panel.nodes['[data-notification-items]'].children[79].className, /is-read/);
    ui.panel.nodes['[data-mark-all]'].events.click();
    respond(ui.requests.shift(), snapshot(firstPage.map(item => ({ ...item, is_read: true })), 0, 80));
    await flush();
    assert.equal(ui.badge.hidden, true);
    assert.equal(ui.panel.querySelectorAll('[data-mark-read]').length, 0);
});
