<?php helper('tanggal'); ?><?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-modul.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-modul.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('evRp')) {
    function evRp($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
}
$imgExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$spBase  = 'uploads/sponsor-realisasi/' . $event['id'] . '/';

// Ringkasan per sponsor (rumus sama dengan tampilan lama)
$rows = [];
$nTerpenuhi = $nSebagian = $nBelum = 0;
foreach ($sponsors as $sp) {
    $rList  = $realisasi[$sp['id']] ?? [];
    $rTotal = array_sum(array_column($rList, 'nilai'));
    $pct    = $sp['nilai'] > 0 ? min(100, round($rTotal / $sp['nilai'] * 100)) : 0;
    $state  = ($sp['nilai'] > 0 && $rTotal >= $sp['nilai']) ? 'terpenuhi' : ($rTotal > 0 ? 'sebagian' : 'belum');
    if ($state === 'terpenuhi') $nTerpenuhi++; elseif ($state === 'sebagian') $nSebagian++; else $nBelum++;
    $rows[] = ['sp' => $sp, 'rList' => $rList, 'rTotal' => $rTotal, 'pct' => $pct, 'state' => $state];
}
$nCash   = count(array_filter($sponsors, fn($s) => $s['jenis'] === 'cash'));
$nBarang = count($sponsors) - $nCash;
$sisaAll = max(0, $totalNilai - $totalRealisasi);
$barAll  = $pctGlobal >= 100 ? 'ok' : ($pctGlobal >= 50 ? 'mid' : 'low');
$stateLabel = ['terpenuhi' => 'Terpenuhi', 'sebagian' => 'Sebagian', 'belum' => 'Belum ada realisasi'];
$stateChip  = ['terpenuhi' => 'ok', 'sebagian' => 'warn', 'belum' => 'mute'];

$actions = '';
if ($canEdit) {
    $actions = '<button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Sponsor</button>';
}
?>

<div class="page-evm evm-sponsors" id="evmRoot" data-key="spon-<?= $event['id'] ?>" data-unit="sponsor">

<?= view('partials/evm_head', ['event' => $event, 'module' => 'sponsors', 'completion' => $completion, 'canEdit' => $canEdit,
    'title' => 'Sponsorship', 'icon' => 'bi-award-fill', 'actions' => $actions]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Sponsor</div>
        <div class="ev-kpi-v"><?= count($sponsors) ?></div>
        <div class="ev-kpi-s">Cash <?= $nCash ?> · Barang <?= $nBarang ?></div>
    </div>
    <div class="ev-kpi t-ok">
        <div class="ev-kpi-l">Nilai Cash</div>
        <div class="ev-kpi-v ev-c-ok"><?= evRp($totalCash) ?></div>
        <div class="ev-kpi-s">dari <?= $nCash ?> sponsor cash</div>
    </div>
    <div class="ev-kpi t-cyan">
        <div class="ev-kpi-l">Barang / In-Kind</div>
        <div class="ev-kpi-v ev-c-cyan"><?= evRp($totalBarang) ?></div>
        <div class="ev-kpi-s">dari <?= $nBarang ?> sponsor barang</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Nilai Deal</div>
        <div class="ev-kpi-v"><?= evRp($totalNilai) ?></div>
        <div class="ev-kpi-s">Terpenuhi <?= $nTerpenuhi ?> · Sebagian <?= $nSebagian ?> · Belum <?= $nBelum ?></div>
    </div>
    <div class="ev-kpi <?= $pctGlobal >= 100 ? 't-ok' : ($pctGlobal >= 50 ? 't-warn' : 't-bad') ?>">
        <div class="ev-kpi-l">Realisasi</div>
        <div class="ev-kpi-v"><?= evRp($totalRealisasi) ?></div>
        <div class="ev-kpi-s"><?= $totalNilai > 0 ? $pctGlobal . '% dari nilai deal · sisa ' . evRp($sisaAll) : 'belum ada nilai deal' ?></div>
        <?php if ($totalNilai > 0): ?><div class="ev-bar ev-bar-<?= $barAll ?>"><i style="width:<?= $pctGlobal ?>%"></i></div><?php endif; ?>
    </div>
</div>

<?php if (empty($sponsors)): ?>
<div class="ev-empty">
    <i class="bi bi-award"></i>
    <h5>Belum ada sponsor untuk event ini</h5>
    <p>Catat sponsor beserta nilai deal, lalu input realisasi penerimaannya di sini.</p>
    <?php if ($canEdit): ?>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Sponsor Pertama</button>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Pencarian & filter -->
<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari sponsor atau barang…" autocomplete="off" aria-label="Cari sponsor">
    </div>
    <select class="ev-select" data-ev-filter="jenis" aria-label="Filter jenis">
        <option value="">Semua jenis</option>
        <option value="cash">Cash</option>
        <option value="barang">Barang / In-Kind</option>
    </select>
    <select class="ev-select" data-ev-filter="state" aria-label="Filter progres realisasi">
        <option value="">Semua progres</option>
        <option value="belum">Belum ada realisasi</option>
        <option value="sebagian">Sebagian</option>
        <option value="terpenuhi">Terpenuhi</option>
    </select>
    <select class="ev-select" id="evSort" aria-label="Urutkan">
        <option value="" data-dir="asc">Urutan: input</option>
        <option value="name" data-type="str" data-dir="asc">Nama A–Z</option>
        <option value="deal" data-dir="desc">Nilai deal terbesar</option>
        <option value="sisa" data-dir="desc">Sisa terbesar</option>
        <option value="pct" data-dir="asc">Progres terendah</option>
    </select>
    <div class="ev-tools-end">
        <span class="ev-count" id="evCount" aria-live="polite"></span>
        <button type="button" class="ev-btn ev-btn-sm" id="evExpand" title="Buka semua sponsor"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="ev-btn ev-btn-sm" id="evCollapse" title="Tutup semua sponsor"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada sponsor yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="ev-btn" id="evReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<section class="ev-group">
<div class="ev-list">
<?php foreach ($rows as $n => $r):
    $sp     = $r['sp'];
    $items  = $itemsBySponsors[$sp['id']] ?? [];
    $rList  = $r['rList'];
    $rTotal = $r['rTotal'];
    $pct    = $r['pct'];
    $state  = $r['state'];
    $nilai  = (int) $sp['nilai'];
    $sisa   = $nilai - $rTotal;
    $barCls = $state === 'terpenuhi' ? 'ok' : ($state === 'sebagian' ? 'mid' : 'low');
    $search = mb_strtolower($sp['nama_sponsor'] . ' ' . ($sp['detail'] ?? '') . ' ' . implode(' ', array_column($items, 'deskripsi_barang')));
    $sid    = $sp['id'];
?>
<article class="ev-card<?= $state === 'terpenuhi' ? ' is-ok' : '' ?>" id="sponsor-<?= $sid ?>"
         data-name="<?= esc($sp['nama_sponsor'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
         data-jenis="<?= $sp['jenis'] === 'cash' ? 'cash' : 'barang' ?>" data-state="<?= $state ?>"
         data-deal="<?= $nilai ?>" data-sisa="<?= $sisa ?>" data-pct="<?= $pct ?>" data-order="<?= $n ?>">
    <div class="ev-card-head" data-ev-toggle>
        <button type="button" class="ev-chev" aria-expanded="false" aria-controls="body-<?= $sid ?>" aria-label="Buka atau tutup rincian sponsor"><i class="bi bi-chevron-right"></i></button>
        <div class="min-w-0">
            <div class="ev-card-name">
                <span class="ev-card-t"><?= esc($sp['nama_sponsor']) ?></span>
                <?php if ($sp['jenis'] === 'cash'): ?>
                <span class="ev-chip ev-chip-ok"><i class="bi bi-cash-coin"></i>Cash</span>
                <?php else: ?>
                <span class="ev-chip ev-chip-blue"><i class="bi bi-box-seam"></i>Barang / In-Kind</span>
                <?php endif; ?>
                <span class="ev-chip ev-chip-<?= $stateChip[$state] ?>"><?= $stateLabel[$state] ?></span>
            </div>
            <div class="ev-card-meta">
                <?php if ($sp['detail']): ?><span><i class="bi bi-card-text"></i><?= esc($sp['detail']) ?></span><?php endif; ?>
                <?php if ($sp['jenis'] !== 'cash' && ! empty($items)): ?><span><i class="bi bi-box"></i><?= count($items) ?> item barang</span><?php endif; ?>
                <span><i class="bi bi-receipt"></i><?= count($rList) ?> realisasi</span>
            </div>
        </div>
        <div class="ev-fig">
            <div class="ev-fig-row">
                <div><span class="ev-fig-l">Nilai deal</span><b><?= evRp($nilai) ?></b></div>
                <div><span class="ev-fig-l">Realisasi</span><b class="ev-c-cyan"><?= evRp($rTotal) ?></b></div>
                <div><span class="ev-fig-l"><?= $sisa < 0 ? 'Lebih' : 'Sisa' ?></span><b class="<?= $sisa > 0 ? 'ev-c-warn' : 'ev-c-ok' ?>"><?= evRp(abs($sisa)) ?></b></div>
            </div>
            <div class="ev-fig-bar">
                <div class="ev-bar ev-bar-<?= $barCls ?>"><i style="width:<?= $pct ?>%"></i></div>
                <span class="ev-pct"><?= $nilai > 0 ? $pct . '%' : '—' ?></span>
            </div>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <?php if ($canEdit): ?>
            <button type="button" class="ev-btn ev-btn-sm ev-btn-ok" data-ev-open="formReal<?= $sid ?>" title="Input realisasi penerimaan">
                <i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Realisasi</span>
            </button>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi sponsor"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li>
                        <button type="button" class="dropdown-item edit-btn"
                            data-id="<?= $sid ?>"
                            data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>"
                            data-jenis="<?= esc($sp['jenis'], 'attr') ?>"
                            data-nilai="<?= number_format($sp['nilai'], 0, ',', '.') ?>"
                            data-detail="<?= esc($sp['detail'], 'attr') ?>"
                            data-items="<?= esc(json_encode($items), 'attr') ?>"><i class="bi bi-pencil"></i>Edit sponsor</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/sponsors/' . $sid . '/delete') ?>" onsubmit="return confirm('Hapus sponsor ini?')">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus sponsor</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ev-card-body" id="body-<?= $sid ?>" hidden>
        <?php if ($sp['jenis'] === 'barang' && ! empty($items)): ?>
        <div class="ev-sec">
            <div class="ev-sec-h"><span class="ev-sec-t"><i class="bi bi-box-seam"></i>Daftar barang <span class="ev-chip ev-chip-mute"><?= count($items) ?></span></span></div>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl ev-stack">
                <thead><tr><th>Barang</th><th class="ev-num">Qty</th><th class="ev-num">Nilai / pcs</th><th class="ev-num">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($items as $itm):
                    $line = (int) $itm['nilai'] * max(1, (int) ($itm['qty'] ?? 1)); ?>
                <tr>
                    <td data-l="Barang"><?= esc($itm['deskripsi_barang'] ?: '—') ?></td>
                    <td class="ev-num" data-l="Qty"><?= $itm['qty'] ? number_format($itm['qty']) : '—' ?></td>
                    <td class="ev-num" data-l="Nilai / pcs"><?= evRp($itm['nilai']) ?></td>
                    <td class="ev-num" data-l="Subtotal"><?= evRp($line) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="ev-sec s-cyan">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-receipt"></i>Realisasi penerimaan
                    <?php if ($rTotal > 0): ?><span class="ev-chip ev-chip-mute"><?= evRp($rTotal) ?></span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="formReal<?= $sid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Input realisasi</button>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="formReal<?= $sid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/sponsors/' . $sid . '/realisasi/add') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-2 mb-2">
                        <div class="col-md-3 col-6">
                            <label class="form-label">Tanggal</label>
                            <?php /* Realisasi = pembayaran sponsor diterima → bisa sebelum event mulai (DP)
                                     atau setelah selesai (pelunasan). Tak dibatasi periode event; hanya
                                     tak boleh masa depan. */ ?>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Nilai Realisasi (Rp)</label>
                            <input type="text" name="nilai" class="form-control form-control-sm currency-input" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Keterangan realisasi...">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-image me-1"></i>Foto Dokumentasi</label>
                            <input type="file" name="file_foto" class="form-control form-control-sm" accept="image/*,.pdf">
                            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-receipt me-1"></i>Bukti Tanda Terima</label>
                            <input type="file" name="file_terima" class="form-control form-control-sm" accept="image/*,.pdf">
                            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan Realisasi</button>
                        <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="formReal<?= $sid ?>">Batal</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (! empty($rList)): ?>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl ev-stack">
                <thead><tr><th>Tanggal</th><th class="ev-num">Nilai</th><th>Catatan</th><th>Foto dokumentasi</th><th>Tanda terima</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($rList as $rr): ?>
                <tr>
                    <td class="ev-nowrap" data-l="Tanggal"><?= $rr['tanggal'] ? tgl_indo_pendek($rr['tanggal'], false) : '—' ?></td>
                    <td class="ev-num" data-l="Nilai"><?= $rr['nilai'] ? evRp($rr['nilai']) : '—' ?></td>
                    <td data-l="Catatan"><?= esc($rr['catatan'] ?: '—') ?><span class="ev-who"><i class="bi bi-person"></i>PIC: <?= esc($rr['pengisi'] ?? '—') ?></span></td>
                    <?php foreach (['file_foto' => 'Foto', 'file_terima' => 'Tanda terima'] as $fk => $fl): ?>
                    <td data-l="<?= $fl ?>">
                        <?php if ($rr[$fk]):
                            $fext = strtolower(pathinfo($rr[$fk], PATHINFO_EXTENSION)); $furl = base_url($spBase . $rr[$fk]); ?>
                        <a class="ev-thumb" href="<?= $furl ?>" target="_blank" title="<?= $fl ?>">
                            <?php if (in_array($fext, $imgExts)): ?><img src="<?= $furl ?>" alt="<?= $fl ?>" loading="lazy">
                            <?php else: ?><i class="bi bi-file-earmark-<?= $fext === 'pdf' ? 'pdf' : 'fill' ?>"></i><?php endif; ?>
                        </a>
                        <?php else: ?><span class="ev-c-mute">—</span><?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if ($canEdit): ?>
                    <td class="ev-act-cell">
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/sponsors/' . $sid . '/realisasi/' . $rr['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus realisasi ini?')">
                            <?= csrf_field() ?>
                            <button class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus realisasi" aria-label="Hapus realisasi"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total</td><td class="ev-num"><?= evRp($rTotal) ?></td><td colspan="<?= $canEdit ? 4 : 3 ?>"></td></tr></tfoot>
            </table>
            </div>
            <?php elseif (! $canEdit): ?>
            <div class="ev-empty-sm">Belum ada realisasi.</div>
            <?php else: ?>
            <div class="ev-empty-sm">Belum ada realisasi. Klik "Input realisasi" saat pembayaran diterima.</div>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>

<div class="ev-foot">
    <span class="ev-foot-l"><?= count($sponsors) ?> sponsor</span>
    <span class="ev-foot-v"><?= evRp($totalNilai) ?><small>nilai deal</small></span>
    <span class="ev-foot-v ev-c-cyan"><?= evRp($totalRealisasi) ?><small>realisasi (<?= $pctGlobal ?>%)</small></span>
</div>
<?php endif; ?>

</div><!-- /page-evm -->

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/sponsors/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Sponsor <span class="text-danger">*</span></label>
        <input type="text" name="nama_sponsor" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jenis</label>
        <select name="jenis" class="form-select" id="addJenis">
            <option value="cash">Cash</option>
            <option value="barang">Barang / In-Kind</option>
        </select>
    </div>
    <div id="addCashSection">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nilai (Rp)</label>
            <input type="text" name="nilai" class="form-control currency-input" value="0">
        </div>
    </div>
    <div id="addBarangSection" style="display:none">
        <label class="form-label small fw-semibold">Daftar Barang</label>
        <div class="mb-1 row g-1 px-1">
            <div class="col-5"><small class="text-muted">Nama Barang</small></div>
            <div class="col-2"><small class="text-muted">Qty</small></div>
            <div class="col-4"><small class="text-muted">Nilai Estimasi (Rp)</small></div>
        </div>
        <div id="addItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addItemRow('add')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
        <div class="mt-2 text-end small fw-semibold">Total: <span id="addBarangTotal">Rp 0</span></div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="detail" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Sponsor</label>
        <input type="text" name="nama_sponsor" id="eNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jenis</label>
        <select name="jenis" id="eJenis" class="form-select">
            <option value="cash">Cash</option>
            <option value="barang">Barang / In-Kind</option>
        </select>
    </div>
    <div id="editCashSection">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nilai (Rp)</label>
            <input type="text" name="nilai" id="eNilai" class="form-control currency-input">
        </div>
    </div>
    <div id="editBarangSection">
        <label class="form-label small fw-semibold">Daftar Barang</label>
        <div class="mb-1 row g-1 px-1">
            <div class="col-5"><small class="text-muted">Nama Barang</small></div>
            <div class="col-2"><small class="text-muted">Qty</small></div>
            <div class="col-4"><small class="text-muted">Nilai Estimasi (Rp)</small></div>
        </div>
        <div id="editItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addItemRow('edit')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
        <div class="mt-2 text-end small fw-semibold">Total: <span id="editBarangTotal">Rp 0</span></div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="detail" id="eDetail" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }
function parseCurrency(v) { return parseInt(v.replace(/[^0-9]/g, '')) || 0; }

function updateBarangTotal(prefix) {
    let total = 0;
    document.getElementById(prefix + 'ItemsContainer').querySelectorAll('.item-nilai').forEach(inp => {
        total += parseCurrency(inp.value);
    });
    document.getElementById(prefix + 'BarangTotal').textContent = formatRp(total);
}

const _escAttr = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function addItemRow(prefix, desk, qty, nilai) {
    const container = document.getElementById(prefix + 'ItemsContainer');
    const row = document.createElement('div');
    row.className = 'row g-1 mb-1 item-row';
    row.innerHTML =
        '<div class="col-5"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Nama barang" value="' + _escAttr(desk||'') + '"></div>' +
        '<div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="0" min="0" value="' + (qty||'') + '"></div>' +
        '<div class="col-4"><input type="text" name="nilai_item[]" class="form-control form-control-sm item-nilai currency-input" placeholder="0" value="' + (nilai ? parseInt(nilai).toLocaleString('id-ID') : '') + '"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row"><i class="bi bi-x"></i></button></div>';
    container.appendChild(row);
    row.querySelector('.item-nilai').addEventListener('input', function() {
        let n = parseCurrency(this.value); this.value = n.toLocaleString('id-ID');
        updateBarangTotal(prefix);
    });
    row.querySelector('.remove-row').addEventListener('click', function() {
        row.remove(); updateBarangTotal(prefix);
    });
    updateBarangTotal(prefix);
}

function toggleJenis(jenis, prefix) {
    document.getElementById(prefix + 'CashSection').style.display  = jenis === 'cash'   ? '' : 'none';
    document.getElementById(prefix + 'BarangSection').style.display = jenis === 'barang' ? '' : 'none';
}

<?php if ($canEdit): ?>
document.getElementById('addModal').addEventListener('show.bs.modal', function() {
    const c = document.getElementById('addItemsContainer');
    if (c.children.length === 0) addItemRow('add');
});
document.getElementById('addJenis').addEventListener('change', function() { toggleJenis(this.value, 'add'); });

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const jenis = this.dataset.jenis;
        document.getElementById('editForm').action = '<?= base_url('events/'.$event['id'].'/sponsors/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eNama').value   = this.dataset.nama;
        document.getElementById('eJenis').value  = jenis;
        document.getElementById('eNilai').value  = this.dataset.nilai;
        document.getElementById('eDetail').value = this.dataset.detail;
        const container = document.getElementById('editItemsContainer');
        container.innerHTML = '';
        if (jenis === 'barang') {
            const items = JSON.parse(this.dataset.items || '[]');
            if (items.length > 0) {
                items.forEach(itm => addItemRow('edit', itm.deskripsi_barang||'', itm.qty||'', itm.nilai||0));
            } else {
                addItemRow('edit');
            }
        }
        toggleJenis(jenis, 'edit');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
document.getElementById('eJenis').addEventListener('change', function() {
    const jenis = this.value;
    if (jenis === 'barang') {
        const c = document.getElementById('editItemsContainer');
        if (c.children.length === 0) addItemRow('edit');
    }
    toggleJenis(jenis, 'edit');
});
<?php endif; ?>

document.querySelectorAll('.currency-input:not(.item-nilai)').forEach(inp => {
    inp.addEventListener('input', function() { let n = parseCurrency(this.value); this.value = n.toLocaleString('id-ID'); });
});
</script>
<script src="<?= base_url('js/events-modul.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-modul.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
