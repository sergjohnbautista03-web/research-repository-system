import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/dean-semester-users.js', import.meta.url), 'utf8');
function element(props = {}) {
    return { ...props, events: {}, addEventListener(name, handler) { this.events[name] = handler; } };
}
function setup() {
    const boxes = [element({ checked: false }), element({ checked: false })];
    const all = element({ checked: false });
    const count = element();
    const button = element();
    const form = element({ querySelectorAll: () => boxes });
    const elements = { 'semester-users-form': form, 'semester-select-all': all,
        'semester-selection-count': count, 'semester-activate-button': button };
    vm.runInNewContext(source, { document: { getElementById: id => elements[id] }, window: {} });
    return { boxes, all, count, button, form };
}

test('selecting and clearing a page updates count, select-all state, and activation availability', () => {
    const { boxes, all, count, button } = setup();
    assert.equal(button.disabled, true);
    boxes[0].checked = true;
    boxes[0].events.change();
    assert.equal(count.textContent, '1 selected');
    assert.equal(all.indeterminate, true);
    assert.equal(button.disabled, false);
    all.checked = true;
    all.events.change();
    assert.ok(boxes.every(box => box.checked));
    assert.equal(count.textContent, '2 selected');
    assert.equal(all.indeterminate, false);
    all.checked = false;
    all.events.change();
    assert.ok(boxes.every(box => !box.checked));
    assert.equal(button.disabled, true);
});

test('empty selections cannot submit, while selected users can submit', () => {
    const { form, boxes } = setup();
    let prevented = false;
    form.events.submit({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);
    prevented = false;
    boxes[0].checked = true;
    form.events.submit({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, false);
});

test('student selection reveals year levels and switching away clears and disables that filter, including modal reloads', () => {
    const memberType = element({ value: '' });
    const yearLevel = element({ value: '2' });
    const yearFilter = element();
    const filters = { elements: { member_type: memberType, year_level: yearLevel }, querySelector: () => yearFilter };
    const window = {};
    vm.runInNewContext(source, { document: { querySelector: () => filters, getElementById: () => null }, window });
    assert.equal(yearFilter.hidden, true);
    assert.equal(yearLevel.disabled, true);
    assert.equal(yearLevel.value, '');
    memberType.value = 'student';
    memberType.events.change();
    assert.equal(yearFilter.hidden, false);
    assert.equal(yearLevel.disabled, false);
    yearLevel.value = '4';
    memberType.value = 'faculty';
    memberType.events.change();
    assert.equal(yearFilter.hidden, true);
    assert.equal(yearLevel.disabled, true);
    assert.equal(yearLevel.value, '');
    memberType.value = 'student';
    yearLevel.value = '2';
    window.SemesterUserSelection.initialize({ querySelector: selector => selector === '.su-filters' ? filters : null });
    assert.equal(yearFilter.hidden, false);
    assert.equal(yearLevel.disabled, false);
    assert.equal(yearLevel.value, '2');
});
