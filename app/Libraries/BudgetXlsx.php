<?php

namespace App\Libraries;

/**
 * Penulis XLSX bergaya untuk ekspor Budget Departemen (ZipArchive + OpenXML
 * mentah, tanpa library eksternal — pola sama dengan TrafficXlsxExporter).
 *
 * Satu sheet = ['name', 'rows' => [[sel, ...], ...], 'widths' => [..],
 *               'freeze' => 'B4' (opsional), 'merge' => ['A1:C1', ...],
 *               'komentar' => ['C5' => "teks", ...] (opsional — komentar sel Excel)].
 * Sel: null (kosong), skalar (teks/angka, gaya bawaan), atau
 *      ['v' => nilai, 's' => self::GAYA, 'f' => 'SUM(B4:B9)' (opsional)].
 */
class BudgetXlsx
{
    // Indeks gaya (cellXfs) — urutan HARUS sama dengan styles().
    const BIASA  = 0;
    const TEKS   = 1;   // teks berbingkai
    const KEPALA = 2;   // tebal putih di biru, tengah
    const ANGKA  = 3;   // #,##0 berbingkai
    const TOTAL  = 4;   // teks tebal di biru muda
    const TOTALN = 5;   // angka tebal di biru muda
    const JUDUL  = 6;   // tebal besar
    const PERSEN = 7;   // 0,0% berbingkai
    const TOTALP = 8;   // persen tebal di biru muda
    const CATAT  = 9;   // miring abu-abu
    const TGL    = 10;  // tanggal dd/mm/yyyy berbingkai
    const LEWAT  = 11;  // angka merah tebal (melewati budget)

    /** Kirim xlsx sebagai unduhan lalu exit. */
    public static function download(string $filename, array $sheets): void
    {
        $bytes = self::build($sheets);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0');
        echo $bytes;
        exit;
    }

    public static function build(array $sheets): string
    {
        $tmp = tempnam(WRITEPATH, 'bxls_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $n = count($sheets);
        $adaKomentar = array_map(fn ($x) => ! empty($x['komentar']), array_values($sheets));
        $zip->addFromString('[Content_Types].xml', self::contentTypes($n, $adaKomentar));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', self::workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels($n));
        $zip->addFromString('xl/styles.xml', self::styles());
        foreach (array_values($sheets) as $i => $s) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', self::sheetXml($s));
            if (! empty($s['komentar'])) {
                $k = $i + 1;
                $zip->addFromString("xl/worksheets/_rels/sheet$k.xml.rels", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/comments" Target="../comments' . $k . '.xml"/>'
                    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/vmlDrawing" Target="../drawings/vmlDrawing' . $k . '.vml"/>'
                    . '</Relationships>');
                $zip->addFromString("xl/comments$k.xml", self::komentarXml($s['komentar']));
                $zip->addFromString("xl/drawings/vmlDrawing$k.vml", self::komentarVml($s['komentar']));
            }
        }
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    /** Indeks kolom 0-based → huruf (0 → A, 26 → AA). */
    public static function kolom(int $i): string
    {
        $s = '';
        for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) $s = chr(65 + ($n - 1) % 26) . $s;
        return $s;
    }

    private static function sheetXml(array $s): string
    {
        $rows = '';
        foreach ($s['rows'] as $ri => $row) {
            $r = $ri + 1;
            $xml = '';
            foreach ($row as $ci => $c) {
                if ($c === null) continue;
                if (! is_array($c)) $c = ['v' => $c];
                $ref  = self::kolom($ci) . $r;
                $gaya = (int) ($c['s'] ?? self::BIASA);
                $v    = $c['v'] ?? null;
                if (isset($c['f'])) {
                    $xml .= '<c r="' . $ref . '" s="' . $gaya . '"><f>' . htmlspecialchars($c['f'], ENT_XML1) . '</f>'
                          . (is_numeric($v) ? '<v>' . $v . '</v>' : '') . '</c>';
                } elseif (is_int($v) || is_float($v)) {
                    $xml .= '<c r="' . $ref . '" s="' . $gaya . '"><v>' . $v . '</v></c>';
                } elseif ($v === null || $v === '') {
                    $xml .= '<c r="' . $ref . '" s="' . $gaya . '"/>';
                } else {
                    $xml .= '<c r="' . $ref . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">'
                          . htmlspecialchars((string) $v, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</t></is></c>';
                }
            }
            $rows .= '<row r="' . $r . '">' . $xml . '</row>';
        }

        $view = '<sheetView workbookViewId="0"';
        if (! empty($s['freeze']) && preg_match('/^([A-Z]+)(\d+)$/', $s['freeze'], $m)) {
            $x = 0;
            foreach (str_split($m[1]) as $ch) $x = $x * 26 + (ord($ch) - 64);
            $x -= 1; $y = (int) $m[2] - 1;
            $pane = ($x && $y) ? 'bottomRight' : ($y ? 'bottomLeft' : 'topRight');
            $view .= '><pane' . ($x ? ' xSplit="' . $x . '"' : '') . ($y ? ' ySplit="' . $y . '"' : '')
                   . ' topLeftCell="' . $s['freeze'] . '" activePane="' . $pane . '" state="frozen"/></sheetView>';
        } else {
            $view .= '/>';
        }

        $cols = '';
        foreach ($s['widths'] ?? [] as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $merge = '';
        if (! empty($s['merge'])) {
            $merge = '<mergeCells count="' . count($s['merge']) . '">';
            foreach ($s['merge'] as $ref) $merge .= '<mergeCell ref="' . $ref . '"/>';
            $merge .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews>' . $view . '</sheetViews>'
            . ($cols ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $rows . '</sheetData>' . $merge
            . (! empty($s['komentar']) ? '<legacyDrawing r:id="rId2"/>' : '')
            . '</worksheet>';
    }

    private static function contentTypes(int $n, array $adaKomentar = []): string
    {
        $o = '';
        for ($i = 1; $i <= $n; $i++) {
            $o .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            if (! empty($adaKomentar[$i - 1])) {
                $o .= '<Override PartName="/xl/comments' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.comments+xml"/>';
            }
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="vml" ContentType="application/vnd.openxmlformats-officedocument.vmlDrawing"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $o . '</Types>';
    }

    private static function komentarXml(array $komentar): string
    {
        $x = '';
        foreach ($komentar as $ref => $teks) {
            $x .= '<comment ref="' . $ref . '" authorId="0"><text><r><rPr><sz val="9"/><rFont val="Calibri"/></rPr><t xml:space="preserve">'
                . htmlspecialchars($teks, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</t></r></text></comment>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<comments xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><authors><author>MIC</author></authors>'
            . '<commentList>' . $x . '</commentList></comments>';
    }

    /** Bentuk VML komentar — wajib agar Excel menampilkan segitiga merah & kotak komentar. */
    private static function komentarVml(array $komentar): string
    {
        $x = ''; $id = 1024;
        foreach ($komentar as $ref => $teks) {
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
            $kol = 0;
            foreach (str_split($m[1]) as $ch) $kol = $kol * 26 + (ord($ch) - 64);
            $kol -= 1; $baris = (int) $m[2] - 1;
            $tinggi = 18 + 12 * min(12, substr_count($teks, "\n") + 1);
            $x .= '<v:shape id="_x0000_s' . (++$id) . '" type="#_x0000_t202" style="position:absolute;margin-left:80pt;margin-top:2pt;width:220pt;height:' . $tinggi . 'pt;z-index:' . ($id - 1024) . ';visibility:hidden" fillcolor="#ffffe1" o:insetmode="auto">'
                . '<v:fill color2="#ffffe1"/><v:shadow on="t" color="black" obscured="t"/><v:path o:connecttype="none"/>'
                . '<v:textbox style="mso-direction-alt:auto"><div style="text-align:left"></div></v:textbox>'
                . '<x:ClientData ObjectType="Note"><x:MoveWithCells/><x:SizeWithCells/>'
                . '<x:Anchor>' . ($kol + 1) . ', 15, ' . $baris . ', 2, ' . ($kol + 5) . ', 15, ' . ($baris + 4) . ', 16</x:Anchor>'
                . '<x:AutoFill>False</x:AutoFill><x:Row>' . $baris . '</x:Row><x:Column>' . $kol . '</x:Column></x:ClientData></v:shape>';
        }
        return '<xml xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
            . '<o:shapelayout v:ext="edit"><o:idmap v:ext="edit" data="1"/></o:shapelayout>'
            . '<v:shapetype id="_x0000_t202" coordsize="21600,21600" o:spt="202" path="m,l,21600r21600,l21600,xe">'
            . '<v:stroke joinstyle="miter"/><v:path gradientshapeok="t" o:connecttype="rect"/></v:shapetype>' . $x . '</xml>';
    }

    private static function workbook(array $sheets): string
    {
        $x = '';
        foreach (array_values($sheets) as $i => $s) {
            // Nama sheet Excel: maks 31 karakter, tanpa : \ / ? * [ ]
            $nama = mb_substr(preg_replace('#[:\\\\/?*\[\]]#', '-', $s['name']), 0, 31);
            $x .= '<sheet name="' . htmlspecialchars($nama, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $x . '</sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';
    }

    private static function workbookRels(int $n): string
    {
        $x = '';
        for ($i = 1; $i <= $n; $i++) {
            $x .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $x .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $x . '</Relationships>';
    }

    private static function styles(): string
    {
        $b = 'borderId="1" applyBorder="1"';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0;-#,##0;&quot;-&quot;"/>'
            . '<numFmt numFmtId="165" formatCode="0.0%"/><numFmt numFmtId="166" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="6">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><color rgb="FF1F3864"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF7F7F7F"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFC00000"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2F5597"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCE6F1"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/></border>'
            . '<border><left style="thin"><color rgb="FFB4C6E7"/></left><right style="thin"><color rgb="FFB4C6E7"/></right>'
            . '<top style="thin"><color rgb="FFB4C6E7"/></top><bottom style="thin"><color rgb="FFB4C6E7"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="12">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" ' . $b . ' xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" ' . $b . ' xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" ' . $b . ' xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" ' . $b . ' xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="2" fillId="3" ' . $b . ' xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" ' . $b . ' xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="2" fillId="3" ' . $b . ' xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="166" fontId="0" fillId="0" ' . $b . ' xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            . '<xf numFmtId="164" fontId="5" fillId="0" ' . $b . ' xfId="0" applyNumberFormat="1" applyFont="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
