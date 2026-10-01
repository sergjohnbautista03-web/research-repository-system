(() => {
    'use strict';

    const warning = 'Content Protected by PHILCST.';
    function screenshotReason(event) {
        const key = String(event.key || '').toLowerCase();
        const code = String(event.code || '').toLowerCase();
        if (['printscreen', 'snapshot'].includes(key) || ['printscreen', 'snapshot'].includes(code)
            || event.keyCode === 44) return 'printscreen';
        if (event.metaKey && event.shiftKey) {
            const platform = String(window.navigator?.userAgentData?.platform
                || window.navigator?.platform || '').toLowerCase();
            if ((!platform || platform.includes('win')) && (key === 's' || code === 'keys')) {
                return 'windows_snipping_shortcut';
            }
            if ((!platform || platform.includes('mac'))
                && (['3', '4', '5'].includes(key) || ['digit3', 'digit4', 'digit5'].includes(code))) {
                return 'macos_screenshot_shortcut';
            }
        }
        return null;
    }

    const guard = document.getElementById('screenGuard');
    if (guard) {
        const shell = document.querySelector('.viewer-shell');
        const title = document.getElementById('screenGuardTitle');
        const description = document.getElementById('screenGuardDescription');
        const resume = document.getElementById('resumeViewing');
        let restricted = false;
        let inactive = document.hidden;
        let disposed = false;
        const listeners = [];
        const embedded = window.parent !== window;

        function listen(target, type, handler) {
            target.addEventListener(type, handler, true);
            listeners.push(() => target.removeEventListener(type, handler, true));
        }
        function log(type, details) {
            // Guard updates must succeed even when logging or the network fails.
            try { window.logCaptureAttempt?.(type, details); } catch (_) { /* best effort */ }
        }
        function refresh() {
            const hidden = restricted || inactive || document.hidden;
            shell.classList.toggle('is-obscured', hidden);
            guard.classList.toggle('is-visible', hidden);
            guard.setAttribute('aria-hidden', hidden ? 'false' : 'true');
            title.textContent = warning;
            description.textContent = restricted
                ? 'A restricted shortcut was detected. The document is hidden. Return to this viewer to resume.'
                : 'The document is hidden while this window is inactive.';
            resume.hidden = !restricted;
            resume.disabled = inactive || document.hidden;
        }
        function restrict(type, details) {
            if (disposed) return;
            restricted = true;
            refresh();
            log(type, details);
        }
        function handleKeyboard(event, screenshotsOnly = false) {
            if (disposed) return;
            const reason = screenshotReason(event);
            const key = String(event.key || '').toLowerCase();
            const actions = { p: 'print_blocked', s: 'save_blocked', u: 'source_view_blocked', c: 'copy_blocked' };
            const type = reason ? 'printscreen' : (!screenshotsOnly && event.type === 'keydown'
                && (event.ctrlKey || event.metaKey) ? actions[key] : null);
            if (!type) return;
            event.preventDefault();
            event.stopPropagation();
            restrict(type, { reason: reason || 'document_shortcut', key: event.key || '',
                code: event.code || '', phase: event.type, ctrl: !!event.ctrlKey,
                alt: !!event.altKey, shift: !!event.shiftKey, meta: !!event.metaKey });
        }
        function setInactive(value, type) {
            if (disposed) return;
            inactive = value;
            refresh();
            if (value && type) log(type, { reason: type });
        }
        function windowInactive() {
            // Moving focus between an iframe and its parent is normal viewing.
            try { return document.hidden || !window.top.document.hasFocus(); }
            catch (_) { return document.hidden || !document.hasFocus(); }
        }
        listen(document, 'keydown', handleKeyboard);
        listen(document, 'keyup', handleKeyboard);
        listen(resume, 'click', () => {
            if (windowInactive()) return;
            restricted = false;
            inactive = false;
            refresh();
        });
        ['contextmenu', 'dragstart', 'drop', 'copy', 'cut', 'paste', 'selectstart'].forEach(type => {
            listen(document, type, event => {
                event.preventDefault();
                if (type === 'contextmenu') log('context_menu_blocked', { reason: 'right_click' });
            });
        });
        listen(document, 'beforeinput', event => {
            if (['insertFromPaste', 'insertFromDrop'].includes(event.inputType)) event.preventDefault();
        });
        listen(window, 'beforeprint', () => restrict('print_blocked', { reason: 'beforeprint' }));
        listen(document, 'visibilitychange', () => setInactive(document.hidden, 'tab_hidden'));
        listen(window, 'blur', () => setInactive(windowInactive(), 'window_blur'));
        listen(window, 'focus', () => setInactive(windowInactive()));
        // Also observe top-level focus: OS capture tools may consume their shortcut.
        if (embedded) {
            try {
                listen(window.top, 'blur', () => setInactive(windowInactive(), 'window_blur'));
                listen(window.top, 'focus', () => setInactive(windowInactive()));
            } catch (_) { /* cross-origin embedding cannot share focus events */ }
        }
        const api = { handleKeyboard, setInactive, restrict,
            dispose() {
                disposed = true;
                listeners.splice(0).forEach(remove => remove());
                window.logCaptureAttempt = () => {};
            } };
        window.PhilcstDocumentProtection = api;
        listen(window, 'pagehide', () => setInactive(true));
        listen(window, 'pageshow', () => setInactive(windowInactive()));
        inactive = windowInactive();
        refresh();
        return;
    }

    // This bridge exists only on research detail pages; it is idle until opened.
    const viewer = document.querySelector('[data-protected-document]');
    const frame = document.getElementById('pdfFrame');
    const button = document.getElementById('viewerBtn');
    if (!viewer || !frame || !button) return;
    button.setAttribute('aria-controls', viewer.id);
    button.setAttribute('aria-expanded', 'false');
    const originalButton = button.innerHTML;
    let opened = false;
    function protection() {
        try { return opened ? frame.contentWindow?.PhilcstDocumentProtection : null; }
        catch (_) { return null; }
    }
    function capture(event) {
        // Leave copy/save and all outside-viewer interactions alone.
        if (opened && screenshotReason(event)) protection()?.handleKeyboard(event, true);
    }
    function close() {
        opened = false;
        document.removeEventListener('keydown', capture, true);
        document.removeEventListener('keyup', capture, true);
        // Dispose before navigating so no hidden viewer logs or handlers remain.
        try { frame.contentWindow?.PhilcstDocumentProtection?.dispose(); } catch (_) { /* same-origin only */ }
        frame.setAttribute('src', 'about:blank');
        viewer.style.display = 'none';
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = originalButton;
    }
    window.toggleProtectedDocument = () => {
        if (opened) { close(); return; }
        const url = new URL(frame.dataset.src, window.location.href);
        if (url.origin !== window.location.origin) return;
        opened = true;
        viewer.style.display = 'block';
        frame.setAttribute('src', url.href);
        button.setAttribute('aria-expanded', 'true');
        button.textContent = 'Close Document';
        document.addEventListener('keydown', capture, true);
        document.addEventListener('keyup', capture, true);
        viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    window.addEventListener('pagehide', close);
})();
