import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup(type = 'dean', serverErrors = {}) {
    let focused;
    class Field {
        constructor(name, value = '', required = true) {
            Object.assign(this, {name, value, required, disabled: false, events: {}, attrs: {}, customMessage: ''});
        }
        addEventListener(type, callback) { (this.events[type] ??= []).push(callback); }
        emit(type) {
            const event = {target: this, preventDefault() { this.prevented = true; }};
            this.events[type]?.forEach(callback => callback(event));
            return event;
        }
        setAttribute(name, value) { this.attrs[name] = value; }
        getAttribute(name) { return this.attrs[name]; }
        setCustomValidity(message) { this.customMessage = message; }
        focus() { focused = this; }
        get validity() { return {valid: !this.customMessage && !(this.required && !this.value)}; }
        get validationMessage() { return this.validity.valid ? '' : 'Invalid value.'; }
    }
    const fields = {
        account_type: new Field('account_type', type), department: new Field('department', 'College of Computer Studies'),
        firstname: new Field('firstname', 'Ana'), lastname: new Field('lastname', 'Cruz'),
        middlename: new Field('middlename', '', false), dean_id: new Field('dean_id', '000001'),
        email: new Field('email', 'ana@gmail.com'),
    };
    fields.account_type.tagName = fields.department.tagName = 'SELECT';
    const errors = Object.fromEntries(Object.keys(fields).map(name => [name, {id: `error-${name}`, textContent: serverErrors[name] || ''}]));
    const login = {}, password = {};
    const form = Object.assign(new Field(), {
        elements: fields,
        querySelectorAll: () => Object.values(fields),
        querySelector: selector => selector === '[data-department-login]' ? login
            : selector === '[data-department-password]' ? password
            : errors[selector.match(/"(.+)"/)[1]],
    });
    vm.runInNewContext(readFileSync(new URL('../../public/js/department-account-form.js', import.meta.url), 'utf8'), {
        document: {querySelectorAll: () => [form]},
    });
    return {fields, errors, login, password, form, get focused() { return focused; }};
}

for (const [type, prefix] of [['dean', 'DEAN-'], ['coordinator', 'RC-']]) {
    test(`${type} preserves six digits and generates prefixed access details`, () => {
        const ui = setup(type);
        assert.equal(ui.fields.dean_id.value, '000001');
        assert.equal(ui.login.value, `${prefix}000001`);
        assert.equal(ui.password.value, `${prefix}000001_Ana`);
        assert.equal(ui.form.emit('submit').prevented, undefined);
        ui.fields.dean_id.value = '087987';
        ui.fields.dean_id.emit('input');
        assert.equal(ui.login.value, `${prefix}087987`);
        assert.equal(ui.password.value, `${prefix}087987_Ana`);
    });

    test(`${type} updates the password from the first three first-name letters`, () => {
        const ui = setup(type);
        ui.fields.dean_id.value = '000000';
        ui.fields.dean_id.emit('input');
        for (const [firstname, suffix] of [['Serg', 'Ser'], ['sERG', 'Ser'], ['  Mary Ann  ', 'Mar'], ['Al', 'Al'], ['Q', 'Q'], ['', ''], ['   ', '']]) {
            ui.fields.firstname.value = firstname;
            ui.fields.firstname.emit('input');
            assert.equal(ui.login.value, `${prefix}000000`);
            assert.equal(ui.password.value, suffix ? `${prefix}000000_${suffix}` : '', firstname);
        }
    });

    test(`${type} rejects invalid IDs and clears stale access previews`, () => {
        const ui = setup(type);
        for (const value of ['', '12345', '1234567', '12a456', `${prefix}000001`, '123-56']) {
            ui.fields.dean_id.value = value;
            ui.fields.dean_id.emit('input');
            assert.equal(ui.fields.dean_id.getAttribute('aria-invalid'), 'true', value);
            assert.ok(ui.errors.dean_id.textContent, value);
            assert.equal(ui.login.value, '');
            assert.equal(ui.password.value, '');
            assert.equal(ui.form.emit('submit').prevented, true);
        }
        ui.fields.dean_id.value = '000001';
        ui.fields.dean_id.emit('input');
        assert.equal(ui.errors.dean_id.textContent, '');
        assert.equal(ui.fields.dean_id.getAttribute('aria-invalid'), 'false');
    });

    test(`${type} highlights every empty required field and focuses the first`, () => {
        const ui = setup(type);
        for (const field of Object.values(ui.fields)) if (field.required) field.value = '';
        assert.equal(ui.form.emit('submit').prevented, true);
        for (const [name, field] of Object.entries(ui.fields)) {
            assert.equal(field.getAttribute('aria-invalid'), field.required ? 'true' : 'false', name);
            assert.equal(ui.errors[name].textContent, !field.required ? '' : name === 'email' ? 'Email address is required.' : 'This field is required.', name);
        }
        assert.equal(ui.focused, ui.fields.account_type);
    });

    test(`${type} validates Gmail syntax and domain then normalizes valid email`, () => {
        const ui = setup(type);
        for (const value of ['', 'invalid', 'name@gmail', 'name..test@gmail.com', 'name@yahoo.com', 'name@gmail.com.example.com', `${'a'.repeat(65)}@gmail.com`]) {
            ui.fields.email.value = value;
            ui.fields.email.emit('blur');
            assert.equal(ui.fields.email.getAttribute('aria-invalid'), 'true', value);
            assert.ok(ui.errors.email.textContent, value);
            assert.equal(ui.form.emit('submit').prevented, true);
        }
        ui.fields.email.value = '  Ana.Cruz+test@GMAIL.COM  ';
        assert.equal(ui.form.emit('submit').prevented, undefined);
        assert.equal(ui.fields.email.value, 'ana.cruz+test@gmail.com');
        assert.equal(ui.errors.email.textContent, '');
    });

    test(`${type} retains server validation errors until the field is corrected`, () => {
        const ui = setup(type, {dean_id: 'This ID is already registered.'});
        assert.equal(ui.fields.dean_id.getAttribute('aria-invalid'), 'true');
        assert.equal(ui.fields.dean_id.getAttribute('aria-describedby'), 'error-dean_id');
        assert.equal(ui.errors.dean_id.textContent, 'This ID is already registered.');
        ui.fields.dean_id.value = '000002';
        ui.fields.dean_id.emit('input');
        assert.equal(ui.fields.dean_id.getAttribute('aria-invalid'), 'false');
        assert.equal(ui.errors.dean_id.textContent, '');
    });
}

test('changing account type regenerates the login ID and password', () => {
    const ui = setup();
    ui.fields.account_type.value = 'coordinator';
    ui.fields.account_type.emit('change');
    assert.equal(ui.login.value, 'RC-000001');
    assert.equal(ui.password.value, 'RC-000001_Ana');
    ui.fields.account_type.value = 'dean';
    ui.fields.account_type.emit('change');
    assert.equal(ui.login.value, 'DEAN-000001');
    assert.equal(ui.password.value, 'DEAN-000001_Ana');
});
