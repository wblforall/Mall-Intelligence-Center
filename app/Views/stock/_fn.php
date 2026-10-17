<?php
/**
 * Pembantu tampilan Stok Fisik (hanya derivasi dari data yang sudah ada).
 * Dipakai: stock/summary, stock/barang/index, stock/voucher/index.
 */
if (! function_exists('sf_rp')) {
    function sf_rp($n): string { return 'Rp ' . number_format((int) $n, 0, ',', '.'); }
}
if (! function_exists('sf_n')) {
    function sf_n($n): string { return number_format((int) $n, 0, ',', '.'); }
}

/** Ambang tunggal: stok "menipis" bila bebas <= 20% dari stok dasar. */
if (! function_exists('sf_low_ratio')) {
    function sf_low_ratio(): float { return 0.2; }
}

/** Kelompok barang = teks sebelum " - " pada nama (mis. "POP JOY - Boneka"). */
if (! function_exists('sf_group')) {
    function sf_group(string $nama): string
    {
        $p = preg_split('/\s+-\s+/', trim($nama), 2);
        return (count($p) === 2 && trim($p[0]) !== '') ? trim($p[0]) : 'Lainnya';
    }
}

/** Label & warna tiap status (satu sumber untuk badge + filter). */
if (! function_exists('sf_status_meta')) {
    function sf_status_meta(): array
    {
        return [
            'exp'   => ['Kedaluwarsa',  'is-bad',  'bi-calendar-x',            1],
            'short' => ['Kurang',       'is-bad',  'bi-exclamation-octagon',   2],
            'out'   => ['Habis',        'is-none', 'bi-slash-circle',          6],
            'soon'  => ['Segera Exp',   'is-warn', 'bi-hourglass-split',       3],
            'low'   => ['Menipis',      'is-warn', 'bi-exclamation-triangle',  4],
            'ok'    => ['Cukup',        'is-ok',   'bi-check-circle',          5],
        ];
    }
}

/**
 * Status stok barang. 'short' = reserved melebihi stok (kebutuhan program
 * lebih besar dari yang ada), termasuk stok minus.
 */
if (! function_exists('sf_status_barang')) {
    function sf_status_barang(array $b): array
    {
        $tersedia = (int) $b['stok_tersedia'];
        $reserved = (int) ($b['stok_reserved'] ?? 0);
        $bebas    = max(0, $tersedia - $reserved);
        $base     = max((int) ($b['stok_awal'] ?? 0), $tersedia, 1);
        $short    = max(0, $reserved - $tersedia);
        if ($short > 0)                                        $k = 'short';
        elseif ($tersedia <= 0)                                $k = 'out';
        elseif ($bebas / $base <= sf_low_ratio())              $k = 'low';
        else                                                   $k = 'ok';
        return ['key' => $k, 'tersedia' => $tersedia, 'reserved' => $reserved, 'bebas' => $bebas, 'base' => $base, 'short' => $short];
    }
}

if (! function_exists('sf_status_voucher')) {
    function sf_status_voucher(array $v): array
    {
        $sisa     = (int) $v['sisa_kode'];
        $total    = (int) $v['total_kode'];
        $reserved = (int) ($v['stok_reserved'] ?? 0);
        $bebas    = max(0, $sisa - $reserved);
        $base     = max($total, $sisa, 1);
        $short    = max(0, $reserved - $sisa);
        $exp      = $v['expired_date'] ?? null;
        $hari     = $exp ? (int) floor((strtotime($exp) - strtotime(date('Y-m-d'))) / 86400) : null;
        if ($sisa > 0 && $hari !== null && $hari < 0)          $k = 'exp';
        elseif ($sisa <= 0 && $short <= 0)                     $k = 'out';
        elseif ($short > 0)                                    $k = 'short';
        elseif ($hari !== null && $hari <= 14)                 $k = 'soon';
        elseif ($bebas / $base <= sf_low_ratio())              $k = 'low';
        else                                                   $k = 'ok';
        return ['key' => $k, 'tersedia' => $sisa, 'reserved' => $reserved, 'bebas' => $bebas, 'base' => $base, 'short' => $short, 'hari' => $hari];
    }
}

/** Perlu perhatian = bukan Cukup dan bukan Habis (habis tanpa kebutuhan = riwayat). */
if (! function_exists('sf_attn')) {
    function sf_attn(string $key): bool { return in_array($key, ['exp', 'short', 'soon', 'low'], true); }
}

if (! function_exists('sf_badge')) {
    function sf_badge(string $key, string $extra = ''): string
    {
        $m = sf_status_meta()[$key];
        return '<span class="sf-badge ' . $m[1] . '"' . ($extra !== '' ? ' title="' . esc($extra) . '"' : '') . '><i class="bi ' . $m[2] . '"></i>' . $m[0] . '</span>';
    }
}

/** Bilah level stok: bagian bebas + bagian reserved, terhadap stok dasar. */
if (! function_exists('sf_bar')) {
    function sf_bar(array $st): string
    {
        $base = max(1, $st['base']);
        $ters = max(0, $st['tersedia']);
        $res  = min($st['reserved'], $ters);
        $wBeb = round(min(100, $st['bebas'] / $base * 100), 1);
        $wRes = round(min(100 - $wBeb, $res / $base * 100), 1);
        $tone = ['ok' => 'ok', 'low' => 'warn', 'soon' => 'warn', 'short' => 'bad', 'exp' => 'bad', 'out' => 'none'][$st['key']];
        return '<div class="sf-bar is-' . $tone . '" role="img" aria-label="Level stok"><i class="sf-bar-free" style="width:' . $wBeb . '%"></i><i class="sf-bar-res" style="width:' . $wRes . '%"></i></div>';
    }
}

/** Chip filter status. $counts = [key => jumlah]. */
if (! function_exists('sf_chips')) {
    function sf_chips(array $counts, int $total): string
    {
        $meta = sf_status_meta();
        $attn = 0;
        foreach ($counts as $k => $n) if (sf_attn($k)) $attn += $n;
        $h  = '<button type="button" class="sf-chip is-on" data-sf-filter="all">Semua <b>' . $total . '</b></button>';
        if ($attn > 0) $h .= '<button type="button" class="sf-chip" data-sf-filter="attn"><span class="dot is-warn"></span>Perlu perhatian <b>' . $attn . '</b></button>';
        $order = ['exp', 'short', 'soon', 'low', 'ok', 'out'];
        foreach ($order as $k) {
            if (empty($counts[$k])) continue;
            $tone = ['is-bad' => 'is-bad', 'is-warn' => 'is-warn', 'is-ok' => 'is-ok', 'is-none' => ''][$meta[$k][1]];
            $h .= '<button type="button" class="sf-chip" data-sf-filter="status:' . $k . '"><span class="dot ' . $tone . '"></span>' . $meta[$k][0] . ' <b>' . $counts[$k] . '</b></button>';
        }
        return $h;
    }
}

/** Atribut data-* untuk baris .sf-item. */
if (! function_exists('sf_attrs')) {
    function sf_attrs(array $a): string
    {
        $h = '';
        foreach ($a as $k => $v) $h .= ' ' . $k . '="' . esc((string) $v, 'attr') . '"';
        return $h;
    }
}

/** Lencana kecil untuk subteks KPI; nol dibuat pudar. */
if (! function_exists('sf_kc')) {
    function sf_kc(string $label, int $n, string $tone): string
    {
        return '<span class="sf-badge is-' . ($n > 0 ? $tone : 'none') . ($n > 0 ? '' : ' z') . '">' . $label . ' ' . $n . '</span>';
    }
}

/** Konteks periode (from/to) dari query, tervalidasi → "&from=..&to=.." atau "". */
if (! function_exists('sf_period_qs')) {
    function sf_period_qs(): string
    {
        $r = service('request'); $f = (string) $r->getGet('from'); $t = (string) $r->getGet('to'); $q = '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) $q .= '&from=' . $f;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) $q .= '&to=' . $t;
        return $q;
    }
}
/** URL Summary dengan periode yang sedang dibawa. */
if (! function_exists('sf_summary_url')) {
    function sf_summary_url(): string
    {
        $qs = sf_period_qs();
        return base_url('stock/summary') . ($qs ? '?' . ltrim($qs, '&') : '');
    }
}
/** Breadcrumb: [[label, url|null], ...]; yang terakhir = halaman aktif. */
if (! function_exists('sf_crumbs')) {
    function sf_crumbs(array $items): string
    {
        $h = '<nav class="sf-crumbs" aria-label="Breadcrumb"><i class="bi bi-box-seam"></i>';
        $last = count($items) - 1;
        foreach ($items as $i => [$lbl, $url]) {
            $h .= $i === 0 ? ' ' : ' <i class="bi bi-chevron-right"></i> ';
            $h .= ($url && $i !== $last) ? '<a href="' . esc($url, 'attr') . '">' . esc($lbl) . '</a>' : '<span aria-current="page">' . esc($lbl) . '</span>';
        }
        return $h . '</nav>';
    }
}
