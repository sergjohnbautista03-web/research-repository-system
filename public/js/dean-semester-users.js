(() => {
    function initialize(root = document, submitHandler = null) {
        const filters = root.querySelector?.('.su-filters');
        const memberType = filters?.elements.member_type;
        const yearLevel = filters?.elements.year_level;
        const yearFilter = filters?.querySelector('[data-semester-year-filter]');
        if (memberType && yearLevel && yearFilter) {
            function updateYearFilter() {
                const student = memberType.value === 'student';
                yearFilter.hidden = !student;
                yearLevel.disabled = !student;
                if (!student) yearLevel.value = '';
            }
            memberType.addEventListener('change', updateYearFilter);
            updateYearFilter();
        }
        const find = id => root === document ? document.getElementById(id) : root.querySelector(`#${id}`);
        const form = find('semester-users-form');
        if (!form) return;
        const all = find('semester-select-all');
        const boxes = [...form.querySelectorAll('input[name="user_ids[]"]:not(:disabled)')];
        const count = find('semester-selection-count');
        const button = find('semester-activate-button');
        function update() {
            const selected = boxes.filter(box => box.checked).length;
            count.textContent = `${selected} selected`;
            all.checked = boxes.length > 0 && selected === boxes.length;
            all.indeterminate = selected > 0 && selected < boxes.length;
            all.disabled = boxes.length === 0;
            button.disabled = selected === 0;
        }
        all.addEventListener('change', () => {
            boxes.forEach(box => { box.checked = all.checked; });
            update();
        });
        boxes.forEach(box => box.addEventListener('change', update));
        form.addEventListener('submit', event => {
            if (!boxes.some(box => box.checked)) event.preventDefault();
            else if (submitHandler) {
                event.preventDefault();
                submitHandler(form);
            }
        });
        update();
    }
    window.SemesterUserSelection = { initialize };
    initialize();
})();
