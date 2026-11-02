<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\Semester;
use App\Models\User;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class AdminAcademicYearReportsTest extends TestCase
{
    use JournalWorkflowSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->actingAs(User::create(['name' => 'Reports Admin', 'email' => 'academic-report@example.test',
            'password' => 'password', 'role' => 'admin', 'is_active' => true]));
    }

    private function record(string $title, array $attributes = []): Research
    {
        return Research::create($attributes + ['title' => $title, 'author_name' => 'Author',
            'department' => 'College of Computer Studies', 'status' => Research::STATUS_APPROVED,
            'school_year' => '2025-2026', 'year_published' => 2026]);
    }

    public function test_academic_year_options_are_ranges_in_descending_order(): void
    {
        $this->record('Latest academic year', ['school_year' => '2027-2028']);
        $this->record('Old academic year', ['school_year' => '2022-2023']);
        $response = $this->get(route('admin.reports'))->assertOk()
            ->assertSee('All Academic Years')->assertSee('name="school_year"', false)
            ->assertDontSee('name="year"', false)->assertDontSee('Academic Year / Year');
        $years = $response->viewData('years')->all();
        $sorted = $years;
        rsort($sorted);
        $this->assertSame($sorted, $years);
        $this->assertContains('2027-2028', $years);
        $this->assertContains('2022-2023', $years);
        foreach ($years as $year) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{4}$/', $year);
        }
    }

    public function test_report_uses_saved_academic_year_and_falls_back_to_linked_semester(): void
    {
        $semester = Semester::create(['school_year' => '2025-2026', 'semester' => '1st', 'is_active' => false]);
        $saved = $this->record('Saved academic year', ['year_published' => 2024]);
        $legacy = $this->record('Linked semester year', ['school_year' => null, 'semester_id' => $semester->id]);
        $this->record('Saved year takes precedence', ['school_year' => '2024-2025', 'semester_id' => $semester->id]);
        $this->record('Same publication year different academic year', ['school_year' => '2026-2027']);
        $this->record('Unpublished journal', ['status' => Research::STATUS_PENDING]);
        $this->record('Other college', ['department' => 'College of Business Administration']);
        $this->record('No recorded academic year', ['school_year' => null]);

        $response = $this->get(route('admin.reports', ['school_year' => '2025-2026', 'department' => $saved->department]))->assertOk();
        $this->assertEqualsCanonicalizing([$saved->id, $legacy->id], $response->viewData('researches')->pluck('id')->all());
        $this->assertCount(2, $response->viewData('printResearches'));
        $this->assertSame(2, $response->viewData('departmentTotals')->get($saved->department));
        $legacyPreview = $response->viewData('reportFilterRows')->where('department', $saved->department)->pluck('year');
        $this->assertSame(2, $legacyPreview->filter(fn ($year) => $year === '2025-2026')->count());
    }

    public function test_exports_follow_selected_academic_years_and_department(): void
    {
        $selected = $this->record('Included selected year');
        $this->record('Included second year', ['school_year' => '2024-2025']);
        $this->record('Excluded academic year', ['school_year' => '2023-2024']);
        $this->record('Excluded department', ['department' => 'College of Business Administration']);
        $this->record('Excluded pending', ['status' => Research::STATUS_PENDING]);
        $filters = ['school_years' => ['2025-2026', '2024-2025'], 'department' => $selected->department];
        $this->get(route('admin.reports.export.excel', $filters))->assertOk()
            ->assertSee('Included selected year')->assertSee('Included second year')
            ->assertSee('2025-2026, 2024-2025')->assertSee('Academic Year')
            ->assertDontSee('Excluded academic year')->assertDontSee('Excluded department')->assertDontSee('Excluded pending');
        $pdf = $this->get(route('admin.reports.export.pdf', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get(route('admin.reports.export.excel', ['school_year' => '2025-2026', 'department' => $selected->department]))
            ->assertOk()->assertSee('Included selected year')->assertDontSee('Included second year');
    }

    public function test_department_limits_program_options_using_catalog_and_saved_records(): void
    {
        $this->record('Historical course', ['course' => 'Historical CCS Program']);
        $this->record('Business journal', ['department' => 'College of Accountancy and Business Education', 'program' => 'Accountancy']);
        $this->record('Unpublished hidden program', ['program' => 'Private Draft Program', 'status' => Research::STATUS_DRAFT]);
        $response = $this->get(route('admin.reports', ['department' => 'College of Computer Studies']))->assertOk()
            ->assertSee('id="reportProgram"', false)->assertSee('id="reportExportProgram"', false);
        $this->assertEqualsCanonicalizing(['Computer Science', 'Information Technology', 'Historical CCS Program'], $response->viewData('programs')->all());
        $this->assertNotContains('Accountancy', $response->viewData('programs'));
        $this->assertNotContains('Private Draft Program', $response->viewData('programs'));
        $this->get(route('admin.reports', ['department' => 'College of Accountancy and Business Education']))
            ->assertOk()->assertViewHas('programs', fn ($options) => $options->contains('Accountancy') && ! $options->contains('Computer Science'));
    }

    public function test_search_filters_titles_authors_and_research_ids_in_results_totals_and_exports(): void
    {
        $selected = $this->record('Robotics classroom study', [
            'author_name' => 'Maya Santos', 'program' => 'Information Technology',
        ]);
        $this->record('Unrelated finance study', ['author_name' => 'Another Author']);
        $this->record('Excluded Robotics program', ['program' => 'Computer Science']);
        $this->record('Excluded Robotics year', ['program' => 'Information Technology', 'school_year' => '2024-2025']);
        $this->record('Excluded Robotics department', ['program' => 'Information Technology', 'department' => 'Other College']);
        $this->record('Excluded Robotics draft', ['program' => 'Information Technology', 'status' => Research::STATUS_DRAFT]);
        $filters = ['department' => $selected->department, 'program' => 'Information Technology',
            'school_year' => '2025-2026'];

        foreach (['Robotics', 'Maya Santos', $selected->research_id, '  Maya Santos  '] as $search) {
            $response = $this->get(route('admin.reports', $filters + ['search' => $search]))->assertOk()
                ->assertSee('id="reportSearch"', false)
                ->assertViewHas('researches', fn ($rows) => $rows->total() === 1 && $rows->first()->is($selected))
                ->assertViewHas('printResearches', fn ($rows) => $rows->count() === 1 && $rows->first()->is($selected));
            $this->assertSame(1, $response->viewData('departmentTotals')->get($selected->department));
            $this->get(route('admin.reports.export.excel', $filters + ['search' => $search]))->assertOk()
                ->assertSee($selected->title)->assertDontSee('Excluded Robotics')->assertDontSee('Unrelated finance study');
        }
        $response = $this->get(route('admin.reports', ['search' => $selected->research_id]))->assertOk();
        $this->assertCount(1, $response->viewData('reportFilterRows'));
        $this->get(route('admin.reports.export.pdf', $filters + ['search' => 'Maya Santos']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.reports', ['search' => 'no matching paper']))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('reportFilterRows', fn ($rows) => $rows->isEmpty())
            ->assertSee('No research found matching your search and filters.');
    }

    public function test_dashboard_summary_counts_use_academic_years_and_linked_semester_fallback(): void
    {
        $semester = Semester::create(['school_year' => '2022-2023', 'semester' => '1st', 'is_active' => false]);
        $otherSemester = Semester::create(['school_year' => '2024-2025', 'semester' => '1st', 'is_active' => false]);
        foreach ([
            ['2022-2023', null, 2026, 10, 2],
            ['2022-2023', null, 2025, 5, 1],
            [null, $semester->id, 2024, 7, 3],
            ['', $otherSemester->id, 2022, 4, 2],
            ['2024-2025', $semester->id, 2023, 9, 4],
            [null, null, 2022, 3, 1],
        ] as $index => [$year, $semesterId, $published, $views, $copies]) {
            $this->record('Dashboard year paper '.$index, ['school_year' => $year, 'semester_id' => $semesterId,
                'year_published' => $published, 'view_count' => $views, 'citation_copy_count' => $copies]);
        }
        $response = $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<label for="summaryViewsYear">Academic Year</label>', false)
            ->assertSee('All Academic Years');
        $summary = $response->viewData('analyticsSummary');
        $this->assertEquals(6, $summary['total_research_papers']);
        $this->assertEquals(38, $summary['total_views']);
        $this->assertEquals(13, $summary['total_copy_citations']);
        $this->assertEquals(3, $summary['papers_by_year']['2022-2023']);
        $this->assertEquals(22, $summary['views_by_year']['2022-2023']);
        $this->assertEquals(6, $summary['citations_by_year']['2022-2023']);
        $this->assertEquals(2, $summary['papers_by_year']['2024-2025']);
        $this->assertEquals(13, $summary['views_by_year']['2024-2025']);
        $this->assertEquals(6, $summary['citations_by_year']['2024-2025']);
        $this->assertArrayNotHasKey(2026, $summary['views_by_year']->all());
        foreach (['2022-2023', '2024-2025'] as $year) {
            $this->assertContains($year, $response->viewData('analyticsSummaryAcademicYears'));
        }
        $this->assertContains('2023-2024', $response->viewData('analyticsSummaryAcademicYears'));

        $this->record('Other department summary', ['school_year' => '2022-2023',
            'department' => 'Other College', 'view_count' => 100, 'citation_copy_count' => 100]);
        $this->actingAs(User::create(['name' => 'Summary Dean', 'email' => 'summary-dean@example.test',
            'password' => 'password', 'role' => 'admin', 'is_department_dean' => true,
            'department' => 'College of Computer Studies', 'is_active' => true]));
        $scoped = $this->get(route('admin.dashboard'))->assertOk()->viewData('analyticsSummary');
        $this->assertEquals(6, $scoped['total_research_papers']);
        $this->assertEquals(38, $scoped['total_views']);
        $this->assertEquals(6, $scoped['citations_by_year']['2022-2023']);
    }

    public function test_year_dropdowns_preserve_historical_choices_and_add_future_years_when_research_exists(): void
    {
        Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false]);
        Semester::create(['school_year' => '2027-2028', 'semester' => '1st', 'is_active' => false]);
        $this->record('Existing research', ['school_year' => '2025-2026']);

        $dropdowns = ['admin.reports' => 'years', 'admin.dashboard' => 'analyticsSummaryAcademicYears',
            'admin.researches' => 'schoolYears'];
        foreach ($dropdowns as $route => $key) {
            $this->get(route($route))->assertOk()
                ->assertViewHas($key, fn ($years) => $years->all() === ['2025-2026', '2024-2025', '2023-2024', '2022-2023']);
        }

        $new = $this->record('New academic year research', ['school_year' => '2026-2027']);
        foreach ($dropdowns as $route => $key) {
            $this->get(route($route))->assertOk()
                ->assertViewHas($key, fn ($years) => $years->all() === ['2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023']);
        }

        $this->record('Next academic year research', ['school_year' => '2027-2028']);
        foreach ($dropdowns as $route => $key) {
            $this->get(route($route))->assertOk()
                ->assertViewHas($key, fn ($years) => $years->all() === ['2027-2028', '2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023']);
        }
        $new->delete();
        $this->get(route('admin.reports'))->assertOk()
            ->assertViewHas('years', fn ($years) => ! $years->contains('2026-2027'));
    }

    public function test_program_filter_applies_to_report_totals_preview_and_exports(): void
    {
        $saved = $this->record('Included IT journal', ['program' => 'Information Technology']);
        $legacy = $this->record('Included legacy IT course', ['program' => '', 'course' => 'Information Technology']);
        $this->record('Excluded CS journal', ['program' => 'Computer Science', 'course' => 'Information Technology']);
        $this->record('Excluded other year', ['program' => 'Information Technology', 'school_year' => '2024-2025']);
        $this->record('Excluded other department', ['program' => 'Information Technology', 'department' => 'Other College']);
        $this->record('Excluded unpublished', ['program' => 'Information Technology', 'status' => Research::STATUS_PENDING]);
        $filters = ['department' => $saved->department, 'school_year' => '2025-2026', 'program' => 'Information Technology'];
        $response = $this->get(route('admin.reports', $filters))->assertOk();
        $this->assertEqualsCanonicalizing([$saved->id, $legacy->id], $response->viewData('researches')->pluck('id')->all());
        $this->assertCount(2, $response->viewData('printResearches'));
        $this->assertSame(2, $response->viewData('departmentTotals')->get($saved->department));
        $this->assertSame('Information Technology', $response->viewData('reportFilterRows')->first()['program']);
        $this->get(route('admin.reports.export.excel', $filters))->assertOk()
            ->assertSee('Included IT journal')->assertSee('Included legacy IT course')->assertDontSee('Excluded');
        $this->get(route('admin.reports.export.pdf', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.reports', $filters + ['page' => 2]))->assertOk()
            ->assertViewHas('printResearches', fn ($rows) => $rows->count() === 2);
    }
}
