<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class PageStyleLoadingTest extends TestCase
{
    use JournalWorkflowSchema;

    public static function pages(): array
    {
        return [
            'home' => ['/', null],
            'about' => ['/about', null],
            'faq' => ['/faq', null],
            'contact' => ['/contact', null],
            'policy' => ['/policy', '.policy-shell'],
            'password recovery' => ['/forgot-password', '.fp-page'],
            'research details' => ['/research/1', '.rd-card'],
            'admin dashboard' => ['/admin', '.ra-toolbar'],
            'add member' => ['/admin/create-user', null],
            'published researches' => ['/admin/researches', '.mr-'],
            'academic periods' => ['/admin/semesters', '.sem-'],
            'coordinator tasks' => ['/admin/coordinator/dean-submissions', '.coord-shell'],
        ];
    }

    #[DataProvider('pages')]
    public function test_styles_are_available_before_the_page_body(string $url, ?string $selector): void
    {
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);

        if ($url === '/admin/semesters') {
            Schema::create('semester_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('semester_id');
                $table->string('status')->default('active');
            });
        }

        Research::create([
            'title' => 'Style loading research', 'author_name' => 'Journal Author',
            'department' => 'College of Computer Studies', 'year_published' => 2026,
            'status' => Research::STATUS_APPROVED,
        ]);

        if (str_starts_with($url, '/admin')) {
            $this->actingAs(User::create([
                'name' => 'Style Reviewer', 'email' => 'styles@example.test',
                'password' => 'password', 'role' => 'admin', 'is_active' => true,
                'department' => 'College of Computer Studies',
                'is_department_dean' => $url === '/admin/create-user',
                'is_research_coordinator' => str_contains($url, '/coordinator/'),
            ]));
        }

        $html = $this->get($url)->assertOk()->getContent();
        // Ignore HTML snippets in report-printing scripts; those are separate documents.
        $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html);
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        $this->assertSame(0, $xpath->query('//body//style | //body//link[@rel="stylesheet"]')->length,
            'Page styles must be loaded in the head to prevent unstyled content during navigation.');
        $this->assertSame(1, $xpath->query('//head/link[@rel="stylesheet" and contains(@href, "css/app.css")]')->length);
        $this->assertSame(1, $xpath->query('//head/link[@rel="stylesheet" and contains(@href, "css/live-search.css")]')->length);

        if ($selector !== null) {
            $this->assertStringContainsString($selector, $xpath->query('//head')->item(0)->textContent);
        }
    }
}
