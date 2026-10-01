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
    field.addEventListener('blur', () => validateField(field));
});
function validateField(field) {
    field.setCustomValidity('');
    let message = '';
    if (!field.disabled) {
        if (field.required && !field.value.trim()) message = 'This field is required.';
        else if (nameLabels[field.name] && field.value && !/^[a-zA-Z\s]+$/.test(field.value)) message = `${nameLabels[field.name]} must contain letters only.`;
        else if (field.name === 'student_id' && field.value && !/^[A-Za-z0-9-]+$/.test(field.value)) message = 'Student or Faculty ID may only contain letters, numbers, and hyphens.';
        else if (field.name === 'email' && field.validity.typeMismatch) message = 'Enter a valid email address.';
        else if (!field.validity.valid) message = field.validationMessage;
    }
    field.setCustomValidity(message);
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
    document.getElementById(`member-error-${field.name}`).textContent = message;
    return !message;
}
form.addEventListener('invalid', event => validateField(event.target), true);
form.addEventListener('submit', event => {
    const valid = fields.map(validateField).every(Boolean);
    if (!valid) {
        event.preventDefault();
        fields.find(field => field.getAttribute('aria-invalid') === 'true')?.focus();
    }
});
function updateMemberFields(){const student=memberType.value==='student';document.getElementById('member-id-label').textContent=student?'Student ID':'Faculty ID';document.getElementById('member-year').hidden=!student;const year=document.getElementById('member-year').querySelector('select');year.disabled=!student;year.required=student;}
memberType.addEventListener('change',updateMemberFields);updateMemberFields();
function updateLoginDetails() {
    const id = form.elements.student_id.value.trim();
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
