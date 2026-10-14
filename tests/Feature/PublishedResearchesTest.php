<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\User;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class PublishedResearchesTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->admin = User::create([
            'name' => 'Publication Admin', 'email' => 'publication-admin@example.test',
            'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function research(array $attributes = []): Research
    {
        return Research::create($attributes + [
            'title' => 'Repository Record '.(Research::count() + 1),
            'author_name' => 'Journal Author', 'authors' => ['Journal Author'],
            'department' => 'College of Computer Studies', 'school_year' => '2025-2026',
            'year_published' => 2026, 'status' => Research::STATUS_APPROVED,
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
        ]);
    }

    public function test_researches_lists_only_published_records(): void
    {
        $published = $this->research();
        foreach ([Research::STATUS_DRAFT, Research::STATUS_DEAN_REVIEW,
            Research::STATUS_DEAN_APPROVED, Research::STATUS_PENDING,
            Research::STATUS_REJECTED, Research::STATUS_ARCHIVED] as $status) {
            $this->research(['status' => $status, 'forwarded_to_admin_at' => now()]);
        }
        $deleted = $this->research();
        $deleted->delete();

        $this->actingAs($this->admin)->get(route('admin.researches'))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->total() === 1 && $rows->first()->is($published))
            ->assertSee('Published Researches')->assertSee('Journal Batches for Publication')
            ->assertDontSee('class="mr-add-btn"', false)
            ->assertDontSee('id="mr-status"', false)->assertDontSee('Journals Ready to Publish');

        $this->get(route('admin.researches', ['status' => 'approved']))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->total() === 1);
    }

    public function test_old_unpublished_status_links_redirect_and_preserve_other_filters(): void
    {
        $filters = ['search' => 'Journal Author', 'department' => 'College of Computer Studies',
            'school_year' => '2025-2026', 'year' => '2026'];
        foreach (['pending', 'draft', 'rejected', 'archived', 'invalid'] as $status) {
            $this->actingAs($this->admin)->get(route('admin.researches', $filters + ['status' => $status, 'page' => 2]))
                ->assertRedirect(route('admin.researches', $filters));
        }
    }

    public function test_filters_apply_to_published_records_and_dean_scope_is_preserved(): void
    {
        $selected = $this->research(['title' => 'Technology Journal']);
        $this->research(['title' => 'Technology Journal in another college', 'department' => 'College of Business Administration']);
        $this->research(['title' => 'Technology Journal in another year', 'school_year' => '2024-2025']);
        $this->research(['title' => 'Technology Journal in another publication year', 'year_published' => 2025]);
        $this->research(['title' => 'Unrelated published record', 'author_name' => 'Other Author']);
        $this->research(['title' => 'Technology Journal awaiting publication', 'status' => Research::STATUS_PENDING]);

        $this->actingAs($this->admin)->get(route('admin.researches', [
            'search' => 'Technology', 'department' => $selected->department,
            'school_year' => $selected->school_year, 'year' => $selected->year_published,
        ]))->assertOk()->assertViewHas('researches', fn ($rows) => $rows->total() === 1 && $rows->first()->is($selected));

        $this->get(route('admin.researches', ['search' => 'Other Author']))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->total() === 1 && $rows->first()->author_name === 'Other Author');

        $dean = User::create(['name' => 'CCS Dean', 'email' => 'publication-dean@example.test',
            'password' => 'password', 'role' => 'admin', 'department' => $selected->department,
            'is_department_dean' => true, 'is_active' => true]);
        $this->actingAs($dean)->get(route('admin.researches', ['department' => 'College of Business Administration']))
            ->assertOk()->assertViewHas('researches', fn ($rows) => $rows->total() === 4
                && $rows->every(fn ($record) => $record->department === $dean->department && $record->status === Research::STATUS_APPROVED));
    }

    public function test_legacy_admin_publication_shortcuts_open_the_batch_queue(): void
    {
        $this->actingAs($this->admin)->get(route('admin.add-research'))->assertRedirect(route('admin.journal-batches'));
        $this->get(route('admin.research-handoffs'))->assertRedirect(route('admin.journal-batches'));
    }
}
