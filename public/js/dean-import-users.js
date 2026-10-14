(() => {
    const form = document.getElementById('iu_import_form');
    if (!form) return;
    const file = document.getElementById('iu_file');
    const filename = document.getElementById('iu_filename');
    const submit = document.getElementById('iu_submit_btn');
    const types = [...form.querySelectorAll('input[name="import_member_type"]')];
    const guides = [...form.querySelectorAll('[data-import-guide]')];

    function update() {
        const type = types.find(input => input.checked).value;
        const label = type === 'faculty' ? 'Faculty' : 'Student';
        guides.forEach(guide => { guide.hidden = guide.dataset.importGuide !== type; });
        filename.textContent = file.files[0]?.name || `Upload ${label} Excel or CSV File`;
        submit.textContent = type === 'faculty' ? 'Import Faculty' : 'Import Students';
        file.setCustomValidity(file.files[0]?.size > 10 * 1024 * 1024 ? 'Maximum file size is 10MB.' : '');
    }

    types.forEach(input => input.addEventListener('change', () => {
        file.value = '';
        update();
    }));
    file.addEventListener('change', update);
    update();
})();
