<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CoordinatorReviewWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_02_000001_create_notifications_table.php'))->up();
        (require database_path('migrations/2026_10_02_000002_create_notification_reads_table.php'))->up();
        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->unsignedBigInteger('coordinator_id')->nullable();
            $table->string('status');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('research_handoffs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('status');
            $table->unsignedBigInteger('dean_id');
            $table->unsignedBigInteger('research_id')->nullable();
            $table->unsignedBigInteger('received_by_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    private function loginAs(bool $coordinator): void
    {
        $user = new User();
        $user->forceFill(['id' => $coordinator ? 7 : 1, 'role' => 'admin', 'department' => 'BSIT', 'is_research_coordinator' => $coordinator, 'is_department_dean' => false]);
        $this->actingAs($user);
    }

    private function research(string $status): Research
    {
        return Research::create(['title' => 'Workflow Paper', 'department' => 'BSIT', 'coordinator_id' => 7, 'status' => $status])->setRelation('semester', null);
    }

    private function deanUpdates(User $dean): array
    {
        $request = Request::create('/admin/dean-notifications');
        $request->setUserResolver(fn () => $dean);
        return (new \App\Http\Controllers\DeanNotificationController())->index($request)->getData(true)['notifications'];
    }

    public function test_dean_tracks_receipt_submission_and_publication_with_scoped_notifications(): void
    {
        $dean = (new User())->forceFill(['id' => 4, 'role' => 'admin', 'is_department_dean' => true, 'department' => 'BSIT']);
        $handoff = \App\Models\ResearchHandoff::create([
            'title' => 'Dean Submitted Paper', 'department' => 'BSIT', 'dean_id' => 4, 'status' => 'pending',
        ]);
        $this->assertSame('Forwarded', $handoff->workflowLabel());
        $this->assertCount(0, $this->deanUpdates($dean));
        $controller = new AdminController();
        $this->loginAs(true);
        $controller->confirmResearchHandoffReceived($handoff);
        $receivedAt = $handoff->fresh()->received_at;
        $this->assertSame('Received by Research Coordinator', $handoff->fresh()->workflowLabel());
        $updates = $this->deanUpdates($dean);
        $this->assertCount(1, $updates);
        $this->assertSame('The Research Coordinator has received and reviewed your submitted research titled ‘Dean Submitted Paper’.', $updates[0]['message']);
        $this->travel(1)->minutes();
        $controller->confirmResearchHandoffReceived($handoff->fresh());
        $this->assertTrue($receivedAt->equalTo($handoff->fresh()->received_at));
        $this->assertCount(1, $this->deanUpdates($dean));

        $research = $this->research('draft');
        $handoff->update(['research_id' => $research->id, 'status' => 'added']);
        $this->assertSame('Received by Research Coordinator', $handoff->fresh()->workflowLabel());
        $controller->submitCoordinatorSummary($research);
        $this->assertSame('Submitted to Admin for Review', $handoff->fresh()->workflowLabel());
        $this->assertSame(1, \App\Models\ResearchHandoff::forWorkflowStage('submitted')->count());
        $this->assertSame(0, \App\Models\ResearchHandoff::forWorkflowStage('received')->count());

        $this->loginAs(false);
        $controller->approveResearch($research);
        $this->assertSame('Published', $handoff->fresh()->workflowLabel());
        $this->assertSame(1, \App\Models\ResearchHandoff::forWorkflowStage('published')->count());
        $updates = $this->deanUpdates($dean);
        $this->assertCount(2, $updates);
        $this->assertSame('Published: ‘Workflow Paper’ is now available in the UBE Research Repository.', $updates[0]['message']);
        $this->assertSame(route('research.show', $research), $updates[0]['url']);

        $otherDean = (new User())->forceFill(['id' => 5, 'role' => 'admin', 'is_department_dean' => true, 'department' => 'OTHER']);
        $this->assertCount(0, $this->deanUpdates($otherDean));
        $sameDepartmentDean = (new User())->forceFill(['id' => 6, 'role' => 'admin', 'is_department_dean' => true, 'department' => 'BSIT']);
        $this->assertCount(1, $this->deanUpdates($sameDepartmentDean));
    }

    public function test_dean_updates_deny_non_deans_and_unassigned_deans(): void
    {
        foreach ([['role' => 'user'], ['role' => 'admin', 'is_research_coordinator' => true], ['role' => 'admin', 'is_department_dean' => true, 'department' => null]] as $attributes) {
            $user = (new User())->forceFill(array_merge(['id' => 8, 'department' => 'BSIT'], $attributes));
            try {
                $this->deanUpdates($user);
                $this->fail('Dean notifications must be scoped to an assigned dean.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_prepare_submit_return_resubmit_publish_and_public_visibility(): void
    {
        $controller = new AdminController();
        $research = $this->research('draft');
        $this->assertSame('Preparing', $research->coordinatorStageLabel());
        $this->assertSame(0, Research::approved()->count());

        $this->loginAs(true);
        $controller->submitCoordinatorSummary($research);
        $this->assertSame('pending', $research->fresh()->status);
        $this->assertSame(0, Research::approved()->count());

        $this->loginAs(false);
        $this->assertCount(1, $controller->reviewNotifications()->getData()['researches']);
        $this->assertStringContainsString('Research Ready for Review', $controller->reviewNotifications()->render());
        $this->assertStringContainsString('Workflow Paper', $controller->reviewNotifications()->render());
        $controller->rejectResearch(Request::create('/', 'POST', ['reason' => 'Incorrect author name']), $research);
        $this->assertSame('rejected', $research->fresh()->status);
        $this->assertSame('Incorrect author name', $research->fresh()->rejection_reason);
        $this->assertSame(0, Research::approved()->count());
        $this->assertCount(0, $controller->reviewNotifications()->getData()['researches']);

        $this->loginAs(true);
        $controller->submitCoordinatorSummary($research);
        $this->loginAs(false);
        $this->assertCount(1, $controller->reviewNotifications()->getData()['researches']);
        $controller->approveResearch($research);
        $this->assertSame('Published', $research->fresh()->coordinatorStageLabel());
        $this->assertSame(1, Research::approved()->count());
        $this->assertCount(0, $controller->reviewNotifications()->getData()['researches']);
    }

    public function test_return_requires_nonblank_remarks(): void
    {
        $this->loginAs(false);
        $research = $this->research('pending');
        try {
            (new AdminController())->rejectResearch(Request::create('/', 'POST', ['reason' => '   ']), $research);
            $this->fail('Blank remarks must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
            $this->assertSame('pending', $research->fresh()->status);
        }
    }

    public function test_coordinator_cannot_publish_or_access_admin_notifications(): void
    {
        $this->loginAs(true);
        $research = $this->research('pending');
        foreach (['approveResearch', 'reviewNotifications'] as $method) {
            try {
                (new AdminController())->{$method}($research);
                $this->fail('Admin-only action should be denied.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_admin_cannot_publish_draft_or_return_published_research(): void
    {
        $this->loginAs(false);
        foreach (['draft', 'approved'] as $status) {
            $research = $this->research($status);
            try {
                if ($status === 'draft') {
                    (new AdminController())->approveResearch($research);
                } else {
                    (new AdminController())->rejectResearch(Request::create('/', 'POST', ['reason' => 'Correction']), $research);
                }
                $this->fail('Invalid transition should be denied.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertSame($status, $research->fresh()->status);
            }
        }
    }
}
