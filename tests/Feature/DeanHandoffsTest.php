<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Concerns\JournalWorkflowSchema;

class DeanHandoffsTest extends TestCase
{
    use JournalWorkflowSchema;
    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        DB::table('researches')->insert(['id' => 1, 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'year_published' => 2025]);
        foreach (['pending', 'received', 'added'] as $index => $status) {
            DB::table('research_handoffs')->insert([
                'title' => 'Paper ' . $status, 'department' => 'BSIT', 'status' => $status,
                'research_id' => $index ? 1 : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('research_handoffs')->insert(['title' => 'Private other department', 'department' => 'BSHM', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $user = new User();
        $user->forceFill(['id' => 7, 'name' => 'Test Dean', 'role' => 'admin', 'department' => 'BSIT', 'is_department_dean' => true]);
        $this->actingAs($user);
    }

    public function test_dean_counts_and_table_are_department_scoped(): void
    {
        $view = (new AdminController())->researchHandoffs(Request::create('/'));
        $data = $view->getData();
        $this->assertSame(3, $data['totalCount']);
        $this->assertSame(2, $data['receivedCount']);
        $this->assertSame(1, $data['pendingCount']);
        $this->assertSame(5, $data['handoffs']->perPage());
        $html = $view->with('errors', new \Illuminate\Support\ViewErrorBag())->render();
        $this->assertStringContainsString('Journal Task Records', $html);
        $this->assertStringContainsString('Not yet assigned', $html);
        $this->assertStringNotContainsString('Private other department', $html);
    }

    public function test_combined_filters_keep_summary_counts_and_include_added_as_received(): void
    {
        DB::table('research_handoffs')->where('title', 'Paper added')->update([
            'program' => 'Information Technology', 'school_year' => '2025-2026',
        ]);
        DB::table('research_handoffs')->where('title', 'Paper received')->update([
            'program' => 'Computer Science', 'school_year' => '2025-2026',
        ]);
        $request = Request::create('/', 'GET', ['program' => 'Information Technology', 'school_year' => '2025-2026', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'received']);
        $data = (new AdminController())->researchHandoffs($request)->getData();
        $this->assertSame(1, $data['handoffs']->total());
        $this->assertSame('Paper added', $data['handoffs']->first()->title);
        $this->assertSame(3, $data['totalCount']);
    }

    public function test_program_filters_follow_the_displayed_program_and_keep_choices_department_scoped(): void
    {
        DB::table('researches')->where('id', 1)->update(['program' => 'Computer Science', 'course' => 'Information Technology']);
        DB::table('research_handoffs')->whereIn('title', ['Paper pending', 'Paper added'])->update(['program' => 'Information Technology']);
        DB::table('research_handoffs')->where('title', 'Private other department')->update(['program' => 'Secret Program']);
        $controller = new AdminController();
        $data = $controller->researchHandoffs(Request::create('/', 'GET', [
            'program' => 'Information Technology', 'search' => 'obsolete search', 'year' => '1900',
        ]))->getData();
        $this->assertEqualsCanonicalizing(['Paper pending', 'Paper added'], $data['handoffs']->pluck('title')->all());
        $this->assertSame(['Computer Science', 'Information Technology'], $data['programs']->all());
        $data = $controller->researchHandoffs(Request::create('/', 'GET', ['program' => 'Computer Science']))->getData();
        $this->assertSame(['Paper received'], $data['handoffs']->pluck('title')->all());
        DB::table('researches')->where('id', 1)->update(['program' => null]);
        $data = $controller->researchHandoffs(Request::create('/', 'GET', ['program' => 'Information Technology']))->getData();
        $this->assertSame(3, $data['handoffs']->total());
    }

    public function test_csv_export_respects_status_and_department(): void
    {
        $response = (new AdminController())->researchHandoffs(Request::create('/', 'GET', ['export' => 'csv', 'status' => 'received']));
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('Paper received', $csv);
        $this->assertStringContainsString('Paper added', $csv);
        $this->assertStringNotContainsString('Paper pending', $csv);
        $this->assertStringNotContainsString('Private other department', $csv);
    }

    public function test_assignment_saves_category_and_year_without_a_file_and_filters_pending_tasks(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        auth()->user()->department = 'College of Computer Studies';
        DB::table('users')->insert(['id' => 8, 'role' => 'admin', 'department' => 'College of Computer Studies', 'is_research_coordinator' => true]);
        $request = Request::create('/', 'POST', [
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
            'year_published' => 2026,
            'school_year' => '2026-2027', 'program' => 'Information Technology',
            'notes' => 'Prepare the journal and submit it to me for review.',
        ]);
        (new AdminController())->storeResearchHandoff($request);
        $handoff = \App\Models\ResearchHandoff::where('title', 'Information Technology Journals - AY 2026-2027')->firstOrFail();
        $this->assertSame(Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, $handoff->submission_category);
        $this->assertSame(2026, (int) $handoff->year_published);
        $this->assertSame('pending', $handoff->status);
        $this->assertSame('2026-2027', $handoff->school_year);
        $this->assertSame('Information Technology', $handoff->program);
        $this->assertNull($handoff->file_path);
        $this->assertNull($handoff->file_name);
        $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('public')->allFiles());
        $view = (new AdminController())->researchHandoffs(Request::create('/', 'GET', [
            'year' => 2026, 'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
        ]));
        $this->assertSame(1, $view->getData()['handoffs']->total());
        $this->assertSame($handoff->id, $view->getData()['handoffs']->first()->id);
    }

    public function test_preparation_table_shows_program_academic_year_and_stage_specific_modal_actions(): void
    {
        DB::table('research_handoffs')->where('department', 'BSIT')->delete();
        $cases = [
            ['draft', 'pending', Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'Information Technology', 'BSIT', 'Assigned', 'View Task'],
            ['draft', 'received', Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'Computer Science', 'BSCS', 'Received by Research Coordinator', 'View Task'],
            ['dean_review', 'received', Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, null, 'All Programs', 'Submitted to Dean', 'Review Submission'],
            ['dean_approved', 'received', Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'Information Technology', 'BSIT', 'Approved by Dean', 'Forward to Admin'],
            ['pending', 'received', Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, null, 'All Programs', 'Forwarded to Admin', 'View Details'],
        ];
        foreach ($cases as [$stage, $status, $category, $program]) {
            \App\Models\ResearchHandoff::create([
                'dean_id' => 7, 'department' => 'BSIT', 'title' => 'Task ' . $stage . ' ' . $status,
                'submission_category' => $category, 'program' => $program,
                'school_year' => '2026-2027', 'year_published' => 2026,
                'status' => $status, 'is_batch' => true, 'batch_stage' => $stage,
            ]);
        }
        $html = (new AdminController())->researchHandoffs(Request::create('/'))
            ->with('errors', new \Illuminate\Support\ViewErrorBag())->render();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $filterOptions = $xpath->query('//form[@id="dh-filters"]//select/option[1]');
        $this->assertSame(['All Academic Years', 'All Programs', 'All Categories', 'All Statuses'],
            array_map(fn ($option) => trim($option->textContent), iterator_to_array($filterOptions)));
        $this->assertSame(0, $xpath->query('//form[@id="dh-filters"]//input')->length);
        $this->assertSame('Clear', $xpath->query('//a[@id="dh-clear"]')->item(0)->textContent);
        $headers = $xpath->query('//table[contains(@class, "dh-preparation-table")]/thead/tr/th');
        $this->assertSame(['#', 'Journal Category', 'Program', 'Academic Year', 'Date Assigned', 'Status', 'Actions'],
            array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($headers)));

        $rows = $xpath->query('//table[contains(@class, "dh-preparation-table")]/tbody/tr');
        $this->assertCount(5, $rows);
        $expected = collect($cases)->keyBy(fn ($case) => $case[5]);
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            $case = $expected[trim($cells->item(5)->textContent)];
            $this->assertSame(Research::adminSubmissionCategories()[$case[2]], trim($cells->item(1)->textContent));
            $this->assertSame($case[4], trim($cells->item(2)->textContent));
            $this->assertSame('2026-2027', trim($cells->item(3)->textContent));
            $this->assertSame($case[6], trim($cells->item(6)->textContent));
            $this->assertTrue($cells->item(6)->getElementsByTagName('a')->item(0)->hasAttribute('data-open-journal-batch'));
        }
    }
}
