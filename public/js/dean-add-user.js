(() => {

const memberType = document.getElementById('member-type');
if (!memberType) return;
const form = document.getElementById('dean-add-user-form');
form.noValidate = true;
const fields = Array.from(form.querySelectorAll('input[name]:not([type="hidden"]), select[name]'));
const nameLabels = {firstname: 'First name', middlename: 'Middle name', lastname: 'Last name'};
fields.forEach(field => {
    const error = document.getElementById(`member-error-${field.name}`);
    field.setAttribute('aria-describedby', error.id);
    if (error.textContent.trim()) field.setAttribute('aria-invalid', 'true');
    field.addEventListener('input', () => {
        validateField(field);
        if (field.name === 'firstname' || field.name === 'student_id') updateLoginDetails();
    });
    field.addEventListener('blur', () => {
        if (field.name === 'email') field.value = field.value.trim().toLowerCase();
        validateField(field);
    });
});
function emailMessage(value) {
    value = value.trim().toLowerCase();
    if (!value) return 'Email address is required.';
    const syntax = /^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/i;
    if (value.length > 255 || value.split('@')[0].length > 64 || !syntax.test(value)) return 'Please enter a valid Gmail address.';
    if (!value.endsWith('@gmail.com')) return 'Only Gmail addresses (@gmail.com) are allowed.';
    return '';
}
function validateField(field) {
    field.setCustomValidity('');
    let message = '';
    if (!field.disabled) {
        if (field.name === 'email') message = emailMessage(field.value);
        else if (field.required && !field.value.trim()) message = 'This field is required.';
        else if (nameLabels[field.name] && field.value && !/^[a-zA-Z\s]+$/.test(field.value)) message = `${nameLabels[field.name]} must contain letters only.`;
        else if (field.name === 'student_id' && field.value && !/^[0-9]+$/.test(field.value)) message = 'Enter numbers only.';
        else if (field.name === 'student_id' && memberType.value === 'faculty' && field.value.length !== 6) message = 'Faculty ID must contain exactly 6 digits, without FAC-.';
        else if (field.name === 'student_id' && memberType.value === 'student' && field.value.length > 8) message = 'Student ID may contain up to 8 digits.';
        else if (!field.validity.valid) message = field.validationMessage;
    }
    field.setCustomValidity(message);
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
    document.getElementById(`member-error-${field.name}`).textContent = message;
    return !message;
}
form.addEventListener('invalid', event => validateField(event.target), true);
form.addEventListener('submit', event => {
    form.elements.email.value = form.elements.email.value.trim().toLowerCase();
    const valid = fields.map(validateField).every(Boolean);
    if (!valid) {
        event.preventDefault();
        fields.find(field => field.getAttribute('aria-invalid') === 'true')?.focus();
    }
});
function updateMemberFields(event) {
    const student = memberType.value === 'student';
    document.getElementById('member-id-label').textContent = student ? 'Student ID' : 'Faculty ID';
    const id = form.elements.student_id;
    id.inputMode = 'numeric';
    id.pattern = student ? '[0-9]+' : '[0-9]{6}';
    id.minLength = student ? 1 : 6;
    id.maxLength = student ? 8 : 6;
    id.placeholder = student ? 'e.g. 37893' : 'e.g. 087987';
    document.getElementById('member-year').hidden = !student;
    const year = document.getElementById('member-year').querySelector('select');
    year.disabled = !student;
    year.required = student;
    if (event) {
        validateField(id);
        validateField(year);
        updateLoginDetails();
    }
}
memberType.addEventListener('change',updateMemberFields);updateMemberFields();
function updateLoginDetails() {
    const value = form.elements.student_id.value;
    const valid = /^[0-9]+$/.test(value) && (memberType.value === 'student' ? value.length <= 8 : value.length === 6);
    const id = valid ? (memberType.value === 'student' ? value.padStart(8, '0') : `FAC-${value}`) : '';
    const letters = form.elements.firstname.value.replace(/[^a-zA-Z]/g, '').slice(0, 3).toLowerCase();
    const prefix = letters ? letters[0].toUpperCase() + letters.slice(1) : '';
    document.getElementById('member-username').value = id;
    document.getElementById('member-password').value = id && prefix ? `${id}_${prefix}` : '';
}
updateLoginDetails();

const modal = document.getElementById('addUserModal');
if (!modal) return;
const trigger = document.querySelector('[data-open-add-user]');
trigger?.addEventListener('click', () => {
    modal.showModal();
});
modal.querySelectorAll('[data-close-add-user]').forEach(button => button.addEventListener('click', () => modal.close()));
modal.addEventListener('click', event => {
    const rect = modal.getBoundingClientRect();
    if (event.target === modal && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) modal.close();
});
if (modal.dataset.reopen === 'true') modal.showModal();
})();
