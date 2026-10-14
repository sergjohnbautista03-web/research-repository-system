<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class CoordinatorJournalReportExcel
{
    public function render(array $report): string
    {
        $summary = [['Journal Preparation Report', 'UBE Repository'],
            ['Research Coordinator', $report['coordinator']->name], ['Department', $report['coordinator']->department],
            ['Generated', $report['generatedAt']], ['', '']];
        foreach ($report['filterLabels'] as $label => $value) {
            $summary[] = [$label, $value];
        }
        $summary[] = ['', ''];
        foreach (CoordinatorJournalReport::METRICS as $key => $label) {
            $summary[] = [$label, $report['totals'][$key]];
        }
        $sheets = [
            'Summary' => ['rows' => $summary, 'widths' => [55, 60]],
            'Journal Preparation' => ['rows' => [CoordinatorJournalReport::HEADERS, ...$report['rows']
                ->map(fn ($row) => CoordinatorJournalReport::cells($row))->all()], 'widths' => [22, 42, 35, 22, 32, 22]],
        ];
        foreach ($report['breakdowns'] as $breakdown) {
            $sheets['By '.$breakdown['title']] = [
                'rows' => [[$breakdown['title'], 'Tasks', 'Journals', 'Batches', 'Submitted Batches'], ...$breakdown['rows']
                    ->map(fn ($row) => [$row['label'], $row['tasks'], $row['journals'], $row['batches'], $row['submitted']])->all()],
                'widths' => [48, 16, 16, 16, 24],
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'ube-journal-report-');
        if ($path === false) {
            throw new RuntimeException('Unable to create journal report export.');
        }
        $zip = new ZipArchive;
        $opened = false;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to open journal report workbook.');
            }
            $opened = true;
            $types = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
            $workbook = '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
            $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            $index = 0;
            foreach ($sheets as $name => $sheet) {
                $index++;
                $types .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $workbook .= '<sheet name="'.$this->xml($name).'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
                $rels .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
                $zip->addFromString('xl/worksheets/sheet'.$index.'.xml', $this->sheet($sheet['rows'], $sheet['widths'], $name !== 'Summary'));
            }
            $rels .= '<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
            $zip->addFromString('[Content_Types].xml', $types.'</Types>');
            $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="workbook" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', $workbook.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->close();
            $opened = false;

            return file_get_contents($path);
        } finally {
            if ($opened) {
                $zip->close();
            }
            unlink($path);
        }
    }

    private function sheet(array $rows, array $widths, bool $filter): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
        foreach ($widths as $index => $width) {
            $xml .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        foreach ($rows as $index => $row) {
            $number = $index + 1;
            $height = max(26, max(array_map(fn ($value, $width) => ceil(mb_strlen((string) $value) / ($width - 3)) * 15 + 10, $row, $widths)));
            $xml .= '<row r="'.$number.'" ht="'.$height.'" customHeight="1">';
            foreach ($row as $column => $value) {
                $reference = chr(65 + $column).$number;
                $style = $index === 0 ? 1 : 2;
                if (is_int($value)) {
                    $xml .= '<c r="'.$reference.'" s="'.$style.'" t="n"><v>'.$value.'</v></c>';
                } else {
                    // Inline strings preserve Unicode and cannot execute spreadsheet formulas.
                    $xml .= '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';
        if ($filter) {
            $xml .= '<autoFilter ref="A1:'.chr(64 + count($widths)).count($rows).'"/>';
        }

        return $xml.'<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>';
    }

    private function xml(string $value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function styles(): string
    {
        return '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><color rgb="FF25113F"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4C1D95"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFE5DAF4"/></left><right style="thin"><color rgb="FFE5DAF4"/></right><top style="thin"><color rgb="FFE5DAF4"/></top><bottom style="thin"><color rgb="FFE5DAF4"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
