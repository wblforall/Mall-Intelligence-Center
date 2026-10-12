<?php
/**
 * Kepala bersama halaman Budget Departemen: judul, pilihan dept (admin),
 * tahun, dan tab. Butuh: $dept, $tahun, $tab, $depts, $years, $isAdmin, $url.
 */
if (! function_exists('rpb')) {
    /** Rupiah ringkas tanpa "Rp" untuk tabel padat; nol → tanda pisah. */
    function rpb(float $v, bool $nolStrip = true): string
    {
        if ($nolStrip && abs($v) < 0.5) return '<span class="text-body-tertiary">–</span>';
        return number_format($v, 0, ',', '.');
    }
}
$_tabs = [
    'rekap'     => ['', 'bi-table', 'Rekap'],
    'rencana'   => ['rencana', 'bi-pencil-square', 'Isi Budget'],
    'realisasi' => ['realisasi', 'bi-receipt', 'Realisasi'],
    'pos'       => ['pos', 'bi-list-ol', 'Pos Budget'],
];
$_bawaQ = array_filter(['tahun' => $tahun]);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Budget Departemen</h4>
        <div class="text-muted small">
            <?= esc($dept['name']) ?> · catatan internal budget &amp; realisasi
        </div>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <?php if ($isAdmin): ?>
        <select name="dept" class="form-select form-select-sm" style="min-width:180px" onchange="this.form.submit()" aria-label="Departemen">
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) $dept['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Tahun">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= (int) $y === (int) $tahun ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($_tabs as $k => [$path, $ikon, $label]): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="<?= $url($path, $k === 'pos' ? [] : $_bawaQ) ?>">
            <i class="bi <?= $ikon ?> me-1"></i><?= $label ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
