<?php

namespace App\Libraries;

class TrafficExcelParser
{
    // Sheet1 door mapping: Excel header (lowercase, trimmed) → system nama_pintu
    const DOOR_MAP = [
        'ewalk' => [
            'pintu utama'          => 'GF Lobby Utama (Utara)',
            'pintu gf timur'       => 'GF Lobby Timur',
            'pintu gf barat'       => 'GF Lobby Barat',
            'pintu gf selatan'     => 'GF Lobby Selatan',
            'pintu lg timur'       => 'LG lobby Timur',
            'pintu lg barat'       => 'LG Lobby Barat',
            'pintu ug funstation'  => 'UG Bridge Funstation 1',
            'lantai ug funstation' => 'UG Bridge Funstation 1',
            'pintu lt. 1 xx1'      => 'FF Bridge XXI 1',
            'pintu lt. 1 xxi'      => 'FF Bridge XXI 1',
            'lantai 1 xx1'         => 'FF Bridge XXI 1',
            'lantai 1 xxi'         => 'FF Bridge XXI 1',
        ],
        // Pentacity sheet1 doors (Funstation & FF XXI intentionally absent — come from People Count sheets)
        'pentacity' => [
            'pintu utama'              => 'GF Lobby Utama',
            'pintu gf flying tiger'    => 'GF Lobby Flying Tiger',
            'pintu gf beach gate'      => 'GF Lobby Beach Gate',
            'pintu gf pentacity hotel' => 'GF Pentacity hotel',
            'pintu h&m'                => 'GF H&M',
            'pintu gf travelator'      => 'GF Travelator',
            'pintu lg solaria'         => 'LG Lobby Solaria',
            'pintu lg kfc'             => 'LG Lobby KFC',
            'pintu lg miniso'          => 'LG Lobby Miniso',
            'pintu lg hypermart'       => 'LG Lobby Hypermart',
            'pintu ff lift aquaboom'   => 'FF Lift Aquaboom',
            'pintu p3 otomatis'        => 'P3 Pintu otomatis',
            'pintu p3 kidzoona'        => 'P3 Kidzoona',
            'pintu p4 masjid'          => 'P4 Masjid',
            'pintu p4 mezanin'         => 'P4 mezzanine',
            'pintu p5 mezanin'         => 'P5 Mezzanine',
            'pintu p5 office'          => 'P5 Office',
        ],
    ];

    // PSV People Count sheets: col D/F = IN Penta (→ pentacity), col E/G = IN eWalk (→ ewalk)
    const PSV_UG_DOOR_1 = 'UG Bridge Funstation 1';  // pentacity
    const PSV_UG_DOOR_2 = 'UG Bridge Funstation 2';  // pentacity
    const PSV_FF_DOOR_1 = 'FF Bridge XXI 1';          // pentacity
    const PSV_FF_DOOR_2 = 'FF Bridge XXI 2';          // pentacity
    // eWalk door names that PSV eWalk columns override
    const PSV_EWALK_UG_1 = 'UG Bridge Funstation 1'; // ewalk mall
    const PSV_EWALK_UG_2 = 'UG Bridge Funstation 2'; // ewalk mall
    const PSV_EWALK_FF_1 = 'FF Bridge XXI 1';         // ewalk mall
    const PSV_EWALK_FF_2 = 'FF Bridge XXI 2';         // ewalk mall

    // Normalize: remove non-breaking spaces, lowercase, trim
    private static function normalizeKey(string $val): string
    {
        $val = str_replace("\xc2\xa0", ' ', $val); // UTF-8 non-breaking space
        return strtolower(trim($val));
    }

    private static function colToInt(string $col): int
    {
        $col = strtoupper(trim($col));
        $n   = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }
        return $n;
    }

    private static function parseSharedStrings(string $xml): array
    {
        $strings = [];
        $sx = simplexml_load_string($xml);
        foreach ($sx->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string)$si->t;
            } else {
                $text = '';
                foreach ($si->r ?? [] as $r) {
                    $text .= (string)($r->t ?? '');
                }
                $strings[] = $text;
            }
        }
        return $strings;
    }

    // Returns 2D grid: $grid[rowNum][colInt] = value (string or float)
    private static function buildGrid(string $sheetXml, array $strings): array
    {
        $grid = [];
        $sx   = simplexml_load_string($sheetXml);
        foreach ($sx->sheetData->row as $row) {
            $rowNum = (int)$row['r'];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)(\d+)$/', strtoupper($ref), $m);
                $colInt = self::colToInt($m[1]);
                $t = (string)$c['t'];
                $v = isset($c->v) ? (string)$c->v : '';
                if ($t === 's') {
                    $v = $strings[(int)$v] ?? '';
                } elseif (is_numeric($v)) {
                    $v = (float)$v;
                }
                $grid[$rowNum][$colInt] = $v;
            }
        }
        return $grid;
    }

    private static function parseIndonesianDate(string $str): ?string
    {
        $months = [
            'januari'=>'01','februari'=>'02','maret'=>'03','april'=>'04',
            'mei'=>'05','juni'=>'06','juli'=>'07','agustus'=>'08',
            'september'=>'09','oktober'=>'10','november'=>'11','desember'=>'12',
        ];
        if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/i', $str, $m)) {
            $mon = $months[strtolower($m[2])] ?? null;
            if ($mon) return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$mon, (int)$m[1]);
        }
        return null;
    }

    // Find a sheet file by partial keyword match (any keyword hits = match), case-insensitive
    private static function findSheetFuzzy(array $sheetMap, array $keywords): ?string
    {
        foreach ($sheetMap as $name => $file) {
            $lower = strtolower($name);
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) return $file;
            }
        }
        return null;
    }

    // Build map: sheet name → 'xl/worksheets/sheetN.xml'
    private static function getSheetFiles(string $wbXml, string $wbRels): array
    {
        $relMap = [];
        if (preg_match_all('/Id="([^"]+)"[^>]*Target="([^"]+)"/', $wbRels, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $target = ltrim($m[2], '/');
                $relMap[$m[1]] = strpos($target, 'xl/') === 0 ? $target : 'xl/' . $target;
            }
        }
        $files = [];
        if (preg_match_all('/<sheet\b[^>]*name="([^"]+)"[^>]*r:id="([^"]+)"/', $wbXml, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                if (isset($relMap[$m[2]])) {
                    $files[$m[1]] = $relMap[$m[2]];
                }
            }
        }
        return $files;
    }

    // Parse a PSV People Count sheet.
    // Returns [jam => ['p1','e1','p2','e2']] where:
    //   p1/p2 = IN Penta cols (D, F)  — col indices pentaCols[0] and pentaCols[1]
    //   e1/e2 = IN eWalk cols (E, G)  — each immediately after the corresponding IN Penta col
    private static function parseInPentaSheet(string $sheetXml, array $strings): array
    {
        $grid = self::buildGrid($sheetXml, $strings);

        // Find header row: must contain 'IN Penta' AND at least one 'IN eWalk'/'IN PENTACITY'
        $pentaCols = []; // sorted colInts that have 'in penta'
        $headerRow = null;

        foreach ($grid as $rowNum => $cols) {
            $rowPenta  = [];
            $hasOther  = false;
            foreach ($cols as $colInt => $val) {
                if (! is_string($val)) continue;
                $nk = self::normalizeKey($val);
                if ($nk === 'in penta') {
                    $rowPenta[] = $colInt;
                }
                if ($nk === 'in ewalk' || str_contains($nk, 'in pentacity')) {
                    $hasOther = true;
                }
            }
            if (! empty($rowPenta) && $hasOther) {
                sort($rowPenta);        // ascending by col index
                $pentaCols = $rowPenta;
                $headerRow = $rowNum;
                break;
            }
        }

        if (empty($pentaCols)) return [];

        $p1Col = $pentaCols[0];                 // IN Penta camera 1 (col D)
        $e1Col = $p1Col + 1;                    // IN eWalk camera 1 (col E)
        $p2Col = $pentaCols[1] ?? ($p1Col + 2); // IN Penta camera 2 (col F)
        $e2Col = $p2Col + 1;                    // IN eWalk camera 2 (col G)

        $result = [];
        foreach ($grid as $rowNum => $cols) {
            if ($rowNum <= $headerRow) continue;

            // Time decimal in col A (1) for most rows, col C (3) for first row of date block
            $timeDecimal = null;
            foreach ([1, 3] as $tc) {
                if (isset($cols[$tc]) && is_float($cols[$tc])
                    && $cols[$tc] > 0.40 && $cols[$tc] < 0.97) {
                    $timeDecimal = $cols[$tc];
                    break;
                }
            }
            if ($timeDecimal === null) continue;

            $jam = (int)round($timeDecimal * 24) - 1;
            if ($jam < 10 || $jam > 22) continue;

            $p1 = $cols[$p1Col] ?? 0;
            $e1 = $cols[$e1Col] ?? 0;
            $p2 = $cols[$p2Col] ?? 0;
            $e2 = $cols[$e2Col] ?? 0;
            $result[$jam] = [
                'p1' => (int)(is_numeric($p1) ? $p1 : 0),
                'e1' => (int)(is_numeric($e1) ? $e1 : 0),
                'p2' => (int)(is_numeric($p2) ? $p2 : 0),
                'e2' => (int)(is_numeric($e2) ? $e2 : 0),
            ];
        }

        return $result;
    }

    public static function parse(string $filePath, string $mall = 'ewalk'): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Tidak bisa membuka file Excel.');
        }

        $sharedXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        $wbXml     = $zip->getFromName('xl/workbook.xml') ?: '';
        $wbRels    = $zip->getFromName('xl/_rels/workbook.xml.rels') ?: '';
        $sheetMap  = self::getSheetFiles($wbXml, $wbRels);

        // Main sheet
        $mainFile = $sheetMap['jumlah perjam'] ?? array_values($sheetMap)[0] ?? null;
        if (! $mainFile) throw new \Exception('Sheet utama tidak ditemukan.');
        $sheetXml = $zip->getFromName($mainFile);
        if (! $sheetXml) throw new \Exception('Sheet tidak ditemukan dalam file.');

        // PSV multi-sheet detection (Pentacity format) — fuzzy match to handle typos in sheet names
        $ugFile = self::findSheetFuzzy($sheetMap, ['count ug']);
        $ffFile = self::findSheetFuzzy($sheetMap, ['count ff', 'ff xxi']);
        $isPsv  = ($mall === 'pentacity') && $ugFile !== null;
        $ugXml  = ($isPsv && $ugFile) ? ($zip->getFromName($ugFile) ?: null) : null;
        $ffXml  = ($isPsv && $ffFile) ? ($zip->getFromName($ffFile) ?: null) : null;

        $zip->close();

        $strings = $sharedXml ? self::parseSharedStrings($sharedXml) : [];
        $grid    = self::buildGrid($sheetXml, $strings);
        $doorMap = self::DOOR_MAP[$mall] ?? [];

        // ── Find header row: scan for known door names ──
        $colToDoor = [];
        $headerRow = null;
        $tanggal   = null;

        foreach ($grid as $rowNum => $cols) {
            foreach ($cols as $colInt => $val) {
                if (is_string($val)) {
                    $key = self::normalizeKey($val);
                    if ($key && isset($doorMap[$key])) {
                        $colToDoor[$colInt] = $doorMap[$key];
                        $headerRow = $rowNum;
                    }
                    if ($tanggal === null) {
                        $d = self::parseIndonesianDate($val);
                        if ($d) $tanggal = $d;
                    }
                }
            }
        }

        if (empty($colToDoor)) {
            throw new \Exception('Kolom pintu tidak dikenali. Pastikan format file sesuai.');
        }

        // ── Find JAM column (string with time range pattern) ──
        $jamCol = null;
        foreach ($grid as $rowNum => $cols) {
            if ($headerRow && $rowNum > $headerRow + 5) break;
            foreach ($cols as $colInt => $val) {
                if (is_string($val) && preg_match('/^\d+[.,]\d+\s*[-–]\s*\d+/', $val)) {
                    $jamCol = $colInt;
                    break 2;
                }
            }
        }

        // ── Parse data rows ──
        $rows     = [];
        $warnings = [];

        foreach ($grid as $rowNum => $cols) {
            if ($headerRow && $rowNum <= $headerRow) continue;

            $jamVal = '';
            if ($jamCol !== null && isset($cols[$jamCol])) {
                $jamVal = (string)$cols[$jamCol];
            } else {
                foreach ($cols as $val) {
                    if (is_string($val) && preg_match('/^\d+[.,]\d+\s*[-–]\s*\d+/', $val)) {
                        $jamVal = $val;
                        break;
                    }
                }
            }

            if (empty($jamVal)) continue;
            if (stripos($jamVal, 'total') !== false) continue;
            if (! preg_match('/^(\d+)/', $jamVal, $m)) continue;

            $jam = (int)$m[1];
            if ($jam < 10 || $jam > 23) continue;

            $doorValues = [];
            foreach ($colToDoor as $colInt => $systemDoor) {
                $v = $cols[$colInt] ?? 0;
                $doorValues[$systemDoor] = (int)(is_numeric($v) ? $v : 0);
            }

            if (array_sum($doorValues) === 0) continue;

            $rows[] = [
                'jam'   => $jam,
                'doors' => $doorValues,
                'total' => array_sum($doorValues),
            ];
        }

        // ── PSV: merge People Count sheets ──
        // D/F (IN Penta)  → pentacity rows  |  E/G (IN eWalk) → ewalk rows (override ewalk UG/FF doors)
        $ewalkRows      = [];
        $ewalkColToDoor = [];

        if ($isPsv) {
            $ugByJam = $ugXml ? self::parseInPentaSheet($ugXml, $strings) : [];
            $ffByJam = $ffXml ? self::parseInPentaSheet($ffXml, $strings) : [];

            // colToDoor: Pentacity doors only (p1, p2)
            $nextCol = empty($colToDoor) ? 1 : max(array_keys($colToDoor)) + 1;
            if (! empty($ugByJam)) {
                $colToDoor[$nextCol++] = self::PSV_UG_DOOR_1;
                $colToDoor[$nextCol++] = self::PSV_UG_DOOR_2;
            }
            if (! empty($ffByJam)) {
                $colToDoor[$nextCol++] = self::PSV_FF_DOOR_1;
                $colToDoor[$nextCol++] = self::PSV_FF_DOOR_2;
            }

            // ewalkColToDoor: eWalk doors that will be overridden
            if (! empty($ugByJam)) {
                $ewalkColToDoor[] = self::PSV_EWALK_UG_1;
                $ewalkColToDoor[] = self::PSV_EWALK_UG_2;
            }
            if (! empty($ffByJam)) {
                $ewalkColToDoor[] = self::PSV_EWALK_FF_1;
                $ewalkColToDoor[] = self::PSV_EWALK_FF_2;
            }

            $rowsByJam = array_column($rows, null, 'jam');
            $allJams   = array_unique(array_merge(
                array_keys($rowsByJam),
                array_keys($ugByJam),
                array_keys($ffByJam)
            ));

            $rows = [];
            foreach ($allJams as $jam) {
                $row = $rowsByJam[$jam] ?? ['jam' => $jam, 'doors' => [], 'total' => 0];
                $ug  = $ugByJam[$jam]  ?? ['p1' => 0, 'e1' => 0, 'p2' => 0, 'e2' => 0];
                $ff  = $ffByJam[$jam]  ?? ['p1' => 0, 'e1' => 0, 'p2' => 0, 'e2' => 0];

                // Pentacity (IN Penta columns)
                if ($ug['p1'] > 0) { $row['doors'][self::PSV_UG_DOOR_1] = $ug['p1']; $row['total'] += $ug['p1']; }
                if ($ug['p2'] > 0) { $row['doors'][self::PSV_UG_DOOR_2] = $ug['p2']; $row['total'] += $ug['p2']; }
                if ($ff['p1'] > 0) { $row['doors'][self::PSV_FF_DOOR_1] = $ff['p1']; $row['total'] += $ff['p1']; }
                if ($ff['p2'] > 0) { $row['doors'][self::PSV_FF_DOOR_2] = $ff['p2']; $row['total'] += $ff['p2']; }
                if ($row['total'] > 0) $rows[] = $row;

                // eWalk override (IN eWalk columns)
                $ewalkDoors = [];
                $ewalkTotal = 0;
                if ($ug['e1'] > 0) { $ewalkDoors[self::PSV_EWALK_UG_1] = $ug['e1']; $ewalkTotal += $ug['e1']; }
                if ($ug['e2'] > 0) { $ewalkDoors[self::PSV_EWALK_UG_2] = $ug['e2']; $ewalkTotal += $ug['e2']; }
                if ($ff['e1'] > 0) { $ewalkDoors[self::PSV_EWALK_FF_1] = $ff['e1']; $ewalkTotal += $ff['e1']; }
                if ($ff['e2'] > 0) { $ewalkDoors[self::PSV_EWALK_FF_2] = $ff['e2']; $ewalkTotal += $ff['e2']; }
                if ($ewalkTotal > 0) $ewalkRows[] = ['jam' => $jam, 'doors' => $ewalkDoors, 'total' => $ewalkTotal];
            }
        }

        usort($rows,      fn($a, $b) => $a['jam'] <=> $b['jam']);
        usort($ewalkRows, fn($a, $b) => $a['jam'] <=> $b['jam']);

        return [
            'tanggal'        => $tanggal,
            'mall'           => $mall,
            'rows'           => $rows,
            'ewalkRows'      => $ewalkRows,
            'ewalkColToDoor' => $ewalkColToDoor,
            'colToDoor'      => $colToDoor,
            'totalVisitor'   => array_sum(array_column($rows, 'total')),
            'totalEwalk'     => array_sum(array_column($ewalkRows, 'total')),
            'warnings'       => $warnings,
        ];
    }
}
