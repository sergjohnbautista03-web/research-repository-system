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

class DeanHandoffsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('department');
            $table->boolean('is_research_coordinator')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('submission_category');
            $table->integer('year_published');
            $table->string('status')->default('draft');
            $table->softDeletes();
        });
        Schema::create('research_handoffs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('status');
            $table->string('submission_category')->nullable();
            $table->unsignedSmallInteger('year_published')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('dean_id')->nullable();
            $table->unsignedBigInteger('coordinator_id')->nullable();
            $table->unsignedBigInteger('research_id')->nullable();
            $table->timestamps();
        });
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
        $this->assertStringContainsString('Handoff Records', $html);
        $this->assertStringContainsString('Not yet assigned', $html);
        $this->assertStringNotContainsString('Private other department', $html);
    }

    public function test_combined_filters_keep_summary_counts_and_include_added_as_received(): void
    {
        $request = Request::create('/', 'GET', ['search' => 'added', 'year' => '2025', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'received']);
        $data = (new AdminController())->researchHandoffs($request)->getData();
        $this->assertSame(1, $data['handoffs']->total());
        $this->assertSame('Paper added', $data['handoffs']->first()->title);
        $this->assertSame(3, $data['totalCount']);
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

    public function test_submission_saves_category_and_year_and_filters_pending_handoffs(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        DB::table('users')->insert(['id' => 8, 'role' => 'admin', 'department' => 'BSIT', 'is_research_coordinator' => true]);
        $pdf = new \FPDF();
        $pdf->AddPage();
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('defended.pdf', $pdf->Output('S'));
        $request = Request::create('/', 'POST', [
            'title' => 'New defended paper',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
            'year_published' => 2026,
        ], [], ['file' => $file]);
        (new AdminController())->storeResearchHandoff($request);
        $handoff = \App\Models\ResearchHandoff::where('title', 'New defended paper')->firstOrFail();
        $this->assertSame(Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, $handoff->submission_category);
        $this->assertSame(2026, (int) $handoff->year_published);
        $this->assertSame('pending', $handoff->status);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($handoff->file_path);
        $view = (new AdminController())->researchHandoffs(Request::create('/', 'GET', [
            'year' => 2026, 'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
        ]));
        $this->assertSame(1, $view->getData()['handoffs']->total());
        $this->assertSame($handoff->id, $view->getData()['handoffs']->first()->id);
    }
}
