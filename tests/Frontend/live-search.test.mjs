import test from 'node:test';
import assert from 'node:assert/strict';
import { searchUrl, suggestions } from '../../public/js/live-search.js';

test('retains role and department filters while resetting pagination and disabling export', () => {
    const url = searchUrl('', [['search', 'old'], ['department', 'College of Computer Studies'], ['status', 'pending'], ['page', '4'], ['export', 'csv'], ['_token', 'secret']], 'search', 'robot', 'https://ube.test/admin/researches?imported=1&page=3');
    assert.equal(url.pathname, '/admin/researches');
    assert.equal(url.searchParams.get('search'), 'robot');
    assert.equal(url.searchParams.get('department'), 'College of Computer Studies');
    assert.equal(url.searchParams.get('status'), 'pending');
    assert.equal(url.searchParams.get('imported'), '1');
    for (const key of ['page', 'export', '_token']) assert.equal(url.searchParams.has(key), false);
});

test('explicit actions stay in their own search context and encode special characters', () => {
    const url = searchUrl('/', [['year_from', '2024'], ['search', 'old']], 'search', 'A&B <study>', 'https://ube.test/dashboard?role=admin');
    assert.equal(url.pathname, '/');
    assert.equal(url.searchParams.has('role'), false);
    assert.equal(url.searchParams.get('search'), 'A&B <study>');
    assert.equal(url.searchParams.get('year_from'), '2024');
    assert.equal(url.searchParams.getAll('search').length, 1);
});

test('refuses to send form data to a different origin', () => {
    assert.throws(() => searchUrl('https://other.test/', [], 'search', 'private', 'https://ube.test/admin/users'));
});

test('only annotated authorized records become suggestions; duplicate entries are removed', () => {
    const nodes = [
        {dataset: {searchLabel: 'Research One', searchDetail: 'Author A'}},
        {dataset: {searchLabel: 'Research One', searchDetail: 'Author A'}},
        {dataset: {searchLabel: 'Research Two', searchUrl: '/research/2'}},
        {dataset: {searchLabel: ''}},
    ];
    const root = {querySelectorAll(selector) { assert.equal(selector, '[data-search-label]'); return nodes; }};
    const results = suggestions(root);
    assert.deepEqual(results.map(result => result.label), ['Research One', 'Research Two']);
    assert.equal(results[1].url, '/research/2');
});

test('limits the dropdown to eight records and keeps labels as text', () => {
    const nodes = Array.from({length: 15}, (_, i) => ({dataset: {searchLabel: i ? `Paper ${i}` : '<img src=x onerror=alert(1)>'}}));
    const results = suggestions({querySelectorAll: () => nodes});
    assert.equal(results.length, 8);
    assert.equal(results[0].label, '<img src=x onerror=alert(1)>');
});
