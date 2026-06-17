<?php

namespace App\Services;

if (! class_exists('FPDF')) {
    require_once dirname(__DIR__, 2) . '/vendor/setasign/fpdf/fpdf.php';
}

if (! class_exists('setasign\\Fpdi\\Fpdi')) {
    require_once dirname(__DIR__, 2) . '/vendor/setasign/fpdi/src/autoload.php';
}

class PdfWatermarkDocument extends \setasign\Fpdi\Fpdi
{
    protected float $angle = 0;

    public function rotateText(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x === -1.0) {
            $x = $this->x;
        }

        if ($y === -1.0) {
            $y = $this->y;
        }

        if ($this->angle !== 0.0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle === 0.0) {
            return;
        }

        $angle *= M_PI / 180;
        $cos = cos($angle);
        $sin = sin($angle);
        $centerX = $x * $this->k;
        $centerY = ($this->h - $y) * $this->k;

        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
            $cos,
            $sin,
            -$sin,
            $cos,
            $centerX,
            $centerY,
            -$centerX,
            -$centerY
        ));
    }

    protected function _endpage(): void
    {
        if ($this->angle !== 0.0) {
            $this->angle = 0.0;
            $this->_out('Q');
        }

        parent::_endpage();
    }
}

class PdfWatermarkService
{
    public function createWatermarkedCopy(string $sourcePath, array $watermarkLines): string
    {
        $directory = storage_path('app/temp/watermarked-downloads');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $destinationPath = $directory . DIRECTORY_SEPARATOR . uniqid('research-watermarked-', true) . '.pdf';

        try {
            $pdf = new PdfWatermarkDocument();
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($sourcePath);
            $lines = $this->prepareWatermarkLines($watermarkLines);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                $this->stampPage($pdf, $lines, (float) $size['width'], (float) $size['height']);
            }

            $pdf->Output('F', $destinationPath);
        } catch (\Throwable $e) {
            if (is_file($destinationPath)) {
                unlink($destinationPath);
            }

            throw $e;
        }

        return $destinationPath;
    }

    private function stampPage(PdfWatermarkDocument $pdf, array $lines, float $width, float $height): void
    {
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(112, 68, 170);

        $xStep = 72.0;
        $yStep = 44.0;
        $lineHeight = 4.2;

        for ($y = -24.0; $y <= $height + 45.0; $y += $yStep) {
            for ($x = -42.0; $x <= $width + 45.0; $x += $xStep) {
                $pdf->rotateText(-28.0, $x, $y);

                foreach ($lines as $index => $line) {
                    $pdf->Text($x, $y + ($index * $lineHeight), $line);
                }

                $pdf->rotateText(0.0);
            }
        }
    }

    private function prepareWatermarkLines(array $lines): array
    {
        return collect($lines)
            ->filter(fn ($line) => is_scalar($line) && trim((string) $line) !== '')
            ->map(fn ($line) => $this->toPdfText((string) $line))
            ->take(6)
            ->values()
            ->all();
    }

    private function toPdfText(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $value = \Illuminate\Support\Str::limit($value, 76, '');

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '', $value) ?: ' ';
    }
}
