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
