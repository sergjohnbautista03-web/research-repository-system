(() => {
    document.querySelectorAll('[data-department-account-form]').forEach(form => {
        form.noValidate = true;
        const fields = Array.from(form.querySelectorAll('input[name]:not([type="hidden"]), select[name]'));
        const type = form.elements.account_type;
        const id = form.elements.dean_id;
        const nameLabels = {firstname: 'First name', middlename: 'Middle name', lastname: 'Last name'};
        const prefix = () => type.value === 'coordinator' ? 'RC-' : 'DEAN-';

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
                else if (field.name === 'dean_id' && !/^[0-9]+$/.test(field.value)) message = 'Enter numbers only.';
                else if (field.name === 'dean_id' && field.value.length !== 6) message = `${type.value === 'coordinator' ? 'Coordinator' : 'Dean'} ID must contain exactly 6 digits, without ${prefix()}.`;
                else if (!field.validity.valid) message = field.validationMessage;
            }
            field.setCustomValidity(message);
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
            form.querySelector(`[data-department-error="${field.name}"]`).textContent = message;
            return !message;
        }

        function updateAccess() {
            const identifier = /^[0-9]{6}$/.test(id.value) ? prefix() + id.value : '';
            const letters = form.elements.firstname.value.replace(/[^a-zA-Z]/g, '').slice(0, 3).toLowerCase();
            const suffix = letters.charAt(0).toUpperCase() + letters.slice(1);
            form.querySelector('[data-department-login]').value = identifier;
            form.querySelector('[data-department-password]').value = identifier && suffix ? `${identifier}_${suffix}` : '';
        }

        fields.forEach(field => {
            const error = form.querySelector(`[data-department-error="${field.name}"]`);
            field.setAttribute('aria-describedby', error.id);
            if (error.textContent.trim()) field.setAttribute('aria-invalid', 'true');
            field.addEventListener('input', () => {
                validateField(field);
                if (field === id || field === form.elements.firstname) updateAccess();
            });
            field.addEventListener('blur', () => {
                if (field.name === 'email') field.value = field.value.trim().toLowerCase();
                validateField(field);
            });
            if (field.tagName === 'SELECT') field.addEventListener('change', () => validateField(field));
        });
        type.addEventListener('change', () => {
            if (id.value || id.getAttribute('aria-invalid') === 'true') validateField(id);
            updateAccess();
        });
        form.addEventListener('invalid', event => validateField(event.target), true);
        form.addEventListener('submit', event => {
            form.elements.email.value = form.elements.email.value.trim().toLowerCase();
            const valid = fields.map(validateField).every(Boolean);
            if (!valid) {
                event.preventDefault();
                fields.find(field => field.getAttribute('aria-invalid') === 'true')?.focus();
            }
        });
        updateAccess();
    });
})();
