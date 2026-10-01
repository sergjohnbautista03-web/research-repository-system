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

    public static function departmentReaders(): array
    {
        return [
            'coordinator' => ['is_research_coordinator', 'BSIT'],
            'dean' => ['is_department_dean', 'BSIT'],
            'coordinator without department' => ['is_research_coordinator', null],
            'dean without department' => ['is_department_dean', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('departmentReaders')]
    public function test_department_admin_can_open_other_department_viewer(string $flag, ?string $department): void
    {
        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'admin', $flag => true, 'department' => $department]);
        $this->actingAs($user);
        $research = new Research(['department' => 'BSHM', 'status' => 'approved', 'file_path' => 'research.pdf']);
        $research->id = 54;
        Storage::shouldReceive('disk')->with('public')->once()->andReturnSelf();
        Storage::shouldReceive('exists')->with('research.pdf')->once()->andReturnTrue();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $view = (new ResearchController())->adminViewFile($request, $research);
        $this->assertSame('research.protected-viewer', $view->name());
        $this->assertTrue($view->getData()['adminMode']);
        $this->assertTrue(Request::create($view->getData()['signedUrl'])->hasValidSignature());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('departmentReaders')]
    public function test_cross_department_stream_passes_authorization_before_file_lookup(string $flag, ?string $department): void
    {
        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'admin', $flag => true, 'department' => $department]);
        $this->actingAs($user);
        $research = new Research(['department' => 'BSHM', 'status' => 'approved', 'file_path' => 'missing.pdf']);
        $research->id = 54;
        Storage::shouldReceive('disk')->with('public')->once()->andReturnSelf();
        Storage::shouldReceive('exists')->with('missing.pdf')->once()->andReturnFalse();
        $url = URL::temporarySignedRoute('research.streamPdf', now()->addMinutes(15), ['research' => 54, 'scope' => 'admin']);
        $request = Request::create($url);
        $request->setUserResolver(fn () => $user);
        try {
            (new ResearchController())->streamPdf($request, $research);
            $this->fail('Missing file should return 404 after authorization.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_non_admin_is_denied_for_admin_viewer_and_signed_stream(): void
    {
        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'researcher', 'department' => 'BSHM']);
        $this->actingAs($user);
        $research = new Research(['department' => 'BSHM', 'status' => 'pending']);
        $research->id = 54;
        $url = URL::temporarySignedRoute('research.streamPdf', now()->addMinutes(15), ['research' => 54, 'scope' => 'admin']);
        $request = Request::create($url);
        $request->setUserResolver(fn () => $user);
        foreach (['adminViewFile', 'streamPdf'] as $method) {
            try {
                (new ResearchController())->{$method}($request, $research);
                $this->fail('Non-admin must be denied.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
                $this->assertSame('Admins only.', $exception->getMessage());
            }
        }
    }
}
