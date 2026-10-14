<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class ResearchIdTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        Schema::create('research_pins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('research_id');
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_27_000001_create_research_views_table.php'))->up();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->admin = User::create(['name' => 'Research Admin', 'email' => 'research-id@example.test',
            'password' => 'password', 'role' => 'admin', 'is_active' => true, 'department' => 'College of Computer Studies']);
    }

    private function paper(array $attributes = []): Research
    {
        return Research::create($attributes + ['title' => 'Research Paper', 'abstract' => 'Research abstract.',
            'author_name' => 'Paper Author', 'department' => $this->admin->department,
            'type' => 'Quantitative', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'status' => Research::STATUS_APPROVED, 'year_published' => now('Asia/Manila')->year,
            'school_year' => now('Asia/Manila')->year.'-'.(now('Asia/Manila')->year + 1), 'user_id' => $this->admin->id]);
    }

    public function test_ids_are_unique_automatic_and_included_in_serialized_records(): void
    {
        $papers = collect(range(1, 3))->map(fn () => $this->paper());
        $this->assertSame(['RES-0001', 'RES-0002', 'RES-0003'], $papers->pluck('research_id')->all());
        $this->assertSame('RES-0001', $papers->first()->fresh()->toArray()['research_id']);
        $replica = $papers->first()->replicate();
        $this->assertNull($replica->research_id);
        $replica->save();
        $this->assertSame('RES-0004', $replica->research_id);
        $large = new Research;
        $large->setRawAttributes(['id' => 10000]);
        $this->assertSame('RES-10000', $large->research_id);
    }

    public function test_existing_records_get_ids_without_changing_any_stored_values(): void
    {
        DB::table('researches')->insert(['id' => 25, 'title' => 'Legacy Paper', 'status' => Research::STATUS_REJECTED,
            'created_at' => '2024-01-02 03:04:05', 'updated_at' => '2024-02-03 04:05:06', 'deleted_at' => '2024-03-04 05:06:07']);
        $before = (array) DB::table('researches')->where('id', 25)->first();
        $existing = Research::withTrashed()->findOrFail(25);
        $this->assertSame('RES-0025', $existing->research_id);
        $this->assertSame($before, (array) DB::table('researches')->where('id', 25)->first());
        $this->assertSame('RES-0026', $this->paper()->research_id);
    }

    public function test_soft_and_permanently_deleted_ids_are_not_reused_and_restoring_keeps_the_id(): void
    {
        $one = $this->paper();
        $two = $this->paper();
        $one->delete();
        $two->forceDelete();
        $this->assertSame('RES-0003', $this->paper()->research_id);
        $one->restore();
        $this->assertSame('RES-0001', $one->fresh()->research_id);
    }

    public function test_updates_preserve_ids_and_mass_assignment_cannot_override_them(): void
    {
        $paper = $this->paper(['research_id' => 'RES-9999']);
        $paper->update(['title' => 'Renamed Paper', 'status' => Research::STATUS_ARCHIVED, 'research_id' => 'RES-9999']);
        $this->assertSame('RES-0001', $paper->fresh()->research_id);
        $this->assertSame('Renamed Paper', $paper->fresh()->title);
    }

    public function test_direct_public_id_edits_are_rejected(): void
    {
        $paper = $this->paper();
        $this->expectException(\LogicException::class);
        $paper->research_id = 'RES-9999';
    }

    public function test_changing_the_underlying_permanent_number_is_rejected(): void
    {
        $paper = $this->paper();
        $paper->id = 9999;
        $this->expectException(\LogicException::class);
        $paper->save();
    }

    public function test_id_search_is_case_insensitive_and_preserves_publication_and_department_scope(): void
    {
        $paper = $this->paper(['title' => 'Target Paper']);
        $private = $this->paper(['title' => 'Private Paper', 'status' => Research::STATUS_DRAFT]);
        $foreign = $this->paper(['title' => 'Another Department', 'department' => 'College of Education']);
        $this->assertSame([$paper->id], Research::approved()->search('  res-0001  ')->pluck('id')->all());
        $this->get(route('home', ['search' => $paper->research_id, 'field' => 'research_id']))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->pluck('id')->all() === [$paper->id])
            ->assertDontSee('Research ID:');
        $this->get(route('home', ['search' => $private->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->isEmpty());
        $this->actingAs($this->admin)->get(route('admin.researches', ['search' => $paper->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->pluck('id')->all() === [$paper->id]);
        $this->admin->update(['is_department_dean' => true]);
        $this->get(route('admin.researches', ['search' => $foreign->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->isEmpty());
        $this->assertSame([], Research::where('department', $this->admin->department)
            ->where(fn ($q) => $q->where('title', 'not matching')->orWhereResearchId($foreign->research_id))->pluck('id')->all());
        $this->admin->update(['is_department_dean' => false, 'is_research_coordinator' => true]);
        $paper->update(['coordinator_id' => $this->admin->id]);
        $this->get(route('admin.coordinator.archive', ['search' => $paper->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->pluck('id')->all() === [$paper->id])->assertDontSee('Research ID:');
        $this->get(route('admin.coordinator.archive', ['search' => $foreign->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->isEmpty());
    }

    public function test_admin_management_and_reports_include_the_permanent_id(): void
    {
        $paper = $this->paper();
        $this->get(route('research.show', $paper))->assertOk()->assertDontSee($paper->research_id);
        $this->actingAs($this->admin)->get(route('admin.researches'))->assertOk()
            ->assertSee('Research ID: '.$paper->research_id)->assertSee('researchId', false);
        $this->actingAs($this->admin)->get(route('admin.research.show', $paper))->assertOk()->assertSee($paper->research_id);
        $this->get(route('admin.reports'))->assertOk()->assertSee($paper->research_id);
        $excel = $this->get(route('admin.reports.export.excel'))->assertOk()->assertSee('Research ID')->assertSee($paper->research_id);
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($excel->getContent()));
        $pdf = $this->get(route('admin.reports.export.pdf'))->assertOk();
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf->getContent(), $streams);
        $text = implode("\n", array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $streams[1]));
        $this->assertStringContainsString($paper->research_id, $text);
    }

    public static function readers(): array
    {
        return [
            'guest' => [null],
            'admin' => [['role' => 'admin']],
            'dean' => [['role' => 'admin', 'is_department_dean' => true]],
            'coordinator' => [['role' => 'admin', 'is_research_coordinator' => true]],
            'student' => [['role' => 'user']],
            'faculty' => [['role' => 'researcher']],
        ];
    }

    #[DataProvider('readers')]
    public function test_published_pages_and_viewer_hide_ids_for_every_reader(?array $attributes): void
    {
        if ($attributes !== null) {
            $this->actingAs(User::create($attributes + ['name' => 'Reader', 'email' => 'reader@example.test',
                'password' => 'password', 'department' => $this->admin->department, 'is_active' => true]));
        }
        $paper = $this->paper();
        $batch = ResearchHandoff::create(['title' => 'Published Batch', 'department' => $paper->department,
            'is_batch' => true, 'batch_stage' => Research::STATUS_APPROVED, 'batch_published_at' => now(),
            'school_year' => $paper->school_year, 'dean_id' => $this->admin->id, 'coordinator_id' => $this->admin->id]);
        $paper->update(['research_handoff_id' => $batch->id]);

        foreach ([route('home'), route('home', ['search' => 'Research Paper']), route('research.show', $paper),
            route('research.department', $paper->department), route('research.journal-batch', $batch)] as $url) {
            $this->get($url)->assertOk()->assertSee($paper->title)
                ->assertDontSee($paper->research_id)->assertDontSee('Research ID')->assertDontSee('data-research-id', false);
        }

        $viewer = view('research.protected-viewer', ['research' => $paper, 'signedUrl' => route('research.streamPdf', $paper),
            'adminMode' => auth()->user()?->isAdmin() ?? false])->render();
        $this->assertStringContainsString($paper->title, $viewer);
        $this->assertStringNotContainsString($paper->research_id, $viewer);
        $this->assertStringNotContainsString('Research ID', $viewer);
        $this->assertStringContainsString(json_encode(route('research.streamPdf', $paper)), $viewer);
        $this->assertSame('RES-0001', $paper->fresh()->research_id);
    }

    public function test_dean_management_analytics_and_exports_hide_ids_without_losing_records(): void
    {
        $paper = $this->paper();
        $this->admin->update(['is_department_dean' => true]);
        $this->actingAs($this->admin);
        foreach (['admin.researches', 'admin.dashboard', 'admin.reports'] as $route) {
            $this->get(route($route))->assertOk()->assertSee($paper->title)
                ->assertDontSee($paper->research_id)->assertDontSee('Research ID');
        }
        $this->get(route('admin.research.show', $paper))->assertOk()
            ->assertSee($paper->title)->assertDontSee($paper->research_id);
        $excel = $this->get(route('admin.reports.export.excel'))->assertOk()
            ->assertSee($paper->title)->assertDontSee('Research ID')->assertDontSee($paper->research_id);
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($excel->getContent()));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('s', 'urn:schemas-microsoft-com:office:spreadsheet');
        $this->assertSame(6, $xpath->query('//s:Column')->length);
        $pdf = $this->get(route('admin.reports.export.pdf'))->assertOk();
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf->getContent(), $streams);
        $text = implode("\n", array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $streams[1]));
        $this->assertStringContainsString($paper->title, $text);
        $this->assertStringNotContainsString($paper->research_id, $text);
    }

    public function test_coordinator_archive_hides_ids_and_preserves_search_and_access_rules(): void
    {
        $this->admin->update(['is_research_coordinator' => true]);
        $paper = $this->paper(['coordinator_id' => $this->admin->id]);
        $this->actingAs($this->admin)->get(route('admin.coordinator.archive'))->assertOk()
            ->assertSee($paper->title)->assertDontSee($paper->research_id)->assertDontSee('Research ID');
        $this->get(route('admin.research.show', $paper))->assertForbidden();
        $this->get(route('admin.coordinator.archive', ['search' => $paper->research_id]))->assertOk()
            ->assertViewHas('researches', fn ($papers) => $papers->pluck('id')->all() === [$paper->id])
            ->assertDontSee('Research ID:');
    }

    public function test_admin_document_viewer_hides_the_id_and_preserves_the_signed_stream(): void
    {
        $paper = $this->paper(['file_path' => 'research/paper.pdf']);
        Storage::fake('public');
        Storage::disk('public')->put($paper->file_path, 'test document');
        $this->actingAs($this->admin)->get(route('admin.research.view-file', $paper))->assertOk()
            ->assertSee($paper->title)->assertDontSee($paper->research_id)
            ->assertViewHas('signedUrl', fn ($url) => \Illuminate\Http\Request::create($url)->hasValidSignature());
    }
}
