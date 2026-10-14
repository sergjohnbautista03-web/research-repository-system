import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/dean-import-users.js', import.meta.url), 'utf8');

function setup(selectedType = 'student') {
    const types = ['student', 'faculty'].map(value => ({
        value, checked: value === selectedType, listeners: {},
        addEventListener(event, listener) { this.listeners[event] = listener; },
    }));
    const guides = types.map(input => ({ dataset: { importGuide: input.value }, hidden: false }));
    let files = [];
    const file = {
        listeners: {}, validityMessage: '',
        get files() { return files; },
        set value(value) { if (value === '') files = []; },
        setCustomValidity(message) { this.validityMessage = message; },
        addEventListener(event, listener) { this.listeners[event] = listener; },
    };
    const filename = {}, submit = {};
    const form = { querySelectorAll(selector) { return selector === '[data-import-guide]' ? guides : types; } };
    const elements = { iu_import_form: form, iu_file: file, iu_filename: filename, iu_submit_btn: submit };
    vm.runInNewContext(source, { document: { getElementById: id => elements[id] } });
    return {
        guides, filename, submit, file,
        select(type) {
            types.forEach(input => { input.checked = input.value === type; });
            types.find(input => input.checked).listeners.change();
        },
        upload(name, size = 100) { files = [{ name, size }]; file.listeners.change(); },
    };
}

test('switching account types changes the guide and clears an uploaded file', () => {
    const ui = setup();
    assert.equal(ui.guides[0].hidden, false);
    assert.equal(ui.guides[1].hidden, true);
    ui.upload('students.csv');
    assert.equal(ui.filename.textContent, 'students.csv');
    ui.select('faculty');
    assert.equal(ui.file.files.length, 0);
    assert.equal(ui.guides[0].hidden, true);
    assert.equal(ui.guides[1].hidden, false);
    assert.equal(ui.filename.textContent, 'Upload Faculty Excel or CSV File');
    assert.equal(ui.submit.textContent, 'Import Faculty');
    ui.upload('faculty.csv');
    ui.select('student');
    assert.equal(ui.file.files.length, 0);
    assert.equal(ui.submit.textContent, 'Import Students');
});

test('a restored faculty selection shows the faculty guide on load', () => {
    const ui = setup('faculty');
    assert.equal(ui.guides[0].hidden, true);
    assert.equal(ui.guides[1].hidden, false);
    assert.equal(ui.submit.textContent, 'Import Faculty');
});

test('an oversized file blocks submission and changing types clears the error', () => {
    const ui = setup();
    ui.upload('too-large.csv', 10 * 1024 * 1024 + 1);
    assert.equal(ui.file.validityMessage, 'Maximum file size is 10MB.');
    ui.select('faculty');
    assert.equal(ui.file.validityMessage, '');
    ui.upload('faculty.csv', 10 * 1024 * 1024);
    assert.equal(ui.file.validityMessage, '');
});
