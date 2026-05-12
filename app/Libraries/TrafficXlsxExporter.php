<?php

namespace App\Libraries;

/**
 * Generates a native XLSX (no external library) for Daily Traffic export.
 * Uses ZipArchive + raw OpenXML.
 *
 * Style indices (cellXfs):
 *   0 = default
 *   1 = data cell        (thin border)
 *   2 = header cell      (bold white, blue bg, border, centered)
 *   3 = number cell      (thin border, #,##0 format)
 *   4 = total row cell   (bold, light blue bg, border)
 *   5 = total row number (bold, light blue bg, border, #,##0 format)
 */
class TrafficXlsxExporter
{
    /**
     * $sheets: array of sheet definitions:
     *   ['name' => string, 'doors' => string[], 'dates' => string[], 'data' => [tanggal][pintu] = int]
     */
    public static function download(string $filename, array $sheets): void
    {
        $tmp = tempnam(WRITEPATH, 'txls_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',        self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels',                self::rootRels());
        $zip->addFromString('xl/workbook.xml',            self::workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml',              self::styles());

        foreach ($sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', self::buildSheet($sheet));
        }

        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: max-age=0');
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    // ── OpenXML structural parts ───────────────────────────────────────────────

    private static function contentTypes(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '  <Override PartName="/xl/worksheets/sheet' . $i . '.xml" '
                        . 'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml"   ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
' . $overrides . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    private static function workbook(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>' . "\n";
        foreach ($sheets as $i => $sheet) {
            $n = $i + 1;
            $xml .= '    <sheet name="' . htmlspecialchars($sheet['name'], ENT_XML1) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>' . "\n";
        }
        $xml .= '  </sheets>
</workbook>';
        return $xml;
    }

    private static function workbookRels(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '  <Relationship Id="rId' . $i . '" '
                  . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                  . 'Target="worksheets/sheet' . $i . '.xml"/>' . "\n";
        }
        $xml .= '  <Relationship Id="rId' . ($sheetCount + 1) . '" '
              . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" '
              . 'Target="styles.xml"/>' . "\n";
        $xml .= '</Relationships>';
        return $xml;
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="1">
    <numFmt numFmtId="164" formatCode="#,##0"/>
  </numFmts>
  <fonts count="2">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2F5597"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDCE6F1"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFB4C6E7"/></left>
      <right style="thin"><color rgb="FFB4C6E7"/></right>
      <top style="thin"><color rgb="FFB4C6E7"/></top>
      <bottom style="thin"><color rgb="FFB4C6E7"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="6">
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0"   fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="0"   fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" wrapText="1"/></xf>
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
    <xf numFmtId="0"   fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="164" fontId="1" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
  </cellXfs>
</styleSheet>';
    }

    // ── Sheet builder (pivot: rows=tanggal, cols=pintu) ───────────────────────

    private static function buildSheet(array $sheet): string
    {
        $doors = $sheet['doors'];   // ['Pintu A', 'Pintu B', ...]
        $dates = $sheet['dates'];   // ['2026-05-01', '2026-05-02', ...]
        $data  = $sheet['data'];    // [tanggal][pintu] = int

        $rows  = '';
        $r     = 1;

        // Header row: No | Tanggal | Hari | <door1> | <door2> | ... | Total
        $headerCells = [
            self::cStr('No',      2),
            self::cStr('Tanggal', 2),
            self::cStr('Hari',    2),
        ];
        foreach ($doors as $door) {
            $headerCells[] = self::cStr($door, 2);
        }
        $headerCells[] = self::cStr('Total', 2);
        $rows .= self::buildRow($r++, $headerCells);

        // Data rows
        $no = 1;
        $colTotals = array_fill(0, count($doors), 0);
        $grandTotal = 0;

        foreach ($dates as $tanggal) {
            $hari  = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][date('w', strtotime($tanggal))];
            $cells = [
                self::cNum($no++, 1),
                self::cStr(date('d/m/Y', strtotime($tanggal)), 1),
                self::cStr($hari, 1),
            ];
            $rowTotal = 0;
            foreach ($doors as $di => $door) {
                $val = (int)($data[$tanggal][$door] ?? 0);
                $cells[] = self::cNum($val, 3);
                $colTotals[$di] += $val;
                $rowTotal += $val;
            }
            $cells[] = self::cNum($rowTotal, 5);
            $grandTotal += $rowTotal;
            $rows .= self::buildRow($r++, $cells);
        }

        // Total footer row
        $totalCells = [
            self::cStr('',      4),
            self::cStr('TOTAL', 4),
            self::cStr('',      4),
        ];
        foreach ($colTotals as $ct) {
            $totalCells[] = self::cNum($ct, 5);
        }
        $totalCells[] = self::cNum($grandTotal, 5);
        $rows .= self::buildRow($r++, $totalCells);

        // Column widths: No=5, Tanggal=13, Hari=6, doors=16 each, Total=14
        $widths = [5, 13, 6];
        foreach ($doors as $_) $widths[] = 16;
        $widths[] = 14;

        return self::wrapSheet($rows, $widths);
    }

    // ── Low-level helpers ──────────────────────────────────────────────────────

    private static function colLetter(int $index): string
    {
        $s = '';
        $n = $index + 1;
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private static function cStr(string $v, int $s): array
    {
        return ['t' => 's', 's' => $s, 'v' => htmlspecialchars($v, ENT_XML1)];
    }

    private static function cNum(int $v, int $s): array
    {
        return ['t' => 'n', 's' => $s, 'v' => $v];
    }

    private static function buildRow(int $rowNum, array $cells): string
    {
        $xml = '<row r="' . $rowNum . '">';
        foreach ($cells as $ci => $c) {
            $ref = self::colLetter($ci) . $rowNum;
            if ($c['t'] === 's') {
                $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . $c['s'] . '"><is><t>' . $c['v'] . '</t></is></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $c['s'] . '"><v>' . $c['v'] . '</v></c>';
            }
        }
        return $xml . '</row>';
    }

    private static function wrapSheet(string $rows, array $widths): string
    {
        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetViews>
    <sheetView workbookViewId="0">
      <pane xSplit="3" ySplit="1" topLeftCell="D2" activePane="bottomRight" state="frozen"/>
    </sheetView>
  </sheetViews>
  <cols>' . $cols . '</cols>
  <sheetData>' . $rows . '</sheetData>
</worksheet>';
    }
}
