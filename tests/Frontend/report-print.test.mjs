import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

test('report preview filters academic year strings and sorts selected years newest first', () => {
    const template = readFileSync(new URL('../../resources/views/admin/reports.blade.php', import.meta.url), 'utf8');
    const functions = template.slice(template.indexOf('function reportYearInputs()'), template.indexOf('function syncReportYearSelection('));
    const allYears = { checked: false };
    const inputs = [
        { value: '2024-2025', checked: true },
        { value: '2026-2027', checked: true },
        { value: '2025-2026', checked: false },
    ];
    const context = vm.createContext({
        reportExportRows: [
            { department: 'CCS', year: '2026-2027' },
            { department: 'CCS', year: '2025-2026' },
            { department: 'Other', year: '2024-2025' },
            { department: 'CCS', year: null },
        ],
        document: {
            querySelectorAll: () => inputs,
            getElementById: id => id === 'reportExportAllYears' ? allYears
                : id === 'reportExportDepartment' ? { value: 'CCS', dataset: {} } : null,
        },
    });
    vm.runInContext(functions, context);
    assert.deepEqual(Array.from(vm.runInContext('selectedReportYears()', context)), ['2026-2027', '2024-2025']);
    assert.equal(vm.runInContext('matchingReportExportRows().length', context), 1);
    allYears.checked = true;
    assert.equal(vm.runInContext('matchingReportExportRows().length', context), 3);
});

test('changing report departments updates program options and clears incompatible selections', () => {
    const template = readFileSync(new URL('../../resources/views/admin/reports.blade.php', import.meta.url), 'utf8');
    const functions = template.slice(template.indexOf('function updateReportPrograms('), template.indexOf('function syncReportYearSelection('));
    const program = { value: 'Information Technology', replaceChildren(...options) { this.options = options; } };
    const exportProgram = { value: 'Information Technology' };
    const context = vm.createContext({
        reportProgramsByDepartment: {
            CCS: ['Computer Science', 'Information Technology'],
            Business: ['Accountancy'],
        },
        reportExportRows: [
            { department: 'CCS', program: 'Information Technology', year: '2026-2027' },
            { department: 'CCS', program: 'Computer Science', year: '2026-2027' },
            { department: 'Business', program: 'Information Technology', year: '2026-2027' },
        ],
        Option: class { constructor(text, value) { this.text = text; this.value = value; } },
        document: {
            querySelectorAll: () => [],
            getElementById: id => id === 'reportProgram' ? program
                : id === 'reportExportProgram' ? exportProgram
                : id === 'reportExportDepartment' ? { value: 'CCS', dataset: {} }
                : id === 'reportExportAllYears' ? { checked: true } : null,
        },
    });
    vm.runInContext(functions, context);
    vm.runInContext("updateReportPrograms('CCS', 'reportProgram')", context);
    assert.deepEqual(program.options.map(option => option.value), ['', 'Computer Science', 'Information Technology']);
    assert.equal(program.value, 'Information Technology');
    assert.equal(vm.runInContext('matchingReportExportRows().length', context), 1);
    exportProgram.value = '';
    assert.equal(vm.runInContext('matchingReportExportRows().length', context), 2);
    vm.runInContext("updateReportPrograms('Business', 'reportProgram')", context);
    assert.deepEqual(program.options.map(option => option.value), ['', 'Accountancy']);
    assert.equal(program.value, '');
    vm.runInContext("updateReportPrograms('', 'reportProgram')", context);
    assert.deepEqual(program.options.map(option => option.value), ['', 'Accountancy', 'Computer Science', 'Information Technology']);
});

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
