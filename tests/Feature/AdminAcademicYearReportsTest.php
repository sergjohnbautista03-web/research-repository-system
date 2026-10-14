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
