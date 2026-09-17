/* =========================================================
   UBE REPOSITORY — Main JavaScript
   ========================================================= */

// ── Flash message auto-dismiss ──────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity .4s';
            setTimeout(() => flash.remove(), 400);
        }, 4500);
    }
});

// ── Toggle password visibility ──────────────────────────
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;

    if (input.type === 'password') {
        // Show password — open eye
        input.type = 'text';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    } else {
        // Hide password — closed eye (slash)
        input.type = 'password';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    }
}

// ── Pin / Unpin research ────────────────────────────────
function togglePin(id, btn) {
    fetch(`/pin/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.pinned) {
            btn.classList.add('pinned');
            btn.textContent = 'Pinned';
        } else {
            btn.classList.remove('pinned');
            btn.textContent = 'Pin';
        }
    });
}

// ── File upload display ─────────────────────────────────
function updateFileName(input) {
    const nameEl = document.getElementById('fileName');
    if (!nameEl) return;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        nameEl.textContent = '✓ ' + file.name;
        nameEl.style.color = 'var(--green)';
    } else {
        nameEl.textContent = 'Click or drag file here';
        nameEl.style.color = '';
    }
}

// ── Drag & drop for file upload ─────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const area = document.getElementById('fileUploadArea');
    if (!area) return;

    area.addEventListener('dragover', (e) => {
        e.preventDefault();
        area.style.borderColor = 'var(--purple-main)';
        area.style.background  = 'var(--purple-pale)';
    });

    area.addEventListener('dragleave', () => {
        area.style.borderColor = '';
        area.style.background  = '';
    });

    area.addEventListener('drop', (e) => {
        e.preventDefault();
        area.style.borderColor = '';
        area.style.background  = '';
        const fileInput = area.querySelector('input[type="file"]');
        if (fileInput && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFileName(fileInput);
        }
    });
});

// ── Admin confirm actions ───────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
// ── Show toggle button on password input ─────────────────
// Always show the eye icon (toggle-pw) for password fields
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-wrap input[type="password"]').forEach(input => {
        const btn = input.parentElement.querySelector('.toggle-pw');
        if (btn) {
            btn.style.display = 'flex';
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ca-password-wrap input[type="password"]').forEach(input => {
        const btn = input.parentElement.querySelector('.ca-toggle-pw');
        if (!btn) return;
        input.addEventListener('input', function () {
            btn.style.display = this.value.length > 0 ? 'flex' : 'none';
        });
    });
});

// Public login modal
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-login-modal]');
    if (!modal) return;

    const firstInput = modal.querySelector('input[name="login"]');
    const openButtons = document.querySelectorAll('[data-login-modal-trigger]');
    const closeButtons = modal.querySelectorAll('[data-login-modal-close]');

    function openLoginModal() {
        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
        window.setTimeout(() => firstInput?.focus(), 60);
    }

    function closeLoginModal() {
        modal.classList.remove('is-open');
        document.body.classList.remove('modal-open');
    }

    openButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openLoginModal();
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', closeLoginModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeLoginModal();
        }
    });

    if (modal.dataset.openOnLoad === 'true') {
        openLoginModal();
    }
});
// Site-wide content protection. This deters browser-level text selection,
// manual clipboard actions, drag extraction, print, and common capture shortcuts.
(function () {
    const copyAllowedSelector = '[data-security-copy-allowed="true"]';
    const editableSelector = 'input, textarea, select, [contenteditable="true"], [contenteditable=""]';
    let guardTimer = null;

    function normalizedKey(event) {
        return String(event.key || '').toLowerCase();
    }

    function normalizedCode(event) {
        return String(event.code || '').toLowerCase();
    }

    function platformText() {
        return ((navigator.userAgent || '') + ' ' + (navigator.platform || '')).toLowerCase();
    }

    function closestElement(target, selector) {
        return target instanceof Element ? target.closest(selector) : null;
    }

    function isEditableTarget(target) {
        return Boolean(closestElement(target, editableSelector));
    }

    function isFileDrop(event) {
        const transfer = event.dataTransfer;

        return Boolean(transfer && transfer.files && transfer.files.length > 0);
    }

    function isUploadDropTarget(target) {
        return Boolean(closestElement(target, 'input[type="file"], #fileUploadArea, .file-upload-area, [data-file-upload]'));
    }

    function isCopyAllowed(event) {
        if (window.__ubeAllowProgrammaticCopy === true) {
            return true;
        }

        return Boolean(closestElement(event.target, copyAllowedSelector));
    }

    function ensureGuard() {
        let guard = document.querySelector('.site-security-guard');

        if (guard || !document.body) {
            return guard;
        }

        guard = document.createElement('div');
        guard.className = 'site-security-guard';
        guard.setAttribute('aria-live', 'polite');
        guard.setAttribute('aria-hidden', 'true');
        guard.innerHTML = '<div class="site-security-guard-card"><strong>Content protected</strong><span></span></div>';
        document.body.appendChild(guard);

        return guard;
    }

    function showGuard(message, duration) {
        const guard = ensureGuard();

        if (!guard) {
            return;
        }

        const text = guard.querySelector('span');
        if (text) {
            text.textContent = message || 'Copying, pasting, printing, and screen capture shortcuts are restricted on this website.';
        }

        window.clearTimeout(guardTimer);
        guard.classList.add('is-visible');
        guard.setAttribute('aria-hidden', 'false');

        guardTimer = window.setTimeout(function () {
            guard.classList.remove('is-visible');
            guard.setAttribute('aria-hidden', 'true');
        }, duration || 1800);
    }

    function blockEvent(event, message) {
        event.preventDefault();
        event.stopPropagation();
        showGuard(message);
        return false;
    }

    function isPrintScreenEvent(event) {
        const key = normalizedKey(event);
        const code = normalizedCode(event);

        return key === 'printscreen'
            || code === 'printscreen'
            || key === 'snapshot'
            || code === 'snapshot';
    }

    function isWindowsSnippingShortcut(event, key) {
        return platformText().includes('win')
            && event.metaKey
            && event.shiftKey
            && key === 's';
    }

    function isMacScreenshotShortcut(event, key) {
        const platform = platformText();

        return (platform.includes('mac') || platform.includes('iphone') || platform.includes('ipad'))
            && event.metaKey
            && event.shiftKey
            && ['3', '4', '5'].includes(key);
    }

    function handleCaptureShortcut(event) {
        const key = normalizedKey(event);

        if (!isPrintScreenEvent(event) && !isWindowsSnippingShortcut(event, key) && !isMacScreenshotShortcut(event, key)) {
            return false;
        }

        blockEvent(event, 'Screen capture shortcuts are restricted on this website.');

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText('Screen capture is restricted on this website.').catch(function () {});
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', ensureGuard);

    document.addEventListener('contextmenu', function (event) {
        return blockEvent(event, 'Right-click is disabled on this website.');
    }, true);

    document.addEventListener('selectstart', function (event) {
        if (isEditableTarget(event.target)) {
            return true;
        }

        return blockEvent(event, 'Text highlighting is disabled on this website.');
    }, true);

    document.addEventListener('dragstart', function (event) {
        return blockEvent(event, 'Dragging content from this website is disabled.');
    }, true);

    document.addEventListener('drop', function (event) {
        if (isFileDrop(event) && isUploadDropTarget(event.target)) {
            return true;
        }

        return blockEvent(event, 'Dropping or pasting external content is disabled.');
    }, true);

    document.addEventListener('copy', function (event) {
        if (isCopyAllowed(event)) {
            return true;
        }

        return blockEvent(event, 'Copying text is disabled on this website.');
    }, true);

    document.addEventListener('cut', function (event) {
        return blockEvent(event, 'Cutting text is disabled on this website.');
    }, true);

    document.addEventListener('paste', function (event) {
        return blockEvent(event, 'Pasting text is disabled on this website.');
    }, true);

    document.addEventListener('beforeinput', function (event) {
        if (event.inputType === 'insertFromPaste' || event.inputType === 'insertFromDrop') {
            return blockEvent(event, 'Pasting text is disabled on this website.');
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (handleCaptureShortcut(event)) {
            return false;
        }

        const key = normalizedKey(event);
        const blockedShortcut = (event.ctrlKey || event.metaKey)
            && ['a', 'c', 'p', 's', 'u', 'v', 'x'].includes(key);
        const blockedDeveloperShortcut = event.key === 'F12'
            || ((event.ctrlKey || event.metaKey) && event.shiftKey && ['c', 'i', 'j'].includes(key));

        if (blockedShortcut || blockedDeveloperShortcut) {
            return blockEvent(event, 'This shortcut is restricted on this website.');
        }
    }, true);

    document.addEventListener('keyup', function (event) {
        handleCaptureShortcut(event);
    }, true);

    window.addEventListener('beforeprint', function () {
        document.documentElement.classList.add('site-security-obscured');
        showGuard('Printing is disabled on this website.', 5000);
    });

    window.addEventListener('afterprint', function () {
        document.documentElement.classList.remove('site-security-obscured');
    });

    document.addEventListener('visibilitychange', function () {
        document.documentElement.classList.toggle('site-security-obscured', document.hidden);
    });

    window.addEventListener('blur', function () {
        document.documentElement.classList.add('site-security-obscured');
    });

    window.addEventListener('focus', function () {
        window.setTimeout(function () {
            document.documentElement.classList.remove('site-security-obscured');
        }, 350);
    });
})();
