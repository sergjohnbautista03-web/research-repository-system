# Academic Year journal batches

The Research Coordinator already has access to completed research papers after defense and final approval and personally compiles them into journals outside the system. The system collects the finished journals and PDFs under the Dean's Academic Year assignment. Research papers do not need to be registered or selected in the system before journals can be uploaded.

1. The Department Dean selects **Assign Journal Task**, chooses an Academic Year and a program from their assigned department, and enters the category and instructions. The system names the batch from the program and Academic Year, for example **Information Technology Journals - AY 2026-2027**. The assignment goes to the active Research Coordinator in the department. Dean attachments are prohibited. IT and Computer Science use separate assignments even for the same Academic Year. Each uploaded journal still has its own title.
2. **Assigned Journal Tasks** lists only pending assignments and journals still being prepared, with Pending and Preparing Journals status filters. Select **Prepare Batch** to work on an assignment. Once submitted to the Dean, the batch appears only in **Journal Batches**, where **Open Batch** opens its review. Preparation and review open in a modal while the task list stays on the same page. Adding, editing, saving and submitting journals remain inside the modal; closing it refreshes the list after a saved change. **Add Journal** is available immediately, including when no papers are registered for the assigned Academic Year.
3. The Coordinator selects **Add Journal**, enters the title, authors, abstract, optional comma-separated keywords, category, type, publication year and program where required, and uploads the completed PDF. Select **Save Journal in Batch**, then repeat for the remaining journals. Details can be saved before uploading the PDF; submission requires all PDFs.
4. **Submit Completed Batch to Dean** requires at least one journal, complete details, and an existing uploaded PDF for every journal. All journals enter Dean review together, and the submitted membership is fixed for review.
5. The Dean's **Journal Batches** list receives a batch only after the Coordinator submits the completed journals. Assigned tasks and journals still being prepared remain in **Journal Preparation Task**. Submitted batches remain in the batch list after forwarding and publication. The assigning Dean views all journal PDFs by scrolling down the batch page and selects **Forward Batch to Admin**. There is no Dean correction, revision, or separate approval step.
6. Admin opens **Journal Batches for Publication**, views the same journals by scrolling, and selects **Publish All Journals in Batch**. Every journal and the batch are published in a single database transaction, and each journal appears as a separate repository entry. A missing PDF or incomplete member prevents publication of the whole batch. **Researches** lists only published records; journals awaiting publication stay in the batch queue. Search, department, Academic Year and publication year filters apply to published records. Old unpublished-status links redirect to the published list while preserving the other filters.

The assigned Academic Year, department and program are fixed on every journal. Coordinators cannot change them. The program appears on assignment cards, batch details and the batch list, which can be filtered by Academic Year and program. Annual preparation and review can continue after a semester closes. Academic Year filters use the saved year even when no individual semester is selected. Year Published remains a separate field. Older assignments without a program retain their existing journals and allow the Coordinator to select a program per journal.

Authors use the same separate name fields as Admin's Add Research form. Select **Add Researcher** for student journals or **Add Co-author** for faculty journals, and use the trash button to remove a name. At least one name is required, with up to 12 names and 150 characters per name. Editing loads each saved author into its own field.

| Batch and journal status | Responsible role | Available next step |
| --- | --- | --- |
| `draft` | Assigned coordinator | Prepare journals and submit complete batch |
| `dean_review` | Assigning Dean | Forward complete batch directly to Admin |
| `pending` | Admin | Publish all journals together |
| `approved` | Admin | Journals published in the repository |

Admin receives only batches forwarded by the assigning Dean. Coordinator and Dean actions enforce assignment ownership and department scope. Submitted journals cannot be edited. Individual journal endpoints cannot bypass batch submission, forwarding, or publication. Batch transitions lock the assignment and all members and recheck completeness inside a database transaction. Notifications report one batch event instead of a separate event for every journal. Existing `dean_approved` batches can still be forwarded, and existing `rejected` batches can still be prepared and resubmitted; neither status is produced by the new flow. Older single-journal tasks also go directly from the assigning Dean to Admin.

## Research Coordinator reports

**Reports** covers only journal preparation tasks assigned to the signed-in coordinator in their department. The page displays summary cards, filters and the task table. Academic Year, Program, Journal Category and Status filters apply to the summary cards, task table and exported breakdowns. Select **Generate Report** to apply them or **Clear** to reset them. **Export PDF** and **Export Excel** use the current selections and include every matching task, including rows beyond the table's current page. The Excel workbook includes summary, task records, and four breakdown worksheets.

The coordinator report shows **Pending**, **Preparing Journals**, and **Submitted to Dean**. Received assignments and saved journals are preparation work. A completed submission remains Submitted to Dean in this report when later review or publication proceeds. Legacy returned journals go back to Preparing Journals. Date Submitted uses the stored submission timestamp in Asia/Manila; tasks still in preparation show an em dash, and missing historical timestamps are not guessed.

Assigned-task totals include legacy single-journal assignments. Journal totals count actual non-deleted prepared journal records, excluding source-paper coverage entries. Batch totals count batch assignments, including those still being prepared; submitted-batch totals include only batch assignments submitted to the Dean. Legacy single-journal tasks count as tasks and journals, but do not inflate batch totals. All counts and breakdowns use the same filtered database records.

## Database update

Apply these migrations in order after the existing migrations:

- `2026_10_04_000001_add_dean_journal_review_workflow.php`
- `2026_10_05_000001_add_academic_year_journal_batches.php`
- `2026_10_05_000002_add_program_to_journal_assignments.php`

The second adds batch stages and approval timestamps. Unstarted assignments with an AY become batches. Existing tasks that already have a journal retain the single-journal review workflow, and existing publications stay published. Historical attachments and source-paper coverage records remain stored for compatibility; direct journal uploads and batch transitions do not require them. The third adds the assignment's nullable program field without guessing a program for existing tasks or modifying existing journals.

The first migration moves existing unpublished coordinator submissions to Dean review and backfills assignment years from their linked semester or recorded starting year. It does not assign an AY to unrelated approved source papers.

Regression tests cover ten directly uploaded journals without source records, direct Dean-to-Admin forwarding, retired approval/return actions, scrollable PDF review, empty batches, missing PDFs during forwarding and publication, PDF replacement, role and ownership restrictions, immutable AY, department and program, IT/CS separation in the same year, program filters, atomic publication as separate entries, batch notifications, legacy compatibility, and migration up/down.
