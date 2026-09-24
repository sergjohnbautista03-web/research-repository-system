<?php

namespace Tests\Feature;

use App\Http\Controllers\ResearchController;
use App\Models\Research;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CoordinatorDocumentAccessTest extends TestCase
{
    private function coordinator(): User
    {
        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'admin', 'is_research_coordinator' => true, 'department' => 'BSIT']);
        $this->actingAs($user);
        return $user;
    }

    public function test_coordinator_can_open_own_department_file_before_publication(): void
    {
        $user = $this->coordinator();
        $research = new Research(['department' => 'BSIT', 'status' => 'pending', 'file_path' => 'research.pdf']);
        $research->id = 54;
        Storage::shouldReceive('disk')->with('public')->once()->andReturnSelf();
        Storage::shouldReceive('exists')->with('research.pdf')->once()->andReturnTrue();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $view = (new ResearchController())->adminViewFile($request, $research);
        $this->assertSame('research.protected-viewer', $view->name());
        $this->assertTrue($view->getData()['adminMode']);
    }

    public function test_other_department_is_denied_for_viewer_and_signed_stream(): void
    {
        $user = $this->coordinator();
        $research = new Research(['department' => 'BSHM', 'status' => 'pending']);
        $research->id = 54;
        $url = URL::temporarySignedRoute('research.streamPdf', now()->addMinutes(15), ['research' => 54, 'scope' => 'admin']);
        $request = Request::create($url);
        $request->setUserResolver(fn () => $user);
        foreach (['adminViewFile', 'streamPdf'] as $method) {
            try {
                (new ResearchController())->{$method}($request, $research);
                $this->fail('Other department must be denied.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
                $this->assertStringContainsString('assigned department', $exception->getMessage());
            }
        }
    }
}
