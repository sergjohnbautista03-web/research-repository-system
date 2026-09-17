<?php

namespace Tests\Feature;

use Tests\TestCase;

class DownloadsRemovalTest extends TestCase
{
    public function test_downloads_references_are_removed_from_admin_and_public_views(): void
    {
        $dashboardView = file_get_contents(base_path('resources/views/admin/dashboard.blade.php'));
        $researchDetailView = file_get_contents(base_path('resources/views/admin/research-detail.blade.php'));
        $faqView = file_get_contents(base_path('resources/views/pages/faq.blade.php'));
        $aboutView = file_get_contents(base_path('resources/views/pages/about.blade.php'));

        $this->assertStringNotContainsString('Downloads', $dashboardView);
        $this->assertStringNotContainsString('download', strtolower($researchDetailView));
        $this->assertStringNotContainsString('download', strtolower($faqView));
        $this->assertStringNotContainsString('download', strtolower($aboutView));
    }
}
