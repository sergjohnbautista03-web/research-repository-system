(() => {
    const field = document.getElementById('journal-authors');
    if (!field) return;

    const list = document.getElementById('journal-author-list');
    const template = document.getElementById('journal-author-template');
    const addButton = document.getElementById('journal-add-author');
    const category = document.getElementById('journal-category');
    const label = document.getElementById('journal-author-label');
    const rows = () => Array.from(list.querySelectorAll('[data-author-row]'));

    function updateAuthors() {
        const currentRows = rows();
        const student = category.value === field.dataset.studentCategory;
        label.textContent = student ? 'Researchers *' : 'Co-author(s) *';
        addButton.querySelector('span').textContent = student ? 'Add Researcher' : 'Add Co-author';
        addButton.disabled = currentRows.length >= 12;
        currentRows.forEach(row => {
            const input = row.querySelector('input');
            input.placeholder = student ? 'Enter researcher name' : 'Enter co-author name';
            input.setAttribute('aria-label', student ? 'Researcher name' : 'Co-author name');
            row.querySelector('[data-remove-author]').disabled = currentRows.length <= 1;
        });
    }

    addButton.addEventListener('click', () => {
        if (rows().length >= 12) return;
        const row = template.content.firstElementChild.cloneNode(true);
        list.appendChild(row);
        updateAuthors();
        row.querySelector('input').focus();
    });

    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-author]');
        if (!button || !list.contains(button) || rows().length <= 1) return;
        const row = button.closest('[data-author-row]');
        const next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        updateAuthors();
        next.querySelector('input').focus();
    });

    category.addEventListener('change', updateAuthors);
    updateAuthors();
})();
