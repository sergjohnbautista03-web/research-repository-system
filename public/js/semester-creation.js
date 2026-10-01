(() => {
    const year = document.getElementById('add_school_year');
    if (!year) return;
    const plans = JSON.parse(document.getElementById('semester-creation-plans').textContent);
    const code = document.getElementById('add_semester');
    const display = document.getElementById('add_semester_display');
    const help = document.getElementById('semesterCreationHelp');
    const save = document.getElementById('saveSemesterButton');
    function update() {
        const value = year.value.trim();
        const match = /^(\d{4})-(\d{4})$/.exec(value);
        const validYear = match && Number(match[2]) === Number(match[1]) + 1;
        const plan = plans[value] ?? { semester: '1st', can_create: true,
            message: 'A new academic year starts with 1st Semester.' };
        code.value = validYear ? (plan.semester ?? '') : '';
        display.value = validYear ? (plan.semester ? `${plan.semester} Semester` : 'Academic Year Complete') : '';
        help.textContent = validYear ? plan.message : 'Enter consecutive years, e.g. 2026-2027.';
        save.disabled = !validYear || !plan.can_create;
    }
    year.addEventListener('input', update);
    window.SemesterCreation = { update };
    update();
})();
