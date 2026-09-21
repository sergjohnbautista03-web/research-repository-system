<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class WatermarkedPdf extends Fpdi
{
    private array $extGStates = [];

    private array $extGStateIndexes = [];

    private float $rotationAngle = 0.0;

    public static function fromPath(string $sourcePath, string $watermarkText, ?string $logoPath = null): string
    {
        $pdf = new self();
        $pdf->SetAutoPageBreak(false);

        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            $pdf->drawWatermark($watermarkText, $logoPath, (float) $size['width'], (float) $size['height']);
        }

        return $pdf->Output('S');
    }

    private function drawWatermark(string $text, ?string $logoPath, float $pageWidth, float $pageHeight): void
    {
        $fontSize = max(30, min(58, $pageWidth / 5.2));
        $centerX = $pageWidth / 2;
        $centerY = $pageHeight / 2;
        $gap = max(5, $pageWidth * 0.025);
        $logoWidth = 0.0;
        $logoHeight = 0.0;

        $this->SetFont('Arial', 'B', $fontSize);
        $textWidth = $this->GetStringWidth($text);
        $textBaselineOffset = ($fontSize / $this->k) * 0.35;

        if ($logoPath && is_file($logoPath)) {
            $imageSize = getimagesize($logoPath);

            if ($imageSize) {
                $logoWidth = max(24, min(40, $pageWidth * 0.16));
                $logoHeight = $logoWidth * ($imageSize[1] / $imageSize[0]);
            }
        }

        $groupWidth = $textWidth + ($logoWidth > 0 ? $logoWidth + $gap : 0);
        $startX = $centerX - ($groupWidth / 2);

        $this->rotate(-35, $centerX, $centerY);

        if ($logoWidth > 0) {
            $this->setAlpha(0.24);
            $this->Image($logoPath, $startX, $centerY - ($logoHeight / 2), $logoWidth, $logoHeight);
            $startX += $logoWidth + $gap;
        }

        $this->setAlpha(0.42);
        $this->SetTextColor(59, 15, 122);
        $this->Text($startX, $centerY + $textBaselineOffset, $text);
        $this->rotate(0);
        $this->setAlpha(1);
    }

    private function rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x === -1.0) {
            $x = $this->x;
        }

        if ($y === -1.0) {
            $y = $this->y;
        }

        if ($this->rotationAngle !== 0.0) {
            $this->_out('Q');
        }

        $this->rotationAngle = $angle;

        if ($angle === 0.0) {
            return;
        }

        $angle *= M_PI / 180;
        $cosine = cos($angle);
        $sine = sin($angle);
        $centerX = $x * $this->k;
        $centerY = ($this->h - $y) * $this->k;

        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
            $cosine,
            $sine,
            -$sine,
            $cosine,
            $centerX,
            $centerY,
            -$centerX,
            -$centerY
        ));
    }

    private function setAlpha(float $alpha, string $blendMode = 'Normal'): void
    {
        $alpha = max(0, min(1, $alpha));
        $key = number_format($alpha, 3) . '|' . $blendMode;

        if (! isset($this->extGStateIndexes[$key])) {
            $this->extGStateIndexes[$key] = $this->addExtGState([
                'ca' => $alpha,
                'CA' => $alpha,
                'BM' => '/' . $blendMode,
            ]);
        }

        $this->setExtGState($this->extGStateIndexes[$key]);
    }

    private function addExtGState(array $parameters): int
    {
        $this->extGStates[] = ['parms' => $parameters];

        return count($this->extGStates);
    }

    private function setExtGState(int $index): void
    {
        $this->_out('/GS' . $index . ' gs');
    }

    private function putExtGStates(): void
    {
        foreach ($this->extGStates as $index => $extGState) {
            $this->_newobj();
            $this->extGStates[$index]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');

            foreach ($extGState['parms'] as $key => $value) {
                if (is_float($value) || is_int($value)) {
                    $value = sprintf('%.3F', $value);
                }

                $this->_put('/' . $key . ' ' . $value);
            }

            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _endpage()
    {
        if ($this->rotationAngle !== 0.0) {
            $this->rotationAngle = 0.0;
            $this->_out('Q');
        }

        parent::_endpage();
    }

    protected function _putresourcedict()
    {
        parent::_putresourcedict();

        if ($this->extGStates === []) {
            return;
        }

        $this->_put('/ExtGState <<');

        foreach ($this->extGStates as $index => $extGState) {
            $this->_put('/GS' . ($index + 1) . ' ' . $extGState['n'] . ' 0 R');
        }

        $this->_put('>>');
    }

    protected function _putresources()
    {
        $this->putExtGStates();

        parent::_putresources();
    }

    protected function _enddoc()
    {
        if ($this->extGStates !== [] && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }

        parent::_enddoc();
    }
}
