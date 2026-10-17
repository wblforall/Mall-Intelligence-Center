<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/stock-fisik.css') ?>?v=<?= @filemtime(FCPATH . 'css/stock-fisik.css') ?: 1 ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>
<?php
$fromFmt = date('d M Y', strtotime($from));
$toFmt   = date('d M Y', strtotime($to));

// Pra-hitung status & ringkasan (semua dari data yang sudah ada)
$cB = []; $cV = []; $attnB = 0; $attnV = 0; $masukB = $keluarB = $masukV = $keluarV = $returV = 0; $unitB = 0; $kodeV = 0;
$groups = [];
foreach ($barang as $i => $b) {
    $st = sf_status_barang($b); $barang[$i]['_st'] = $st; $barang[$i]['_g'] = sf_group($b['nama_barang']);
    $cB[$st['key']] = ($cB[$st['key']] ?? 0) + 1; if (sf_attn($st['key'])) $attnB++;
    $masukB += $b['masuk']; $keluarB += $b['keluar']; $unitB += (int) $b['stok_tersedia'];
    $groups[mb_strtolower($barang[$i]['_g'])] = $barang[$i]['_g'];
}
foreach ($batches as $i => $v) {
    $st = sf_status_voucher($v); $batches[$i]['_st'] = $st;
    $cV[$st['key']] = ($cV[$st['key']] ?? 0) + 1; if (sf_attn($st['key'])) $attnV++;
    $masukV += $v['masuk']; $keluarV += $v['keluar']; $returV += $v['retur']; $kodeV += (int) $v['sisa_kode'];
}
ksort($groups, SORT_NATURAL);

// Pintasan periode
$pre = [
    'Bulan ini'   => [date('Y-m-01'), date('Y-m-t')],
    'Bulan lalu'  => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    '3 bulan'     => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')],
    'Tahun ini'   => [date('Y-01-01'), date('Y-12-31')],
];
?>

<div class="page-sf" id="sfSummary">

<?= sf_crumbs([['Stok Fisik', null], ['Summary', null]]) ?>
<?php $ctxQ = 'ret=summary&from=' . $from . '&to=' . $to; ?>
<div class="sf-head">
    <div class="min-w-0">
        <h4 class="sf-title"><i class="bi bi-box-seam"></i>Summary Stok Fisik</h4>
        <div class="sf-sub">Barang &amp; voucher fisik — posisi stok saat ini, mutasi masuk/keluar periode <strong><?= $fromFmt ?> – <?= $toFmt ?></strong></div>
    </div>
    <div class="sf-head-actions">
        <a href="<?= base_url('stock/barang?' . $ctxQ) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box me-1"></i>Kelola Hadiah</a>
        <a href="<?= base_url('stock/voucher?' . $ctxQ) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-ticket-perforated me-1"></i>Kelola Voucher</a>
        <a href="<?= base_url('stock/barang/mutasi?' . $ctxQ) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-right me-1"></i>Laporan Mutasi</a>
    </div>
</div>

<!-- Periode -->
<div class="card sf-panel mb-3"><div class="card-body py-2">
<form method="GET" class="d-flex flex-wrap align-items-end gap-2">
    <div><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
    <div><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
    <div><button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button></div>
    <div class="sf-chips ms-md-2">
        <?php foreach ($pre as $lbl => [$pf, $pt]): $on = ($pf === $from && $pt === $to); ?>
        <a class="sf-chip<?= $on ? ' is-on' : '' ?>" href="<?= base_url('stock/summary?from=' . $pf . '&to=' . $pt) ?>" style="text-decoration:none"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>
</form>
</div></div>

<!-- KPI -->
<div class="sf-kpis k6">
    <div class="sf-kpi t-main sf-wide">
        <div class="sf-kpi-l"><i class="bi bi-cash-stack"></i>Total Nilai Stok</div>
        <div class="sf-kpi-v"><?= sf_rp($totalNilaiBarang + $totalNilaiVoucher) ?></div>
        <div class="sf-kpi-s">barang + voucher tersedia</div>
    </div>
    <div class="sf-kpi t-info">
        <div class="sf-kpi-l"><i class="bi bi-box"></i>Stok Barang</div>
        <div class="sf-kpi-v"><?= sf_rp($totalNilaiBarang) ?></div>
        <div class="sf-kpi-s"><?= count($barang) ?> jenis · <?= sf_n($unitB) ?> unit tersedia</div>
    </div>
    <div class="sf-kpi t-warn">
        <div class="sf-kpi-l"><i class="bi bi-ticket-perforated"></i>Stok Voucher</div>
        <div class="sf-kpi-v"><?= sf_rp($totalNilaiVoucher) ?></div>
        <div class="sf-kpi-s"><?= count($batches) ?> batch · <?= sf_n($kodeV) ?> kode tersedia</div>
    </div>
    <button type="button" class="sf-kpi <?= $attnB ? 't-bad' : 't-ok' ?>" data-sf-kpi="attn" data-sf-target="sfBarang" title="Tampilkan barang yang perlu perhatian">
        <div class="sf-kpi-l"><i class="bi bi-exclamation-triangle"></i>Barang Perlu Aksi</div>
        <div class="sf-kpi-v"><?= $attnB ?></div>
        <div class="sf-kpi-s"><?= sf_kc('kurang', $cB['short'] ?? 0, 'bad') ?><?= sf_kc('menipis', $cB['low'] ?? 0, 'warn') ?><?= sf_kc('habis', $cB['out'] ?? 0, 'none') ?></div>
    </button>
    <button type="button" class="sf-kpi <?= $attnV ? 't-bad' : 't-ok' ?>" data-sf-kpi="attn" data-sf-target="sfVoucher" title="Tampilkan voucher yang perlu perhatian">
        <div class="sf-kpi-l"><i class="bi bi-exclamation-triangle"></i>Voucher Perlu Aksi</div>
        <div class="sf-kpi-v"><?= $attnV ?></div>
        <div class="sf-kpi-s"><?= sf_kc('kedaluwarsa', $cV['exp'] ?? 0, 'bad') ?><?= sf_kc('segera exp', $cV['soon'] ?? 0, 'warn') ?><?= sf_kc('kurang', $cV['short'] ?? 0, 'bad') ?><?= sf_kc('menipis', $cV['low'] ?? 0, 'warn') ?></div>
    </button>
    <div class="sf-kpi">
        <div class="sf-kpi-l"><i class="bi bi-arrow-left-right"></i>Mutasi Periode</div>
        <div class="sf-kpi-v"><span class="sf-pos">+<?= sf_n($masukB + $masukV + $returV) ?></span> <span class="sf-muted">/</span> <span class="sf-neg">-<?= sf_n($keluarB + $keluarV) ?></span></div>
        <div class="sf-kpi-s">masuk / keluar (barang + voucher)</div>
    </div>
</div>

<!-- Barang -->
<div class="card sf-panel" id="sfBarang" data-sf-list data-sf-default-sort="name:asc">
    <div class="sf-panel-h">
        <div class="sf-panel-t"><i class="bi bi-box text-primary"></i>Barang Fisik <small data-sf-count></small></div>
        <a href="<?= base_url('stock/barang?' . $ctxQ) ?>" data-sf-carry class="btn btn-sm btn-link text-decoration-none p-0" title="Buka halaman kelola dengan pencarian/filter yang sama">Kelola <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="sf-tools">
        <label class="sf-search"><i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari barang…" aria-label="Cari barang" autocomplete="off">
            <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
        <div class="sf-chips"><?= sf_chips($cB, count($barang)) ?></div>
        <?php if (count($groups) > 1): ?>
        <select class="form-select form-select-sm" data-sf-group aria-label="Kelompok">
            <option value="">Semua kelompok</option>
            <?php foreach ($groups as $gk => $gl): ?><option value="<?= esc($gk) ?>"><?= esc($gl) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select class="form-select form-select-sm sf-spacer" data-sf-sort aria-label="Urutkan">
            <option value="name:asc" data-type="text">Urut: Nama A–Z</option>
            <option value="sev:asc" data-type="num">Urut: Perlu perhatian dulu</option>
            <option value="nilai:desc" data-type="num">Urut: Nilai stok terbesar</option>
            <option value="tersedia:asc" data-type="num">Urut: Stok tersedia terkecil</option>
            <option value="keluar:desc" data-type="num">Urut: Keluar terbanyak</option>
        </select>
    </div>
    <div class="sf-scroll">
    <table class="sf-tbl sf-cards">
        <thead><tr>
            <th data-sort="name" data-type="text">Barang</th>
            <th data-sort="tersedia" data-type="num" data-first="desc">Stok &amp; Status</th>
            <th class="r" data-sort="masuk" data-type="num" data-first="desc">Masuk</th>
            <th class="r" data-sort="keluar" data-type="num" data-first="desc">Keluar</th>
            <th class="r" data-sort="satuan" data-type="num" data-first="desc">Nilai/Satuan</th>
            <th class="r" data-sort="nilai" data-type="num" data-first="desc">Nilai Stok</th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($barang as $b): $st = $b['_st']; $m = sf_status_meta()[$st['key']]; ?>
        <tr class="sf-item"<?= sf_attrs([
            'data-q' => mb_strtolower($b['nama_barang'] . ' ' . $b['satuan'] . ' ' . $m[0]), 'data-status' => $st['key'], 'data-attn' => sf_attn($st['key']) ? 1 : 0,
            'data-group' => mb_strtolower($b['_g']), 'data-v-name' => mb_strtolower($b['nama_barang']), 'data-v-sev' => $m[3],
            'data-v-tersedia' => $b['stok_tersedia'], 'data-v-masuk' => $b['masuk'], 'data-v-keluar' => $b['keluar'], 'data-v-satuan' => $b['nilai_satuan'], 'data-v-nilai' => $b['nilai_stok'],
            'data-sum-tersedia' => $b['stok_tersedia'], 'data-sum-masuk' => $b['masuk'], 'data-sum-keluar' => $b['keluar'], 'data-sum-nilai' => $b['nilai_stok'],
        ]) ?>>
            <td class="sf-t">
                <div class="sf-name"><?= esc($b['nama_barang']) ?> <span class="sf-muted small fw-normal">(<?= esc($b['satuan']) ?>)</span></div>
            </td>
            <td class="sf-stok" data-label="Stok">
                <div class="sf-stok-n"><strong><?= number_format((int) $b['stok_tersedia']) ?></strong>
                    <?= sf_badge($st['key'], $st['short'] ? 'Reserved melebihi stok sebesar ' . $st['short'] : '') ?>
                    <?php if ($st['reserved'] > 0): ?><span>reserved <?= sf_n($st['reserved']) ?> · bebas <?= sf_n($st['bebas']) ?></span><?php endif; ?>
                </div>
                <?= sf_bar($st) ?>
            </td>
            <td class="r <?= $b['masuk'] ? 'sf-pos' : 'sf-muted' ?>" data-label="Masuk"><?= $b['masuk'] ? '+' . number_format($b['masuk']) : '—' ?></td>
            <td class="r <?= $b['keluar'] ? 'sf-neg' : 'sf-muted' ?>" data-label="Keluar"><?= $b['keluar'] ? '-' . number_format($b['keluar']) : '—' ?></td>
            <td class="r sf-muted small" data-label="Nilai/Satuan"><?= sf_rp($b['nilai_satuan']) ?></td>
            <td class="r fw-semibold" data-label="Nilai Stok"><?= sf_rp($b['nilai_stok']) ?></td>
            <td class="sf-act r"><a href="<?= base_url('stock/barang/' . $b['id'] . '/kartu?ret=summary&from=' . $from . '&to=' . $to) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text"></i> Kartu Stok</a></td>
        </tr>
        <?php endforeach; ?>
        <tr class="sf-empty-row" data-sf-empty <?= empty($barang) ? '' : 'hidden' ?>><td colspan="7"><div class="sf-empty"><i class="bi bi-search"></i>
            <?= empty($barang) ? 'Belum ada barang.' : 'Tidak ada barang yang cocok dengan pencarian/filter.' ?></div></td></tr>
        <tr class="sf-anchor" hidden></tr>
        </tbody>
        <tfoot><tr>
            <td class="sf-t">Total (yang ditampilkan)</td>
            <td data-label="Stok"><span data-sf-sum="tersedia"><?= sf_n($unitB) ?></span> unit</td>
            <td class="r sf-pos" data-label="Masuk">+<span data-sf-sum="masuk"><?= sf_n($masukB) ?></span></td>
            <td class="r sf-neg" data-label="Keluar">-<span data-sf-sum="keluar"><?= sf_n($keluarB) ?></span></td>
            <td class="sf-hide-m"></td>
            <td class="r" data-label="Nilai Stok"><span data-sf-sum="nilai" data-fmt="rp"><?= sf_rp($totalNilaiBarang) ?></span></td>
            <td class="sf-hide-m"></td>
        </tr></tfoot>
    </table>
    </div>
    <div class="sf-legend"><span><i style="background:var(--sf-ok)"></i>stok bebas</span><span><i class="sf-bar-res" style="background:repeating-linear-gradient(135deg,#fbbf24 0 3px,#fbbf2488 3px 6px)"></i>reserved program</span><span>Menipis = bebas ≤ 20% dari stok awal · Kurang = reserved melebihi stok</span></div>
</div>

<!-- Voucher -->
<div class="card sf-panel" id="sfVoucher" data-sf-list data-sf-default-sort="name:asc">
    <div class="sf-panel-h">
        <div class="sf-panel-t"><i class="bi bi-ticket-perforated text-warning"></i>Voucher Fisik <small data-sf-count></small></div>
        <a href="<?= base_url('stock/voucher?' . $ctxQ) ?>" data-sf-carry class="btn btn-sm btn-link text-decoration-none p-0" title="Buka halaman kelola dengan pencarian/filter yang sama">Kelola <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="sf-tools">
        <label class="sf-search"><i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari batch voucher…" aria-label="Cari voucher" autocomplete="off">
            <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
        <div class="sf-chips"><?= sf_chips($cV, count($batches)) ?></div>
        <select class="form-select form-select-sm sf-spacer" data-sf-sort aria-label="Urutkan">
            <option value="name:asc" data-type="text">Urut: Nama A–Z</option>
            <option value="sev:asc" data-type="num">Urut: Perlu perhatian dulu</option>
            <option value="nilai:desc" data-type="num">Urut: Nilai stok terbesar</option>
            <option value="tersedia:asc" data-type="num">Urut: Tersedia terkecil</option>
            <option value="exp:asc" data-type="text">Urut: Kedaluwarsa terdekat</option>
        </select>
    </div>
    <div class="sf-scroll">
    <table class="sf-tbl sf-cards">
        <thead><tr>
            <th data-sort="name" data-type="text">Batch Voucher</th>
            <th data-sort="tersedia" data-type="num" data-first="desc">Tersedia &amp; Status</th>
            <th class="r" data-sort="total" data-type="num" data-first="desc">Total Kode</th>
            <th class="r" data-sort="masuk" data-type="num" data-first="desc">Masuk</th>
            <th class="r" data-sort="keluar" data-type="num" data-first="desc">Keluar</th>
            <th class="r" data-sort="retur" data-type="num" data-first="desc">Retur</th>
            <th class="r" data-sort="satuan" data-type="num" data-first="desc">Nilai/Voucher</th>
            <th class="r" data-sort="nilai" data-type="num" data-first="desc">Nilai Stok</th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($batches as $v): $st = $v['_st']; $m = sf_status_meta()[$st['key']]; ?>
        <tr class="sf-item"<?= sf_attrs([
            'data-q' => mb_strtolower($v['nama_voucher'] . ' ' . $m[0]), 'data-status' => $st['key'], 'data-attn' => sf_attn($st['key']) ? 1 : 0,
            'data-v-name' => mb_strtolower($v['nama_voucher']), 'data-v-sev' => $m[3], 'data-v-exp' => $v['expired_date'] ?: '9999-12-31',
            'data-v-tersedia' => $v['sisa_kode'], 'data-v-total' => $v['total_kode'], 'data-v-masuk' => $v['masuk'], 'data-v-keluar' => $v['keluar'],
            'data-v-retur' => $v['retur'], 'data-v-satuan' => $v['nilai_voucher'], 'data-v-nilai' => $v['nilai_stok'],
            'data-sum-tersedia' => $v['sisa_kode'], 'data-sum-total' => $v['total_kode'], 'data-sum-masuk' => $v['masuk'], 'data-sum-keluar' => $v['keluar'],
            'data-sum-retur' => $v['retur'], 'data-sum-nilai' => $v['nilai_stok'],
        ]) ?>>
            <td class="sf-t">
                <div class="sf-name"><?= esc($v['nama_voucher']) ?></div>
                <?php if ($v['expired_date']): ?><div class="sf-note">Exp <?= date('d M Y', strtotime($v['expired_date'])) ?></div><?php endif; ?>
            </td>
            <td class="sf-stok" data-label="Tersedia">
                <div class="sf-stok-n"><strong><?= number_format((int) $v['sisa_kode']) ?></strong>
                    <?= sf_badge($st['key'], $st['short'] ? 'Reserved melebihi stok sebesar ' . $st['short'] : '') ?>
                    <?php if ($st['reserved'] > 0): ?><span>reserved <?= sf_n($st['reserved']) ?> · bebas <?= sf_n($st['bebas']) ?></span><?php endif; ?>
                </div>
                <?= sf_bar($st) ?>
            </td>
            <td class="r" data-label="Total Kode"><?= number_format((int) $v['total_kode']) ?></td>
            <td class="r <?= $v['masuk'] ? 'sf-pos' : 'sf-muted' ?>" data-label="Masuk"><?= $v['masuk'] ? '+' . number_format($v['masuk']) : '—' ?></td>
            <td class="r <?= $v['keluar'] ? 'sf-neg' : 'sf-muted' ?>" data-label="Keluar"><?= $v['keluar'] ? '-' . number_format($v['keluar']) : '—' ?></td>
            <td class="r <?= $v['retur'] ? 'sf-inf' : 'sf-muted' ?>" data-label="Retur"><?= $v['retur'] ? '+' . number_format($v['retur']) : '—' ?></td>
            <td class="r sf-muted small" data-label="Nilai/Voucher"><?= sf_rp($v['nilai_voucher']) ?></td>
            <td class="r fw-semibold" data-label="Nilai Stok"><?= sf_rp($v['nilai_stok']) ?></td>
            <td class="sf-act r"><a href="<?= base_url('stock/voucher/' . $v['id'] . '/kartu?ret=summary&from=' . $from . '&to=' . $to) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text"></i> Kartu Stok</a></td>
        </tr>
        <?php endforeach; ?>
        <tr class="sf-empty-row" data-sf-empty <?= empty($batches) ? '' : 'hidden' ?>><td colspan="9"><div class="sf-empty"><i class="bi bi-search"></i>
            <?= empty($batches) ? 'Belum ada batch voucher.' : 'Tidak ada voucher yang cocok dengan pencarian/filter.' ?></div></td></tr>
        <tr class="sf-anchor" hidden></tr>
        </tbody>
        <tfoot><tr>
            <td class="sf-t">Total (yang ditampilkan)</td>
            <td data-label="Tersedia"><span data-sf-sum="tersedia"><?= sf_n($kodeV) ?></span> kode</td>
            <td class="r" data-label="Total Kode"><span data-sf-sum="total"><?= sf_n(array_sum(array_column($batches, 'total_kode'))) ?></span></td>
            <td class="r sf-pos" data-label="Masuk">+<span data-sf-sum="masuk"><?= sf_n($masukV) ?></span></td>
            <td class="r sf-neg" data-label="Keluar">-<span data-sf-sum="keluar"><?= sf_n($keluarV) ?></span></td>
            <td class="r sf-inf" data-label="Retur">+<span data-sf-sum="retur"><?= sf_n($returV) ?></span></td>
            <td class="sf-hide-m"></td>
            <td class="r" data-label="Nilai Stok"><span data-sf-sum="nilai" data-fmt="rp"><?= sf_rp($totalNilaiVoucher) ?></span></td>
            <td class="sf-hide-m"></td>
        </tr></tfoot>
    </table>
    </div>
    <div class="sf-legend"><span>Segera Exp = kedaluwarsa ≤ 14 hari · Kedaluwarsa = lewat tanggal tapi masih ada kode tersedia · Menipis = bebas ≤ 20% dari total kode</span></div>
</div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/stock-fisik.js') ?>?v=<?= @filemtime(FCPATH . 'js/stock-fisik.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
