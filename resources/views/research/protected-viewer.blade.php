<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $research->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs" type="module"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #eef2ff;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }

        .viewer-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 18px;
            background: rgba(17, 24, 39, 0.94);
            color: #f8fafc;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        }

        .viewer-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .viewer-note {
            font-size: 12px;
            color: rgba(248, 250, 252, 0.8);
            text-align: right;
        }

        .viewer-shell {
            max-width: 1040px;
            margin: 0 auto;
            padding: 24px 14px 48px;
            position: relative;
            z-index: 1;
        }

        .viewer-shell.is-obscured .page-canvas,
        .viewer-shell.is-obscured .page-badge {
            visibility: hidden;
        }

        .viewer-status {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #ddd6fe;
            color: #5b21b6;
            box-shadow: 0 8px 22px rgba(59, 15, 122, 0.06);
            font-size: 13px;
            font-weight: 600;
        }

        .viewer-pages {
            display: grid;
            gap: 18px;
        }

        .page-card {
            position: relative;
            margin: 0 auto;
            width: fit-content;
            max-width: 100%;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .page-canvas {
            display: block;
            max-width: 100%;
            height: auto;
            pointer-events: none;
            -webkit-user-drag: none;
        }

        .page-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(17, 24, 39, 0.78);
            color: #f8fafc;
            font-size: 11px;
            font-weight: 700;
        }

        .screen-guard {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.96);
            color: #f8fafc;
            text-align: center;
        }

        .screen-guard.is-visible {
            display: flex;
        }

        .screen-guard-card {
            max-width: 560px;
            padding: 24px 28px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 20px;
            background: rgba(30, 41, 59, 0.86);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
        }

        .screen-guard-card strong {
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
            letter-spacing: 0.02em;
        }

        .screen-guard-card span {
            display: block;
            font-size: 13px;
            line-height: 1.7;
            color: rgba(248, 250, 252, 0.84);
        }

        @media print {
            body {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .viewer-topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .viewer-note {
                text-align: left;
            }
        }
    </style>
</head>
<body>
<div class="viewer-topbar">
    <div class="viewer-title">{{ $research->title }}</div>
    <div class="viewer-note">Authorized academic use only. Do not share, copy, sell, upload, or redistribute this research material.</div>
</div>

<div class="viewer-shell">
    <div class="viewer-status" id="viewerStatus">Loading protected document...</div>
    <div class="viewer-pages" id="viewerPages"></div>
</div>

<div class="screen-guard" id="screenGuard" aria-live="polite" aria-hidden="true">
    <div class="screen-guard-card">
        <strong>Protected document hidden</strong>
        <span>This viewer temporarily hides the full document when screen capture, print, or app switching is detected.</span>
    </div>
</div>

<script>
    window.protectedViewerConfig = {
        logUrl: @json(route('research.capture-attempt', $research)),
        viewerScope: @json(!empty($adminMode) ? 'admin' : 'standard'),
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        loadedMessage: 'Protected document loaded. Browser PDF save, print, and screenshot shortcuts are blocked where the browser allows.',
    };

    (function() {
        const config = window.protectedViewerConfig;
        const loggedEvents = new Map();

        function shouldLogEvent(eventType) {
            const now = Date.now();
            const lastLoggedAt = loggedEvents.get(eventType) || 0;

            if (now - lastLoggedAt < 15000) {
                return false;
            }

            loggedEvents.set(eventType, now);

            return true;
        }

        function normalizeDetailValue(value) {
            if (value === null || typeof value === 'undefined') {
                return '';
            }

            if (typeof value === 'object') {
                try {
                    return JSON.stringify(value);
                } catch (error) {
                    return String(value);
                }
            }

            return String(value);
        }

        function buildBeaconPayload(payload) {
            const formData = new FormData();
            formData.append('_token', config.csrfToken);
            formData.append('event_type', payload.event_type);
            formData.append('scope', payload.scope);

            Object.entries(payload.details || {}).forEach(function(entry) {
                formData.append('details[' + entry[0] + ']', normalizeDetailValue(entry[1]));
            });

            return formData;
        }

        function sendCaptureLog(payload) {
            const canUseBeacon = navigator.sendBeacon
                && (document.visibilityState === 'hidden'
                    || payload.event_type === 'window_blur'
                    || payload.event_type === 'tab_hidden'
                    || payload.event_type === 'protected_view_opened');

            if (canUseBeacon && navigator.sendBeacon(config.logUrl, buildBeaconPayload(payload))) {
                return;
            }

            fetch(config.logUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch(() => {});
        }

        window.logCaptureAttempt = function(eventType, details = {}) {
            if (!shouldLogEvent(eventType)) {
                return;
            }

            sendCaptureLog({
                event_type: eventType,
                scope: config.viewerScope,
                details,
            });
        };
    })();
</script>

<script type="module">
    import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs';

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs';

    const signedUrl = @json($signedUrl);
    const viewerConfig = window.protectedViewerConfig || {};
    const statusEl = document.getElementById('viewerStatus');
    const pagesEl = document.getElementById('viewerPages');
    const shellEl = document.querySelector('.viewer-shell');
    const guardEl = document.getElementById('screenGuard');
    let guardTimer = null;

    function updateStatus(message) {
        statusEl.textContent = message;
    }

    function setGuardState(active, message) {
        shellEl.classList.toggle('is-obscured', active);
        guardEl.classList.toggle('is-visible', active);
        guardEl.setAttribute('aria-hidden', active ? 'false' : 'true');

        if (message) {
            updateStatus(message);
        }
    }

    function triggerGuard(message, duration = 3500) {
        window.clearTimeout(guardTimer);
        setGuardState(true, message);

        guardTimer = window.setTimeout(() => {
            setGuardState(false, viewerConfig.loadedMessage);
        }, duration);
    }

    window.protectedViewerGuard = {
        setGuardState,
        triggerGuard,
    };

    async function renderProtectedPdf() {
        try {
            const loadingTask = pdfjsLib.getDocument({
                url: signedUrl,
                disableAutoFetch: true,
                disableStream: false,
                isEvalSupported: false,
                useSystemFonts: false,
            });

            const pdf = await loadingTask.promise;
            updateStatus(viewerConfig.loadedMessage);

            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                const page = await pdf.getPage(pageNumber);
                const viewport = page.getViewport({ scale: 1.45 });

                const pageCard = document.createElement('div');
                pageCard.className = 'page-card';

                const badge = document.createElement('div');
                badge.className = 'page-badge';
                badge.textContent = 'Page ' + pageNumber;

                const canvas = document.createElement('canvas');
                canvas.className = 'page-canvas';
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                const context = canvas.getContext('2d', { alpha: false });
                await page.render({ canvasContext: context, viewport }).promise;

                pageCard.appendChild(canvas);
                pageCard.appendChild(badge);
                pagesEl.appendChild(pageCard);
            }

            getViewerLogger()('protected_view_opened', {
                page_count: pdf.numPages,
                viewer: viewerConfig.viewerScope || 'standard',
            });
        } catch (error) {
            console.error(error);
            updateStatus('Unable to render the protected document viewer right now.');
        }
    }

    renderProtectedPdf();
</script>

<script>
    function getViewerGuard() {
        return window.protectedViewerGuard;
    }

    function getViewerLogger() {
        return window.logCaptureAttempt || function () {};
    }

    function getViewerLoadedMessage() {
        return window.protectedViewerConfig?.loadedMessage
            || 'Protected document loaded. Browser PDF save, print, and screenshot shortcuts are blocked where the browser allows.';
    }

    function normalizedEventKey(e) {
        return String(e.key || '').toLowerCase();
    }

    function normalizedEventCode(e) {
        return String(e.code || '').toLowerCase();
    }

    function viewerPlatformText() {
        return ((navigator.userAgent || '') + ' ' + (navigator.platform || '')).toLowerCase();
    }

    function isPrintScreenEvent(e) {
        const key = normalizedEventKey(e);
        const code = normalizedEventCode(e);

        return key === 'printscreen'
            || code === 'printscreen'
            || key === 'snapshot'
            || code === 'snapshot';
    }

    function isWindowsSnippingShortcut(e, key) {
        return viewerPlatformText().includes('win')
            && e.metaKey
            && e.shiftKey
            && key === 's';
    }

    function isMacScreenshotShortcut(e, key) {
        const platform = viewerPlatformText();

        return (platform.includes('mac') || platform.includes('iphone') || platform.includes('ipad'))
            && e.metaKey
            && e.shiftKey
            && ['3', '4', '5'].includes(key);
    }

    function shortcutDetails(e, reason, phase) {
        return {
            key: e.key || 'unknown',
            code: e.code || 'unknown',
            reason,
            phase,
            ctrl: e.ctrlKey ? 'yes' : 'no',
            alt: e.altKey ? 'yes' : 'no',
            shift: e.shiftKey ? 'yes' : 'no',
            meta: e.metaKey ? 'yes' : 'no',
        };
    }

    function logScreenshotShortcut(e, reason, phase) {
        e.preventDefault();
        e.stopPropagation();
        getViewerGuard()?.triggerGuard('Screen capture shortcut detected. The protected document was hidden.');
        getViewerLogger()('printscreen', shortcutDetails(e, reason, phase));

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText('Protected document capture is blocked where supported.').catch(() => {});
        }
    }

    function handleScreenshotShortcut(e, phase) {
        const key = normalizedEventKey(e);

        if (isPrintScreenEvent(e)) {
            logScreenshotShortcut(e, phase === 'keyup' ? 'printscreen_keyup' : 'printscreen_keydown', phase);
            return true;
        }

        if (phase === 'keydown' && isWindowsSnippingShortcut(e, key)) {
            logScreenshotShortcut(e, 'windows_snipping_shortcut', phase);
            return true;
        }

        if (phase === 'keydown' && isMacScreenshotShortcut(e, key)) {
            logScreenshotShortcut(e, 'macos_screenshot_shortcut', phase);
            return true;
        }

        return false;
    }

    function blockProtectedAction(e) {
        e.preventDefault();
        return false;
    }

    document.addEventListener('contextmenu', blockProtectedAction);
    document.addEventListener('dragstart', blockProtectedAction);
    document.addEventListener('drop', blockProtectedAction);
    document.addEventListener('copy', blockProtectedAction);
    document.addEventListener('cut', blockProtectedAction);
    document.addEventListener('paste', blockProtectedAction);
    document.addEventListener('selectstart', blockProtectedAction);
    document.addEventListener('beforeinput', function(e) {
        if (e.inputType === 'insertFromPaste' || e.inputType === 'insertFromDrop') {
            blockProtectedAction(e);
        }
    });
    document.addEventListener('contextmenu', function() {
        getViewerLogger()('context_menu_blocked', { reason: 'right_click' });
    });

    document.addEventListener('keydown', function(e) {
        if (handleScreenshotShortcut(e, 'keydown')) {
            return false;
        }

        const key = normalizedEventKey(e);

        if ((e.ctrlKey || e.metaKey) && ['p', 's', 'u', 'c'].includes(key)) {
            e.preventDefault();
            e.stopPropagation();
            getViewerGuard()?.triggerGuard('Blocked a protected shortcut for this document.');
            const typeMap = { p: 'print_blocked', s: 'save_blocked', u: 'source_view_blocked', c: 'copy_blocked' };
            getViewerLogger()(typeMap[key] || 'copy_blocked', { key });
            return false;
        }
    }, true);

    document.addEventListener('keyup', function(e) {
        handleScreenshotShortcut(e, 'keyup');
    }, true);

    window.addEventListener('beforeprint', function() {
        getViewerGuard()?.triggerGuard('Printing is blocked in the protected viewer.', 5000);
        getViewerLogger()('print_blocked', { reason: 'beforeprint' });
    });

    window.addEventListener('afterprint', function() {
        getViewerGuard()?.setGuardState(false, getViewerLoadedMessage());
    });

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            getViewerGuard()?.setGuardState(true, 'Document hidden while this tab is inactive.');
            getViewerLogger()('tab_hidden', { reason: 'visibilitychange' });
            return;
        }

        getViewerGuard()?.setGuardState(false, getViewerLoadedMessage());
    });

    window.addEventListener('blur', function() {
        getViewerGuard()?.setGuardState(true, 'Document hidden while the window is out of focus.');
        getViewerLogger()('window_blur', { reason: 'window_blur' });
    });

    window.addEventListener('focus', function() {
        getViewerGuard()?.setGuardState(false, getViewerLoadedMessage());
    });
</script>

</body>
</html>
