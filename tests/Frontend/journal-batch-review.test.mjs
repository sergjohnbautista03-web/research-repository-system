import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('batch review fits each protected PDF to its content and rejects unrelated messages', () => {
    const origin = 'http://localhost:8000';
    const listeners = {};
    const frames = [0, 1].map(() => ({
        style: {}, events: {}, messages: [],
        addEventListener(type, callback) { this.events[type] = callback; },
        contentWindow: { postMessage(message, target) { frames.find(frame => frame.contentWindow === this).messages.push({ message, target }); } },
    }));
    const context = vm.createContext({
        document: { querySelectorAll: () => frames },
        window: { location: { origin }, addEventListener(type, callback) { listeners[type] = callback; } },
    });
    vm.runInContext(readFileSync(new URL('../../public/js/journal-batch-review.js', import.meta.url), 'utf8'), context);
    frames.forEach(frame => {
        assert.equal(frame.messages[0].message.type, 'journal-review-measure');
        assert.equal(frame.messages[0].target, origin);
    });
    const send = (frame, height, extra = {}) => listeners.message({ origin, source: frame.contentWindow,
        data: { type: 'journal-review-height', height }, ...extra });
    send(frames[0], 4800.2);
    send(frames[1], 9200);
    assert.equal(frames[0].style.height, '4801px');
    assert.equal(frames[1].style.height, '9200px');
    send(frames[0], 500, { origin: 'https://other.example' });
    send(frames[0], 500, { source: {} });
    for (const height of ['500', NaN, Infinity, -10, 0]) send(frames[0], height);
    assert.equal(frames[0].style.height, '4801px');
    send(frames[0], 120);
    assert.equal(frames[0].style.height, '320px');
    frames[0].events.load();
    assert.equal(frames[0].messages.length, 2);
});
