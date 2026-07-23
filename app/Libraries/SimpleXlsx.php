<?php

namespace App\Libraries;

/**
 * Pembaca & penulis XLSX generik (ZipArchive + OpenXML mentah, tanpa library
 * eksternal). Dipakai untuk fitur import berbasis template Excel — mis. import
 * item template Appraisal (KPI & Kompetensi).
 *
 * Menulis pakai inline string (tak perlu sharedStrings). Membaca menangani
 * inline string, shared string, dan angka.
 */
class SimpleXlsx
{
    /**
     * Bangun bytes file .xlsx dari beberapa sheet.
     * @param array $sheets [ ['name' => 'KPI', 'rows' => [[c,c,..],[c,c,..]]], ... ]
     *        Sel: string apa adanya, atau angka (int/float). Baris pertama biasanya header.
     * @param array $widths opsional: nama sheet => [lebar kolom, ...]
     */
    public static function build(array $sheets, array $widths = []): string
    {
        $tmp = tempnam(WRITEPATH, 'sxls_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $n = count($sheets);
        $zip->addFromString('[Content_Types].xml', self::contentTypes($n));
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels($n));
        $zip->addFromString('xl/styles.xml', self::styles());
        foreach (array_values($sheets) as $i => $sheet) {
            $w = $widths[$sheet['name']] ?? [];
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', self::sheetXml($sheet['rows'], $w));
        }
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    /** Kirim xlsx sebagai unduhan lalu exit. */
    public static function download(string $filename, array $sheets, array $widths = []): void
    {
        $bytes = self::build($sheets, $widths);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0');
        echo $bytes;
        exit;
    }

    /**
     * Baca satu sheet → daftar baris (array kolom, string/float). Baris kosong
     * dilewati. $sheetIndex 0-based.
     * @return array<int, array<int, mixed>>
     */
    public static function readRows(string $path, int $sheetIndex = 0): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];

        $strings   = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx) foreach ($sx->si as $si) {
                if (isset($si->t)) { $strings[] = (string) $si->t; }
                else { $t = ''; foreach ($si->r ?? [] as $r) { $t .= (string) ($r->t ?? ''); } $strings[] = $t; }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet' . ($sheetIndex + 1) . '.xml');
        $zip->close();
        if (! $sheetXml) return [];

        $sx = @simplexml_load_string($sheetXml);
        if (! $sx) return [];

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)\d+$/', strtoupper((string) $c['r']), $m);
                $col = self::colToInt($m[1] ?? 'A');
                $t   = (string) $c['t'];
                if ($t === 'inlineStr') {
                    $v = (string) ($c->is->t ?? '');
                } else {
                    $raw = isset($c->v) ? (string) $c->v : '';
                    if ($t === 's')          $v = $strings[(int) $raw] ?? '';
                    elseif (is_numeric($raw)) $v = 0 + $raw;
                    else                      $v = $raw;
                }
                $cells[$col] = $v;
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $flat = [];
                for ($i = 0; $i <= $max; $i++) { $flat[$i] = $cells[$i] ?? ''; }
                // lewati baris yang seluruhnya kosong
                if (implode('', array_map('strval', $flat)) !== '') $rows[] = $flat;
            }
        }
        return $rows;
    }

    // ── OpenXML parts ──────────────────────────────────────────────────────
    private static function contentTypes(int $n): string
    {
        $ov = '';
        for ($i = 1; $i <= $n; $i++) {
            $ov .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $ov . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(array $sheets): string
    {
        $s = '';
        foreach (array_values($sheets) as $i => $sheet) {
            $s .= '<sheet name="' . htmlspecialchars($sheet['name'], ENT_XML1) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $s . '</sheets></workbook>';
    }

    private static function workbookRels(int $n): string
    {
        $r = '';
        for ($i = 1; $i <= $n; $i++) {
            $r .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $r . '</Relationships>';
    }

    private static function styles(): string
    {
        // s=0 normal, s=1 header (bold + fill)
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF334155"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private static function sheetXml(array $rows, array $widths = []): string
    {
        $cols = '';
        if ($widths) {
            $cols = '<cols>';
            foreach ($widths as $i => $w) { $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>'; }
            $cols .= '</cols>';
        }
        $body = '';
        foreach ($rows as $ri => $cells) {
            $rowNum = $ri + 1;
            $style  = $ri === 0 ? 1 : 0; // baris pertama = header
            $body  .= '<row r="' . $rowNum . '">';
            foreach (array_values($cells) as $ci => $val) {
                $ref = self::colLetter($ci) . $rowNum;
                if (is_int($val) || is_float($val)) {
                    $body .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
                } else {
                    $body .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . htmlspecialchars((string) $val, ENT_XML1) . '</t></is></c>';
                }
            }
            $body .= '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $cols . '<sheetData>' . $body . '</sheetData></worksheet>';
    }

    private static function colLetter(int $index): string
    {
        $s = ''; $n = $index + 1;
        while ($n > 0) { $n--; $s = chr(65 + ($n % 26)) . $s; $n = intdiv($n, 26); }
        return $s;
    }

    private static function colToInt(string $letters): int
    {
        $n = 0;
        foreach (str_split($letters) as $ch) { $n = $n * 26 + (ord($ch) - 64); }
        return $n - 1;
    }
}
