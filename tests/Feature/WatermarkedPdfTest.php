<?php

namespace Tests\Feature;

use App\Services\WatermarkedPdf;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class WatermarkedPdfTest extends TestCase
{
    public function test_it_generates_a_valid_pdf_with_watermark_graphics_state(): void
    {
        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $sourcePath = $directory . '/source-watermark-test.pdf';
        $watermarkedPath = $directory . '/watermarked-test.pdf';

        try {
            $source = new \FPDF();
            $source->AddPage();
            $source->SetFont('Arial', '', 12);
            $source->Cell(0, 10, 'Sample research content');
            $source->Output('F', $sourcePath);

            $watermarkedPdf = WatermarkedPdf::fromPath(
                $sourcePath,
                'PROPERTY OF PHILCST',
                public_path('images/philcstlogologo.png')
            );
            file_put_contents($watermarkedPath, $watermarkedPdf);

            $reader = new Fpdi();

            $this->assertStringStartsWith('%PDF-', $watermarkedPdf);
            $this->assertStringContainsString('/ExtGState', $watermarkedPdf);
            $this->assertStringContainsString('/GS1', $watermarkedPdf);
            $this->assertStringContainsString('/Subtype /Image', $watermarkedPdf);
            $this->assertSame(1, substr_count($watermarkedPdf, '/Subtype /Image'));
            $this->assertSame(1, $reader->setSourceFile($watermarkedPath));
        } finally {
            @unlink($sourcePath);
            @unlink($watermarkedPath);
        }
    }
}
