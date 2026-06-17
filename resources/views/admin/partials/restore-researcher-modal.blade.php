{{-- ============================================================ --}}
{{-- I-SAVE AS: resources/views/admin/partials/restore-researcher-modal.blade.php --}}
{{-- Ginagamit sa: researcher-accounts.blade.php at graduated-researchers.blade.php --}}
{{-- ============================================================ --}}

<div id="restoreModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-icon">♻️</span>
            <div>
                <h3 class="modal-title">Restore Researcher Account</h3>
                <p class="modal-subtitle" id="modalSubtitle">Update the year level to reactivate this researcher account.</p>
            </div>
        </div>

        <div class="modal-note">
            <strong>Once restored:</strong> The researcher will be able to submit new research again.
            The graduation year will be recomputed based on the new year level.
        </div>

        <form method="POST" id="restoreForm" action="">
            @csrf
            <div class="modal-fields">
                <div class="modal-field">
                    <label>Current Year Level</label>
                    <select name="year_level" id="modalYearLevel" required onchange="updateModalGradYear()">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label>Course Duration</label>
                    <select name="course_duration" id="modalCourseDuration" required onchange="updateModalGradYear()">
                        <option value="3">3 Years</option>
                        <option value="4">4 Years</option>
                    </select>
                </div>
                <div class="modal-field modal-field-full">
                    <label>Researcher End Date</label>
                    <input type="date" name="researcher_end_date" id="modalResearcherEndDate">
                </div>
            </div>

            <div class="modal-preview">
                🎓 New Graduation Year: <strong id="modalGradYear">—</strong>
                <span id="modalYearsLeft" class="modal-years-left"></span>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeRestoreModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">✅ Restore as Active Researcher</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;padding:1rem;}
.modal-box{background:var(--card-bg,#fff);border-radius:16px;padding:1.75rem;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-header{display:flex;align-items:flex-start;gap:12px;margin-bottom:1rem;}
.modal-icon{font-size:1.75rem;flex-shrink:0;}
.modal-title{font-size:1.1rem;font-weight:700;margin:0 0 2px;}
.modal-subtitle{color:var(--muted);font-size:.8125rem;margin:0;}
.modal-note{background:#ede9fe;border:1px solid #c4b5fd;border-radius:8px;padding:10px 14px;font-size:.8125rem;color:#4c1d95;margin-bottom:1.25rem;line-height:1.55;}
.modal-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1rem;}
.modal-field-full{grid-column:1 / -1;}
.modal-field label{display:block;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:5px;}
.modal-field select,.modal-field input{width:100%;padding:8px 10px;border:1.5px solid var(--border,#e5e7eb);border-radius:8px;font-size:.875rem;outline:none;font-family:inherit;}
.modal-field select:focus,.modal-field input:focus{border-color:#7c3aed;}
.modal-preview{background:#f5f3ff;border:1.5px solid #7c3aed;border-radius:8px;padding:10px 14px;font-size:.875rem;color:#3b0f7a;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.modal-years-left{font-size:.75rem;color:#7c3aed;margin-left:auto;}
.modal-actions{display:flex;gap:.75rem;justify-content:flex-end;}
</style>

<script>
function openRestoreModal(userId, userName, courseDuration) {
    document.getElementById('modalSubtitle').textContent = 'Restoring: ' + userName;
    document.getElementById('restoreForm').action =
        '{{ url("admin/users") }}/' + userId + '/restore-researcher';
    document.getElementById('modalCourseDuration').value = courseDuration || 4;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('modalResearcherEndDate').min = tomorrow.toISOString().split('T')[0];
    document.getElementById('restoreModal').style.display = 'flex';
    updateModalGradYear();
}
function closeRestoreModal() {
    document.getElementById('restoreModal').style.display = 'none';
}
function updateModalGradYear() {
    const yl   = parseInt(document.getElementById('modalYearLevel').value);
    const cd   = parseInt(document.getElementById('modalCourseDuration').value);
    const now  = new Date().getFullYear();
    if (yl && cd) {
        const gradYear   = now + (cd - yl);
        const yearsLeft  = cd - yl;
        document.getElementById('modalGradYear').textContent = gradYear;
        document.getElementById('modalYearsLeft').textContent =
            yearsLeft === 0 ? '(graduating this year)' :
            yearsLeft === 1 ? '(1 year left)' :
            '(' + yearsLeft + ' years left)';
    }
}
document.getElementById('restoreModal').addEventListener('click', function(e) {
    if (e.target === this) closeRestoreModal();
});
</script>
