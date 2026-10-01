<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $research->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">


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

        *, *::before, *::after { box-sizing: border-box; }

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
            min-width: 0;
            overflow-wrap: anywhere;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .viewer-note {
            min-width: 0;
            overflow-wrap: anywhere;
            font-size: 12px;
            color: rgba(248, 250, 252, 0.8);
            text-align: right;
        }

        .viewer-shell {
            width: 100%;
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
            grid-template-columns: minmax(0, 1fr);
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
            background: #0f172a;
            color: #f8fafc;
            text-align: center;
        }

        .screen-guard.is-visible {
            display: flex;
        }

        .screen-guard-card {
            width: 100%;
            max-width: 560px;
            max-height: 100%;
            overflow-y: auto;
            overflow-wrap: anywhere;
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

        #resumeViewing {
            min-height: 44px;
            max-width: 100%;
            margin-top: 18px;
            padding: 10px 18px;
            border: 1px solid #c4b5fd;
            border-radius: 10px;
            background: #ede9fe;
            color: #3b0f7a;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        #resumeViewing[hidden] { display: none; }
        #resumeViewing:disabled { opacity: .5; cursor: default; }
        #resumeViewing:focus-visible { outline: 3px solid #a78bfa; outline-offset: 4px; }

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

        @media (max-width: 480px) {
            .viewer-topbar { padding: 12px; gap: 8px; }
            .viewer-shell { padding: 16px 8px 24px; }
            .viewer-pages { gap: 12px; }
            .screen-guard { padding: 12px; }
            .screen-guard-card { padding: 18px; border-radius: 14px; }
            #resumeViewing { width: 100%; font-size: 16px; }
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

<div class="screen-guard" id="screenGuard" role="alert" aria-live="assertive" aria-hidden="true">
    <div class="screen-guard-card">
        <strong id="screenGuardTitle">Content Protected by PHILCST.</strong>
        <span id="screenGuardDescription">The document is hidden while this window is inactive.</span>
        <button type="button" id="resumeViewing" hidden>Resume Viewing</button>
    </div>
</div>

<script>
    window.protectedViewerConfig = {
        logUrl: @json(route('research.capture-attempt', $research, false)),
        viewerScope: @json(!empty($adminMode) ? 'admin' : 'standard'),
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        loadedMessage: 'Protected document loaded. Authorized academic use only.',
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

<script src="{{ asset('js/document-protection.js') }}?v={{ filemtime(public_path('js/document-protection.js')) }}"></script>

<script type="module">
    import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs';

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs';

    const signedUrl = @json($signedUrl);
    const viewerConfig = window.protectedViewerConfig || {};
    const statusEl = document.getElementById('viewerStatus');
    const pagesEl = document.getElementById('viewerPages');

    function updateStatus(message) {
        statusEl.textContent = message;
    }

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

            window.logCaptureAttempt?.('protected_view_opened', {
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



</body>
</html>
