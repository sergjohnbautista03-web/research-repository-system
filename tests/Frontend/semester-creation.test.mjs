import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/semester-creation.js', import.meta.url), 'utf8');
function setup(plans) {
    const year = { value: '2026-2027', events: {}, addEventListener(name, handler) { this.events[name] = handler; } };
    const nodes = { add_school_year: year, add_semester: {}, add_semester_display: {},
        semesterCreationHelp: {}, saveSemesterButton: {}, 'semester-creation-plans': { textContent: JSON.stringify(plans) } };
    const window = {};
    vm.runInNewContext(source, { document: { getElementById: id => nodes[id] }, window });
    return { year, nodes, window };
}

test('ongoing first semester previews second semester and prevents saving it', () => {
    const { nodes } = setup({ '2026-2027': { semester: '2nd', can_create: false, message: 'Wait until 1st Semester ends.' } });
    assert.equal(nodes.add_semester.value, '2nd');
    assert.equal(nodes.add_semester_display.value, '2nd Semester');
    assert.equal(nodes.saveSemesterButton.disabled, true);
    assert.equal(nodes.semesterCreationHelp.textContent, 'Wait until 1st Semester ends.');
});

test('year changes select the correct next term and reject invalid or completed years', () => {
    const { year, nodes, window } = setup({
        '2026-2027': { semester: '2nd', can_create: true, message: '1st Semester has finished.' },
        '2025-2026': { semester: null, can_create: false, message: 'Use a new academic year.' },
    });
    assert.equal(nodes.saveSemesterButton.disabled, false);
    year.value = '2027-2028';
    year.events.input();
    assert.equal(nodes.add_semester.value, '1st');
    assert.equal(nodes.saveSemesterButton.disabled, false);
    year.value = '2025-2026';
    window.SemesterCreation.update();
    assert.equal(nodes.add_semester_display.value, 'Academic Year Complete');
    assert.equal(nodes.saveSemesterButton.disabled, true);
    year.value = '2027-2030';
    year.events.input();
    assert.equal(nodes.add_semester.value, '');
    assert.equal(nodes.saveSemesterButton.disabled, true);
});
