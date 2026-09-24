<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CoordinatorDepartmentScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['researches', 'research_handoffs'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('department');
                $table->unsignedBigInteger('coordinator_id')->nullable();
                $table->string('status');
                $table->softDeletes();
            });
            DB::table($name)->insert([
                ['id' => 1, 'department' => 'BSIT', 'coordinator_id' => 99, 'status' => 'pending'],
                ['id' => 2, 'department' => 'BSHM', 'coordinator_id' => 7, 'status' => 'pending'],
            ]);
        }

        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'admin', 'department' => 'BSIT', 'is_research_coordinator' => true]);
        $this->actingAs($user);
    }

    public function test_research_and_handoff_queries_exclude_other_departments_even_when_assigned_to_coordinator(): void
    {
        $controller = new AdminController();
        $researchScope = new \ReflectionMethod($controller, 'scopeCoordinatorResearchQuery');
        $handoffScope = new \ReflectionMethod($controller, 'coordinatorHandoffQuery');

        $this->assertSame([1], $researchScope->invoke($controller, Research::query())->pluck('id')->all());
        $this->assertSame([1], $handoffScope->invoke($controller)->pluck('id')->all());
    }

    public function test_department_monitoring_only_lists_assigned_department(): void
    {
        $view = (new AdminController())->coordinatorDepartmentMonitoring();
        $departments = $view->getData()['departments'];

        $this->assertCount(1, $departments);
        $this->assertSame(1, $departments->first()['research_total']);
        $this->assertSame(1, $departments->first()['handoff_total']);
    }

    public function test_direct_access_to_other_department_is_denied_even_when_assigned_to_coordinator(): void
    {
        $controller = new AdminController();
        $access = new \ReflectionMethod($controller, 'ensureCoordinatorResearchAccess');
        $access->invoke($controller, Research::findOrFail(1));

        try {
            $access->invoke($controller, Research::findOrFail(2));
            $this->fail('Cross-department access should be denied.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
