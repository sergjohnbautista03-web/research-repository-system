<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\Semester;
use App\Models\User;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class DeanResearchReportsTest extends TestCase
{
    use JournalWorkflowSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->actingAs(User::create(['name' => 'Reports Dean', 'email' => 'dean-reports@example.test',
            'password' => 'password', 'role' => 'admin', 'is_department_dean' => true,
            'department' => 'College of Computer Studies', 'is_active' => true]));
    }

    private function record(string $title, array $attributes = []): Research
    {
        return Research::create($attributes + ['title' => $title, 'author_name' => 'Author',
            'department' => 'College of Computer Studies', 'status' => Research::STATUS_APPROVED,
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
            'school_year' => '2023-2024', 'year_published' => 2026]);
    }

    public function test_empty_report_has_default_department_programs_academic_years_and_journal_categories(): void
    {
        $response = $this->get(route('admin.reports'))->assertOk()
            ->assertSee('Computer Science')->assertSee('Information Technology')
            ->assertSee('Faculty Research Journal')->assertSee('Student Research Journal')
            ->assertDontSee('<option value="research"', false)->assertDontSee('<option value="journal"', false)
            ->assertSee('<th scope="col">Academic Year</th>', false)
            ->assertDontSee('<th scope="col">Year</th>', false);
        $this->assertSame(['Computer Science', 'Information Technology'], $response->viewData('programs')->all());
        $this->assertSame(['2027-2028', '2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023'],
            $response->viewData('schoolYears')->all());
        $this->assertSame(Research::adminSubmissionCategories(), $response->viewData('categories'));
    }

    public function test_report_and_print_use_saved_academic_year_with_linked_semester_fallback(): void
    {
        $semester = Semester::create(['school_year' => '2024-2025', 'semester' => '1st', 'is_active' => false]);
        $saved = $this->record('Saved AY takes precedence', ['semester_id' => $semester->id]);
        $legacy = $this->record('Semester AY fallback', ['school_year' => null, 'semester_id' => $semester->id]);
        $unassigned = $this->record('No academic year', ['school_year' => null]);
        $response = $this->get(route('admin.reports'))->assertOk();
        $rows = $response->viewData('allRows')->keyBy('id');
        $this->assertSame('2023-2024', $rows[$saved->id]->academicYearLabel());
        $this->assertSame('2024-2025', $rows[$legacy->id]->academicYearLabel());
        $this->assertSame('Not set', $rows[$unassigned->id]->academicYearLabel());
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '<td>2023-2024</td>'));
        $this->assertSame(2, substr_count($html, '<td>2024-2025</td>'));
        $this->assertStringNotContainsString('<td>2026</td>', $html);

        foreach (['2023-2024' => $saved, '2024-2025' => $legacy] as $year => $expected) {
            foreach ([[], ['semester' => '1st']] as $extra) {
                $this->get(route('admin.reports', ['school_year' => $year] + $extra))->assertOk()
                    ->assertViewHas('allRows', fn ($records) => $records->pluck('id')->all() === [$expected->id])
                    ->assertViewHas('researches', fn ($records) => $records->total() === 1);
            }
        }
    }

    public function test_search_matches_title_authors_and_program_with_existing_filters_and_print_data(): void
    {
        $saved = $this->record('Robotics classroom study', ['authors' => ['Ana Cruz', 'José Reyes'],
            'author_name' => 'Old author value', 'program' => 'Information Technology']);
        $this->record('Excluded Robotics year', ['school_year' => '2024-2025', 'program' => 'Information Technology']);
        $this->record('Excluded Robotics program', ['program' => 'Computer Science']);
        $this->record('Excluded Robotics category', ['program' => 'Information Technology',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL]);
        $this->record('Excluded Robotics department', ['department' => 'Other College', 'program' => 'Information Technology']);
        $this->record('Excluded Robotics draft', ['status' => Research::STATUS_DRAFT, 'program' => 'Information Technology']);
        $filters = ['school_year' => '2023-2024', 'program' => 'Information Technology',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL];
        foreach (['Robotics', '  ROBOTICS  ', 'Ana Cruz', 'JOSÉ REYES', 'information technology'] as $search) {
            $response = $this->get(route('admin.reports', $filters + ['search' => $search]))->assertOk()
                ->assertSee('Search by research title, author, or program...')
                ->assertViewHas('allRows', fn ($records) => $records->pluck('id')->all() === [$saved->id])
                ->assertViewHas('researches', fn ($records) => $records->total() === 1)
                ->assertViewHas('studentCount', 1)->assertViewHas('facultyCount', 0);
            $this->assertSame(trim($search), $response->viewData('filters')['search']);
            $this->assertSame(2, substr_count($response->getContent(), $saved->title));
            $response->assertDontSee('Excluded Robotics');
        }
        $legacy = $this->record('Legacy author and course', ['author_name' => 'Maya Santos', 'course' => 'Historical Program']);
        foreach (['Maya Santos', 'Historical Program'] as $search) {
            $this->get(route('admin.reports', compact('search')))->assertOk()
                ->assertViewHas('allRows', fn ($records) => $records->pluck('id')->all() === [$legacy->id]);
        }
        foreach (['no match', 'Old author value', '%'] as $search) {
            $this->get(route('admin.reports', compact('search')))->assertOk()
                ->assertViewHas('allRows', fn ($records) => $records->isEmpty())
                ->assertViewHas('studentCount', 0)->assertViewHas('facultyCount', 0);
        }
    }

    public function test_search_filters_all_pages_and_print_includes_every_matching_record(): void
    {
        foreach (range(1, 51) as $index) {
            $this->record('Matching study '.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
        }
        $this->record('Unrelated study');
        $this->get(route('admin.reports', ['search' => 'Matching study', 'page' => 2]))->assertOk()
            ->assertViewHas('allRows', fn ($records) => $records->count() === 51)
            ->assertViewHas('studentCount', 51)
            ->assertViewHas('researches', fn ($records) => $records->total() === 51 && $records->count() === 1)
            ->assertSee('search=Matching%20study', false);
    }

    public function test_program_and_category_filters_keep_results_totals_and_print_department_scoped(): void
    {
        $saved = $this->record('Included legacy program', ['course' => 'Historical CCS Program']);
        $this->record('Different program', ['program' => 'Computer Science']);
        $this->record('Different category', ['course' => 'Historical CCS Program',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL]);
        $this->record('Other department', ['department' => 'Other College', 'program' => 'Secret Program']);
        $this->record('Unpublished', ['program' => 'Private Program', 'status' => Research::STATUS_DRAFT]);
        $response = $this->get(route('admin.reports', ['school_year' => '2023-2024',
            'program' => 'Historical CCS Program', 'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL]))->assertOk()
            ->assertViewHas('allRows', fn ($records) => $records->pluck('id')->all() === [$saved->id])
            ->assertViewHas('studentCount', 1)->assertViewHas('facultyCount', 0)
            ->assertDontSee('Secret Program')->assertDontSee('Private Program');
        $this->assertSame(['Computer Science', 'Historical CCS Program', 'Information Technology'],
            $response->viewData('programs')->all());
    }
}
