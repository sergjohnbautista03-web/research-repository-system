<?php

namespace App\Services;

class CoordinatorJournalReportPdf extends \FPDF
{
    private string $department = '';

    public function render(array $report): string
    {
        $this->department = $report['coordinator']->department;
        $this->SetTitle('Journal Preparation Report');
        $this->SetAuthor('UBE Repository');
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(true, 16);
        $this->AliasNbPages();
        $this->AddPage('L', 'A4');
        $this->SetFont('Arial', '', 9);
        $this->MultiCell(273, 5, $this->encode('Research Coordinator: '.$report['coordinator']->name.' | Generated: '.$report['generatedAt']));
        foreach ($report['filterLabels'] as $label => $value) {
            $this->MultiCell(273, 5, $this->encode($label.': '.$value));
        }
        $this->Ln(3);
        $this->heading('Summary');
        $this->table(['Metric', 'Total'], collect(CoordinatorJournalReport::METRICS)
            ->map(fn ($label, $key) => [$label, $report['totals'][$key]])->values()->all(), [233, 40]);
        $this->Ln(6);
        $this->heading('Journal Preparation Tasks');
        $this->table(CoordinatorJournalReport::HEADERS, $report['rows']->map(fn ($row) => CoordinatorJournalReport::cells($row))->all(), [35, 65, 62, 28, 48, 35]);

        foreach ($report['breakdowns'] as $breakdown) {
            $this->Ln(6);
            $this->heading('Breakdown by '.$breakdown['title']);
            $this->table([$breakdown['title'], 'Tasks', 'Journals', 'Batches', 'Submitted Batches'], $breakdown['rows']
                ->map(fn ($row) => [$row['label'], $row['tasks'], $row['journals'], $row['batches'], $row['submitted']])->all(), [133, 30, 30, 30, 50]);
        }

        return $this->Output('S');
    }

    public function Header(): void
    {
        $this->SetTextColor(76, 29, 149);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(273, 8, 'UBE Repository | Journal Preparation Report', 0, 1);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(108, 89, 132);
        $this->MultiCell(273, 5, $this->encode($this->department));
        $this->Ln(4);
        $this->SetTextColor(37, 17, 63);
    }

    public function Footer(): void
    {
        $this->SetY(-11);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(108, 89, 132);
        $this->Cell(273, 4, 'Research Coordinator Report | Page '.$this->PageNo().' of {nb}', 0, 0, 'R');
    }

    private function heading(string $text): void
    {
        if ($this->GetY() + 26 > $this->PageBreakTrigger) {
            $this->AddPage('L', 'A4');
        }
        $this->SetTextColor(76, 29, 149);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(273, 8, $text, 0, 1);
    }

    private function table(array $headers, array $rows, array $widths): void
    {
        $this->tableRow($headers, $widths, true);
        if (! $rows) {
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(108, 89, 132);
            $this->Cell(array_sum($widths), 11, 'No matching records.', 1, 1, 'C');

            return;
        }
        foreach ($rows as $index => $row) {
            $this->SetFont('Arial', '', 9);
            $height = max(array_map(fn ($value, $width) => count($this->lines($this->encode((string) $value), $width - 6)), $row, $widths)) * 4.5 + 6;
            if ($this->GetY() + $height > $this->PageBreakTrigger) {
                $this->AddPage('L', 'A4');
                $this->tableRow($headers, $widths, true);
            }
            $this->tableRow($row, $widths, false, $index % 2 === 0);
        }
    }

    private function tableRow(array $values, array $widths, bool $header, bool $shade = false): void
    {
        $this->SetFont('Arial', $header ? 'B' : '', 9);
        $lines = array_map(fn ($value, $width) => $this->lines($this->encode((string) $value), $width - 6), $values, $widths);
        $height = max(array_map('count', $lines)) * 4.5 + 6;
        $this->SetFillColor(...($header ? [76, 29, 149] : ($shade ? [249, 246, 253] : [255, 255, 255])));
        $this->SetTextColor(...($header ? [255, 255, 255] : [37, 17, 63]));
        $this->SetDrawColor(229, 218, 244);
        $y = $this->GetY();
        $x = 12;
        foreach ($lines as $index => $cellLines) {
            $this->Rect($x, $y, $widths[$index], $height, 'DF');
            $this->SetXY($x + 3, $y + 3);
            $this->MultiCell($widths[$index] - 6, 4.5, implode("\n", $cellLines));
            $x += $widths[$index];
        }
        $this->SetXY(12, $y + $height);
    }

    private function lines(string $text, float $width): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            if ($this->GetStringWidth($line.($line !== '' ? ' ' : '').$word) <= $width) {
                $line .= ($line !== '' ? ' ' : '').$word;

                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }
            // Long program names without spaces must also stay inside their cell.
            foreach (str_split($word) as $character) {
                if ($this->GetStringWidth($line.$character) > $width && $line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $character;
            }
        }
        $lines[] = $line;

        return $lines;
    }

    private function encode(string $value): string
    {
        $value = str_replace(['—', '–'], '-', $value);

        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);

        return $encoded === false ? '' : $encoded;
    }
}
