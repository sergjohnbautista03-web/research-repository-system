import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup({ modal = false, serverErrors = {} } = {}) {
    let focused;
    class Field {
        constructor(name, value = '', required = true) {
            Object.assign(this, { name, value, required, disabled: false, readOnly: false, events: {}, attrs: {}, customMessage: '' });
        }
        addEventListener(type, callback) { this.events[type] = callback; }
        emit(type) {
            const event = { target: this, preventDefault() { this.prevented = true; } };
            this.events[type]?.(event);
            return event;
        }
        setAttribute(name, value) { this.attrs[name] = value; }
        removeAttribute(name) { delete this.attrs[name]; }
        getAttribute(name) { return this.attrs[name]; }
        setCustomValidity(message) { this.customMessage = message; }
        focus() { focused = this; }
        get validity() {
            const typeMismatch = this.name === 'email' && this.value && !this.value.includes('@');
            return { typeMismatch, valid: this.disabled || (!this.customMessage && !(this.required && !this.value) && !typeMismatch) };
        }
        get validationMessage() { return this.customMessage || (this.validity.valid ? '' : 'Invalid value.'); }
    }
    const fields = {
        member_type: new Field('member_type', 'student'), student_id: new Field('student_id', '37893'),
        firstname: new Field('firstname', 'Ana'), lastname: new Field('lastname', 'Student'),
        middlename: new Field('middlename', '', false), year_level: new Field('year_level', '1'),
        program: new Field('program', 'Information Technology'), email: new Field('email', 'ana@gmail.com'),
    };
    const form = Object.assign(new Field(), {
        elements: fields, querySelectorAll: () => Object.values(fields),
    });
    const trigger = new Field();
    const dialog = Object.assign(new Field(), {
        dataset: {}, open: false, showModal() { this.open = true; }, querySelectorAll: () => [],
    });
    const nodes = {
        'member-type': fields.member_type, 'dean-add-user-form': form,
        'member-id-label': {}, 'member-year': { querySelector: () => fields.year_level },
        'member-username': {}, 'member-password': {},
        ...(modal ? { addUserModal: dialog } : {}),
    };
    for (const name of Object.keys(fields)) {
        const id = `member-error-${name}`;
        nodes[id] = { id, textContent: serverErrors[name] || '' };
    }
    vm.runInNewContext(readFileSync(new URL('../../public/js/dean-add-user.js', import.meta.url), 'utf8'), {
        document: { getElementById: id => nodes[id] || null, querySelector: () => trigger },
    });
    return { fields, form, nodes, trigger, dialog, get focused() { return focused; } };
}

test('editable Student numbers are padded in the login preview without changing the entered number', () => {
    const ui = setup();
    assert.equal(ui.fields.student_id.readOnly, false);
    assert.equal(ui.fields.student_id.value, '37893');
    assert.equal(ui.fields.student_id.maxLength, 8);
    assert.equal(ui.nodes['member-username'].value, '00037893');
    assert.equal(ui.nodes['member-password'].value, '00037893_Ana');
    assert.equal(ui.form.emit('submit').prevented, undefined);
    ui.fields.student_id.value = '00000123';
    ui.fields.student_id.emit('input');
    assert.equal(ui.nodes['member-username'].value, '00000123');
});

test('Faculty numbers receive only the FAC prefix and member type changes update the preview', () => {
    const ui = setup();
    ui.fields.member_type.value = 'faculty';
    ui.fields.member_type.emit('change');
    ui.fields.student_id.value = '087987';
    ui.fields.student_id.emit('input');
    assert.equal(ui.nodes['member-username'].value, 'FAC-087987');
    assert.equal(ui.nodes['member-password'].value, 'FAC-087987_Ana');
    assert.equal(ui.fields.student_id.minLength, 6);
    assert.equal(ui.fields.student_id.maxLength, 6);
    assert.equal(ui.fields.student_id.inputMode, 'numeric');
    assert.equal(ui.fields.year_level.disabled, true);
    assert.equal(ui.form.emit('submit').prevented, undefined);
    ui.fields.member_type.value = 'student';
    ui.fields.member_type.emit('change');
    assert.equal(ui.nodes['member-username'].value, '00087987');
    assert.equal(ui.fields.student_id.value, '087987');
    assert.equal(ui.fields.year_level.disabled, false);
});

test('invalid numbers block submission and clear the login preview', () => {
    const ui = setup();
    for (const type of ['student', 'faculty']) {
        ui.fields.member_type.value = type;
        ui.fields.member_type.emit('change');
        for (const number of ['', 'abc123', '123-45', '+37893', '12.34', 'FAC-87987', '123 456']) {
            ui.fields.student_id.value = number;
            ui.fields.student_id.emit('input');
            assert.equal(ui.form.emit('submit').prevented, true);
            assert.equal(ui.fields.student_id.getAttribute('aria-invalid'), 'true');
            assert.equal(ui.nodes['member-username'].value, '');
            assert.equal(ui.focused, ui.fields.student_id);
        }
    }
    ui.fields.member_type.value = 'student';
    ui.fields.member_type.emit('change');
    ui.fields.student_id.value = '123456789';
    ui.fields.student_id.emit('input');
    assert.equal(ui.form.emit('submit').prevented, true);
    ui.fields.student_id.value = '37893';
    ui.fields.student_id.emit('input');
    assert.equal(ui.form.emit('submit').prevented, undefined);
    ui.fields.member_type.value = 'faculty';
    ui.fields.member_type.emit('change');
    for (const number of ['87987', '1234567']) {
        ui.fields.student_id.value = number;
        ui.fields.student_id.emit('input');
        assert.equal(ui.form.emit('submit').prevented, true);
        assert.equal(ui.nodes['member-username'].value, '');
    }
});

test('the modal keeps the typed ID when reopened and existing field errors remain accessible', () => {
    const ui = setup({ modal: true, serverErrors: { firstname: 'First name is required.' } });
    ui.trigger.emit('click');
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.fields.student_id.value, '37893');
    assert.equal(ui.fields.firstname.getAttribute('aria-invalid'), 'true');
    assert.equal(ui.fields.firstname.getAttribute('aria-describedby'), 'member-error-firstname');
    ui.fields.firstname.value = 'SERGIO';
    ui.fields.firstname.emit('input');
    assert.equal(ui.nodes['member-error-firstname'].textContent, '');
    assert.equal(ui.nodes['member-password'].value, '00037893_Ser');
});

test('email validation uses the requested inline messages and rejects incomplete or other-domain addresses', () => {
    const ui = setup();
    for (const [value, message] of [
        ['', 'Email address is required.'], ['   ', 'Email address is required.'],
        ['roseannbautista@gmail', 'Please enter a valid Gmail address.'],
        ['roseann@', 'Please enter a valid Gmail address.'],
        ['roseann@gmail.', 'Please enter a valid Gmail address.'],
        ['rose..ann@gmail.com', 'Please enter a valid Gmail address.'],
        ['rose ann@gmail.com', 'Please enter a valid Gmail address.'],
        ['roseann@yahoo.com', 'Only Gmail addresses (@gmail.com) are allowed.'],
        ['roseann@gmail.com.evil', 'Only Gmail addresses (@gmail.com) are allowed.'],
    ]) {
        ui.fields.email.value = value;
        ui.fields.email.emit('input');
        assert.equal(ui.nodes['member-error-email'].textContent, message);
        assert.equal(ui.fields.email.getAttribute('aria-invalid'), 'true');
        assert.equal(ui.form.emit('submit').prevented, true);
        assert.equal(ui.focused, ui.fields.email);
    }
    ui.fields.email.value = 'roseannbautista@gmail.com';
    ui.fields.email.emit('input');
    assert.equal(ui.nodes['member-error-email'].textContent, '');
    assert.equal(ui.form.emit('submit').prevented, undefined);
});

test('email is trimmed and lowercased on blur and submission and server duplicate errors appear inline', () => {
    const message = 'This email address is already registered.';
    const ui = setup({ serverErrors: { email: message } });
    assert.equal(ui.nodes['member-error-email'].textContent, message);
    assert.equal(ui.fields.email.getAttribute('aria-describedby'), 'member-error-email');
    ui.fields.email.value = '  RoseAnn+Student@GMAIL.COM  ';
    ui.fields.email.emit('blur');
    assert.equal(ui.fields.email.value, 'roseann+student@gmail.com');
    assert.equal(ui.nodes['member-error-email'].textContent, '');
    ui.fields.email.value = '  OTHER@GMAIL.COM  ';
    assert.equal(ui.form.emit('submit').prevented, undefined);
    assert.equal(ui.fields.email.value, 'other@gmail.com');
});
