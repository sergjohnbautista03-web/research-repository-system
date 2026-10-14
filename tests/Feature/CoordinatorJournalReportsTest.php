<?php

namespace Tests\Feature;

use App\Models\JournalBatchItem;
use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Services\CoordinatorJournalReport;
use Illuminate\Support\Carbon;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;
use ZipArchive;

class CoordinatorJournalReportsTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->coordinator = $this->staff(['is_research_coordinator' => true]);
    }

    private function staff(array $attributes = []): User
    {
        return User::create($attributes + ['name' => 'Report Staff '.(User::count() + 1),
            'email' => 'report'.(User::count() + 1).'@example.test', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_active' => true]);
    }

    private function task(array $attributes = []): ResearchHandoff
    {
        return ResearchHandoff::create($attributes + ['coordinator_id' => $this->coordinator->id,
            'department' => $this->coordinator->department, 'is_batch' => true, 'batch_stage' => Research::STATUS_DRAFT,
            'status' => ResearchHandoff::STATUS_PENDING, 'title' => 'Assigned Task',
            'school_year' => '2026-2027', 'program' => 'Information Technology',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL]);
    }

    private function journal(ResearchHandoff $task, array $attributes = []): Research
    {
        return Research::create($attributes + ['research_handoff_id' => $task->id,
            'coordinator_id' => $task->coordinator_id, 'department' => $task->department,
            'submission_category' => $task->submission_category, 'school_year' => $task->school_year,
            'program' => $task->program, 'title' => 'Prepared Journal', 'status' => Research::STATUS_DRAFT]);
    }

    private function fixtures(): array
    {
        $pending = $this->task();
        $preparing = $this->task(['school_year' => '2025-2026', 'program' => 'Computer Science', 'status' => ResearchHandoff::STATUS_RECEIVED]);
        $this->journal($preparing);
        $submitted = $this->task(['school_year' => '2024-2025', 'program' => null,
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'batch_stage' => Research::STATUS_DEAN_REVIEW, 'batch_submitted_at' => Carbon::parse('2026-10-06 02:30:00', 'Asia/Manila')]);
        $this->journal($submitted);
        $this->journal($submitted);

        return compact('pending', 'preparing', 'submitted');
    }

    public function test_report_uses_only_owned_journal_tasks_and_counts_prepared_journals_not_source_papers(): void
    {
        $tasks = $this->fixtures();
        $other = $this->staff(['is_research_coordinator' => true]);
        $this->journal($this->task(['coordinator_id' => $other->id]));
        $this->journal($this->task(['department' => 'Another College', 'program' => 'Secret Program']));
        $this->task(['submission_category' => Research::SUBMISSION_CATEGORY_RESEARCH]);
        $source = $this->journal($tasks['submitted'], ['submission_category' => Research::SUBMISSION_CATEGORY_RESEARCH]);
        JournalBatchItem::create(['batch_id' => $tasks['submitted']->id, 'source_research_id' => $source->id, 'sort_order' => 1]);
        $this->journal($tasks['submitted'])->delete();
        $this->journal($tasks['submitted'], ['coordinator_id' => $other->id]);
        $this->journal($tasks['submitted'], ['department' => 'Another College']);

        $response = $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports'))->assertOk()
            ->assertViewHas('totals', ['tasks' => 3, 'journals' => 3, 'batches' => 3, 'submitted' => 1])
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === collect($tasks)->pluck('id')->sort()->values()->all())
            ->assertSee('Export PDF')->assertSee('Export Excel')->assertSee('Generate Report')
            ->assertSee('Date Submitted')->assertSee('Oct 06, 2026')->assertSee('—')
            ->assertDontSee('Download CSV')->assertDontSee('Secret Program');
        foreach (['Received by Research Coordinator', 'Forwarded to Admin', 'Approved by Dean', 'Published', 'Semester'] as $label) {
            $response->assertDontSee($label);
        }
        $this->assertSame(CoordinatorJournalReport::STATUSES, $response->viewData('statuses'));
        $this->assertSame(['2026-2027', '2025-2026', '2024-2025'], $response->viewData('schoolYears')->all());
        foreach ($response->viewData('breakdowns') as $breakdown) {
            $this->assertSame(3, $breakdown['rows']->sum('tasks'));
            $this->assertSame(3, $breakdown['rows']->sum('journals'));
            $this->assertSame(1, $breakdown['rows']->sum('submitted'));
        }
    }

    public function test_coordinator_report_and_exports_do_not_expose_research_ids(): void
    {
        $this->fixtures();
        $ids = Research::pluck('id')->map(fn ($id) => Research::findOrFail($id)->research_id);
        $response = $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports'))->assertOk()
            ->assertDontSee('Research ID')->assertViewHas('rows', fn ($rows) => $rows->every(fn ($row) => ! array_key_exists('research_ids', $row)));
        $excel = $this->get(route('admin.coordinator.reports.export', ['format' => 'excel']))->assertOk();
        $sheets = $this->workbook($excel->getContent());
        $this->assertCount(6, $sheets['xl/worksheets/sheet2.xml'][0]);
        $workbookText = json_encode($sheets);
        $this->assertStringNotContainsString('Research ID', $workbookText);
        $pdf = $this->get(route('admin.coordinator.reports.export', ['format' => 'pdf']))->assertOk();
        $pdfText = $this->pdfText($pdf->getContent());
        $this->assertStringNotContainsString('Research ID', $pdfText);
        foreach ($ids as $id) {
            $response->assertDontSee($id);
            $this->assertStringNotContainsString($id, $workbookText);
            $this->assertStringNotContainsString($id, $pdfText);
        }
    }

    public function test_all_four_filters_apply_to_rows_totals_and_every_breakdown(): void
    {
        $tasks = $this->fixtures();
        $filters = ['school_year' => '2024-2025', 'program' => '__all_programs',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'submitted'];
        $response = $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports', $filters))->assertOk()
            ->assertViewHas('totals', ['tasks' => 1, 'journals' => 2, 'batches' => 1, 'submitted' => 1])
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$tasks['submitted']->id]);
        foreach ($response->viewData('breakdowns') as $breakdown) {
            $this->assertCount(1, $breakdown['rows']);
            $this->assertSame(2, $breakdown['rows']->first()['journals']);
        }
        foreach (['school_year', 'program', 'submission_category', 'status'] as $key) {
            $this->assertSame($filters[$key], $response->viewData('filters')[$key]);
        }
        // A mismatched selection must not silently return an unfiltered report.
        $filters['program'] = 'Computer Science';
        $this->get(route('admin.coordinator.reports', $filters))->assertOk()
            ->assertViewHas('totals', ['tasks' => 0, 'journals' => 0, 'batches' => 0, 'submitted' => 0])
            ->assertSee('No journal preparation tasks found.');
        $this->get(route('admin.coordinator.reports'))->assertViewHas('totals', fn ($totals) => $totals['tasks'] === 3);
    }

    public function test_coordinator_states_preserve_submissions_and_hide_stale_dates_for_returned_batches(): void
    {
        $expected = [];
        foreach ([Research::STATUS_DEAN_REVIEW, Research::STATUS_DEAN_APPROVED, Research::STATUS_PENDING, Research::STATUS_APPROVED] as $stage) {
            $task = $this->task(['batch_stage' => $stage, 'batch_submitted_at' => '2026-10-01']);
            $expected[$task->id] = 'submitted';
        }
        $returned = $this->task(['batch_stage' => Research::STATUS_REJECTED, 'batch_submitted_at' => '2026-09-01']);
        $expected[$returned->id] = 'preparing';
        $started = $this->task(['status' => ResearchHandoff::STATUS_RECEIVED]);
        $expected[$started->id] = 'preparing';
        $pending = $this->task();
        $expected[$pending->id] = 'pending';
        $response = $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports'))->assertOk();
        $rows = $response->viewData('rows')->keyBy('id');
        foreach ($expected as $id => $status) {
            $this->assertSame($status, $rows[$id]['status_key']);
        }
        $this->assertSame('—', $rows[$returned->id]['submitted_at']);
        $this->assertSame('—', $rows[$started->id]['submitted_at']);
        $this->assertSame('—', $rows[$pending->id]['submitted_at']);
        $this->assertSame(4, $response->viewData('totals')['submitted']);
    }

    public function test_legacy_journal_assignments_are_tasks_and_journals_but_not_batch_totals(): void
    {
        $legacy = $this->task(['is_batch' => false, 'school_year' => null, 'program' => null]);
        $journal = $this->journal($legacy, ['school_year' => '2022-2023', 'program' => 'Computer Science',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'status' => Research::STATUS_DEAN_REVIEW, 'submitted_to_dean_at' => '2026-09-15']);
        $legacy->update(['research_id' => $journal->id]);
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports', [
            'school_year' => '2022-2023', 'program' => 'Computer Science',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'submitted',
        ]))->assertOk()->assertViewHas('totals', ['tasks' => 1, 'journals' => 1, 'batches' => 0, 'submitted' => 0])
            ->assertSee('Sep 15, 2026');
    }

    public function test_empty_report_and_both_exports_are_valid(): void
    {
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports'))->assertOk()
            ->assertViewHas('totals', ['tasks' => 0, 'journals' => 0, 'batches' => 0, 'submitted' => 0])
            ->assertSee('No journal preparation tasks found.');
        $pdf = $this->get(route('admin.coordinator.reports.export', ['format' => 'pdf']))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('No matching records.', $this->pdfText($pdf->getContent()));
        $this->assertStringContainsString('(0)', $this->pdfText($pdf->getContent()));
        $excel = $this->get(route('admin.coordinator.reports.export', ['format' => 'excel']))->assertOk();
        $sheets = $this->workbook($excel->getContent());
        $this->assertCount(1, $sheets['xl/worksheets/sheet2.xml']);
        $this->assertSame('0', $sheets['xl/worksheets/sheet1.xml'][11][1]);
    }

    public function test_exports_use_all_selected_filters_and_contain_the_same_totals_breakdowns_and_dates(): void
    {
        $this->fixtures();
        $filters = ['school_year' => '2024-2025', 'program' => '__all_programs',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'submitted'];
        $this->actingAs($this->coordinator);
        $pdf = $this->get(route('admin.coordinator.reports.export', $filters + ['format' => 'pdf']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $text = $this->pdfText($pdf->getContent());
        $this->assertStringContainsString('2024-2025', $text);
        $this->assertStringContainsString('Oct 06, 2026', $text);
        $this->assertStringContainsString('Breakdown by Journal Category', $text);
        $this->assertStringNotContainsString('2025-2026', $text);
        $this->assertStringNotContainsString('Computer Science', $text);
        $excel = $this->get(route('admin.coordinator.reports.export', $filters + ['format' => 'excel']))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $sheets = $this->workbook($excel->getContent());
        $this->assertSame(CoordinatorJournalReport::HEADERS, $sheets['xl/worksheets/sheet2.xml'][0]);
        $this->assertSame(['2024-2025', 'All Programs', 'Faculty Research Journal', '2', 'Submitted to Dean', 'Oct 06, 2026'], $sheets['xl/worksheets/sheet2.xml'][1]);
        $this->assertCount(2, $sheets['xl/worksheets/sheet2.xml']);
        $this->assertSame('2', $sheets['xl/worksheets/sheet1.xml'][11][1]);
        foreach ([3, 4, 5, 6] as $index) {
            $this->assertSame(['1', '2', '1', '1'], array_slice($sheets['xl/worksheets/sheet'.$index.'.xml'][1], 1));
        }
    }

    public function test_exports_include_all_pages_and_excel_preserves_unicode_as_safe_text(): void
    {
        for ($index = 0; $index < 24; $index++) {
            $this->task();
        }
        $program = '=1+1 <Test> & Piñol';
        $this->task(['program' => $program]);
        $response = $this->actingAs($this->coordinator)->get(route('admin.coordinator.reports'))->assertOk();
        $this->assertCount(20, $response->viewData('tableRows')->items());
        $this->assertSame(25, $response->viewData('totals')['tasks']);
        $this->get(route('admin.coordinator.reports', ['page' => 2]))->assertViewHas('tableRows', fn ($rows) => count($rows->items()) === 5);
        $excel = $this->get(route('admin.coordinator.reports.export', ['format' => 'excel', 'page' => 2]))->assertOk();
        $sheets = $this->workbook($excel->getContent());
        $this->assertCount(26, $sheets['xl/worksheets/sheet2.xml']);
        $this->assertSame($program, $sheets['xl/worksheets/sheet2.xml'][1][1]);
        $pdf = $this->get(route('admin.coordinator.reports.export', ['format' => 'pdf', 'page' => 2]))->assertOk();
        $reader = new \setasign\Fpdi\Fpdi;
        $pageCount = $reader->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($pdf->getContent()));
        $this->assertGreaterThan(1, $pageCount);
        $this->assertStringContainsString('(25)', $this->pdfText($pdf->getContent()));
    }

    public function test_only_assigned_coordinators_can_access_reports_or_exports_and_invalid_statuses_are_rejected(): void
    {
        foreach ([$this->staff(), $this->staff(['is_department_dean' => true]),
            $this->staff(['is_research_coordinator' => true, 'department' => null])] as $user) {
            $this->actingAs($user)->get(route('admin.coordinator.reports'))->assertForbidden();
            foreach (['pdf', 'excel'] as $format) {
                $this->get(route('admin.coordinator.reports.export', compact('format')))->assertForbidden();
            }
        }
        $this->actingAs($this->coordinator);
        foreach (['received', 'dean_approved', 'forwarded', 'published'] as $status) {
            $this->getJson(route('admin.coordinator.reports', compact('status')))->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->getJson(route('admin.coordinator.reports.export', ['status' => $status, 'format' => 'excel']))
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $this->getJson(route('admin.coordinator.reports.export', ['format' => 'csv']))->assertUnprocessable();
    }

    private function pdfText(string $content): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches);

        return implode("\n", array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $matches[1]));
    }

    private function workbook(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'ube-report-test-');
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $sheets = [];
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (! str_ends_with($name, '.xml') && ! str_ends_with($name, '.rels')) {
                    continue;
                }
                $doc = new \DOMDocument;
                $this->assertTrue($doc->loadXML($zip->getFromIndex($index)), $name.' must be valid XML');
                $xpath = new \DOMXPath($doc);
                $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $this->assertSame(0, $xpath->query('//s:f')->length, 'Workbook must contain no executable formulas.');
                if (str_starts_with($name, 'xl/worksheets/')) {
                    $rows = [];
                    foreach ($xpath->query('//s:sheetData/s:row') as $row) {
                        $cells = [];
                        foreach ($xpath->query('s:c', $row) as $cell) {
                            $cells[] = $xpath->evaluate('string(s:is/s:t | s:v)', $cell);
                        }
                        $rows[] = $cells;
                    }
                    $sheets[$name] = $rows;
                }
            }
            $this->assertCount(6, $sheets);
        } finally {
            $zip->close();
            unlink($path);
        }

        return $sheets;
    }
}
