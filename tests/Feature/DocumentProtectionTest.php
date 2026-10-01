<?php

namespace Tests\Feature;

use App\Http\Controllers\ResearchController;
use App\Models\Research;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DocumentProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    }

    private function admin(): User
    {
        $user = new User();
        $user->forceFill(['id' => 7, 'role' => 'admin', 'name' => 'Administrator', 'is_active' => true]);
        $user->setRelation('pinnedResearches', collect());
        $this->actingAs($user);

        return $user;
    }

    private function research(): Research
    {
        $research = new Research(['title' => 'Protected Research', 'department' => 'BSIT',
            'abstract' => 'Academic abstract', 'author_name' => 'Author',
            'status' => 'approved', 'file_path' => 'research.pdf', 'file_name' => 'research.pdf',
            'year_published' => 2026, 'view_count' => 0]);
        $research->id = 35;

        return $research;
    }

    public static function siteUrls(): array
    {
        return [
            'local' => ['http://127.0.0.1:8000', 'http'],
            'hosted HTTPS' => ['https://repository.philcst.edu.ph', 'https'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('siteUrls')]
    public function test_viewer_renders_immediate_guard_and_valid_signed_pdf_on_local_and_hosted_urls(string $root, string $scheme): void
    {
        URL::forceRootUrl($root);
        URL::forceScheme($scheme);
        $user = $this->admin();
        $research = $this->research();
        Storage::shouldReceive('disk')->with('public')->once()->andReturnSelf();
        Storage::shouldReceive('exists')->with('research.pdf')->once()->andReturnTrue();
        $request = Request::create($root.'/research/35/view-file');
        $request->setUserResolver(fn () => $user);
        $view = (new ResearchController())->adminViewFile($request, $research);

        $this->assertStringStartsWith($root.'/research/35/stream-pdf?', $view->getData()['signedUrl']);
        $this->assertTrue(Request::create($view->getData()['signedUrl'])->hasValidSignature());
        $html = $view->render();
        $this->assertStringContainsString('Content Protected by PHILCST.', $html);
        $this->assertStringContainsString('role="alert" aria-live="assertive"', $html);
        $this->assertStringContainsString('id="resumeViewing"', $html);
        $this->assertStringContainsString($root.'/js/document-protection.js', $html);
        $this->assertSame(1, preg_match('/logUrl: (".*?"),/', $html, $matches));
        $this->assertSame('/research/35/capture-attempt', json_decode($matches[1]));
        $this->assertLessThan(strpos($html, 'import * as pdfjsLib'), strpos($html, '/js/document-protection.js'));
        if ($scheme === 'https') {
            $this->assertStringNotContainsString('127.0.0.1', $html);
            $this->assertStringNotContainsString('http://', $html);
        }
    }

    public function test_full_document_is_not_loaded_until_opened_on_the_research_detail_page(): void
    {
        $this->admin();
        $html = view('research.show', ['research' => $this->research()])->render();

        $this->assertStringContainsString('data-protected-document style="display:none', $html);
        $this->assertSame(1, preg_match('/<iframe\b[^>]*id="pdfFrame"[^>]*>/s', $html, $matches));
        $this->assertStringContainsString('data-src=', $matches[0]);
        $this->assertSame(0, preg_match('/\s src=/', $matches[0]));
        $this->assertStringContainsString('js/document-protection.js', $html);
        $this->assertStringNotContainsString('id="screenGuard"', $html);
    }

    public function test_general_pages_do_not_include_document_protection(): void
    {
        $html = view('pages.about')->render();

        $this->assertStringNotContainsString('js/document-protection.js', $html);
        $this->assertStringNotContainsString('id="screenGuard"', $html);
        $this->assertStringNotContainsString('site-security-guard', $html);
    }
}
