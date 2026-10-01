import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

test('all report print documents load the supplied letterhead before printing and clean up afterward', async () => {
    class Element {
        constructor() { this.childNodes = []; this.style = {}; this.classList = { add() {} }; }
        append(...nodes) { this.childNodes.push(...nodes); }
        remove() { this.removed = true; }
    }
    const report = {
        head: new Element(), body: new Element(), documentElement: { outerHTML: '<html></html>' },
        createElement: () => new Element(),
        querySelectorAll: () => [],
    };
    report.body.append({ textContent: 'Filtered research report' });
    let resolveImage, printed = 0, revoked = 0;
    const imageReady = new Promise(resolve => { resolveImage = resolve; });
    const frame = Object.assign(new Element(), {
        contentDocument: { images: [{ decode: () => imageReady }], fonts: { ready: Promise.resolve() } },
        contentWindow: { focus() {}, print() { printed++; } },
    });
    const context = vm.createContext({
        window: {}, Blob,
        URL: { createObjectURL: () => 'blob:report', revokeObjectURL: () => revoked++ },
        DOMParser: class { parseFromString() { return report; } },
        document: {
            currentScript: { dataset: { stylesheet: 'https://ube.test/css/report-print.css', letterhead: 'https://ube.test/images/report-letterhead.jpeg' } },
            createElement: () => frame, body: new Element(),
        },
    });
    vm.runInContext(readFileSync(new URL('../../public/js/report-print.js', import.meta.url), 'utf8'), context);
    await context.window.ReportPrint.printHtml('<html><body>Report</body></html>');
    assert.equal(report.head.childNodes[0].href, 'https://ube.test/css/report-print.css');
    assert.equal(report.body.childNodes[1].src, 'https://ube.test/images/report-letterhead.jpeg');
    const loading = frame.onload();
    assert.equal(printed, 0);
    resolveImage();
    await loading;
    assert.equal(printed, 1);
    frame.contentWindow.onafterprint();
    assert.equal(frame.removed, true);
    assert.equal(revoked, 1);
});
