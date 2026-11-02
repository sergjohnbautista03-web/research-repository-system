import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('papers, views and citation modals filter by complete academic-year strings and reset to all years', () => {
    const template = readFileSync(new URL('../../resources/views/admin/dashboard.blade.php', import.meta.url), 'utf8');
    const source = template.slice(template.indexOf("let activeSummaryKey = 'views';"), template.indexOf('function openAnalyticsDetail('));
    const nodes = Object.fromEntries(['summaryCardModalTitle', 'summaryCardModalValue', 'summaryViewsFilter',
        'summaryViewsYear', 'summaryYearHint', 'summaryCardModal'].map(id => [id, {}]));
    const context = vm.createContext({
        analyticsSummary: {
            papers_by_year: { '2022-2023': 3, '2024-2025': 2 },
            views_by_year: { '2022-2023': 22, '2024-2025': 13 },
            citations_by_year: { '2022-2023': 6, '2024-2025': 6 },
        },
        summaryCardData: {
            papers: { label: 'Total Research Papers', value: 6 },
            views: { label: 'Total Views', value: 38 },
            citations: { label: 'Total Copy Citations', value: 13 },
        },
        document: { getElementById: id => nodes[id] },
        formatNumber: String, openRaModal() {}, closeRaModal() {},
    });
    vm.runInContext(source, context);
    for (const [key, all, selected] of [['papers', 6, 3], ['views', 38, 22], ['citations', 13, 6]]) {
        vm.runInContext(`openSummaryCardModal('${key}')`, context);
        assert.equal(nodes.summaryViewsYear.value, 'all');
        assert.equal(nodes.summaryCardModalValue.textContent, String(all));
        assert.match(nodes.summaryYearHint.textContent, /selected academic year/);
        nodes.summaryViewsYear.value = '2022-2023';
        vm.runInContext('updateSummaryViewsYear()', context);
        assert.equal(nodes.summaryCardModalValue.textContent, String(selected));
        nodes.summaryViewsYear.value = '2023-2024';
        vm.runInContext('updateSummaryViewsYear()', context);
        assert.equal(nodes.summaryCardModalValue.textContent, '0');
    }
});
