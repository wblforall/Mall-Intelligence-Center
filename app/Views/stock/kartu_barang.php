<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>
<style>
/* Breadcrumb Kartu Stok: selaras dengan halaman Stok Fisik lain (stock-fisik.css tidak dimuat di sini) */
.sf-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .45rem; font-size: .78rem; color: var(--bs-secondary-color, #94a3b8); }
.sf-crumbs a { color: inherit; text-decoration: none; }
.sf-crumbs a:hover { text-decoration: underline; color: var(--bs-body-color); }
.sf-crumbs .bi-chevron-right { font-size: .6rem; opacity: .6; }
.sf-crumbs [aria-current] { color: var(--bs-body-color); font-weight: 600; }
</style>
<?php
$ret = in_array((string) service('request')->getGet('ret'), ['summary', 'barang', 'voucher'], true) ? (string) service('request')->getGet('ret') : 'summary';
$pq  = '?from=' . esc($from) . '&to=' . esc($to);
if ($ret === 'barang')       { $backUrl = base_url('stock/barang' . $pq . '#barang-' . ($barang['id'] ?? 0));  $backLbl = 'Kembali ke Stock Hadiah'; }
elseif ($ret === 'voucher')  { $backUrl = base_url('stock/voucher' . $pq . '#batch-' . ($batch['id'] ?? 0)); $backLbl = 'Kembali ke Stock Voucher'; }
else                         { $backUrl = base_url('stock/summary' . $pq); $backLbl = 'Kembali ke Summary Stok'; }
$kartuNama = 'barang';
?>
<div class="sf-crumbs-wrap" style="font-size:.78rem;margin-bottom:.55rem">
<?= sf_crumbs(array_filter([
    ['Summary Stok Fisik', base_url('stock/summary' . $pq)],
    $ret === 'barang'  ? ['Stock Hadiah Fisik', base_url('stock/barang' . $pq)] : null,
    $ret === 'voucher' ? ['Stock Voucher Fisik', base_url('stock/voucher' . $pq)] : null,
    ['Kartu Stok', null],
])) ?>
</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary" title="<?= $backLbl ?>"><i class="bi bi-arrow-left me-1"></i><span class="d-none d-sm-inline"><?= $backLbl ?></span></a>
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-card-list me-2"></i>Kartu Stok — <?= esc($barang['nama_barang']) ?></h4>
        <small class="text-muted">Satuan <?= esc($barang['satuan']) ?> · Stok tersedia saat ini: <strong><?= sf_n((int)$barang['stok_tersedia']) ?></strong></small>
    </div>
</div>

<div class="d-none d-print-block small mb-2">Periode: <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?></div>
<div class="card mb-3 no-print"><div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <input type="hidden" name="ret" value="<?= esc($ret) ?>">
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
    <div class="col-auto ms-auto"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button></div>
</form>
</div></div>

<div class="card">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0 align-middle kb-tbl">
    <thead class="table-light"><tr>
        <th class="ps-3" style="width:110px">Tanggal</th>
        <th style="width:90px">Jenis</th>
        <th class="text-end" style="width:90px">Jumlah</th>
        <th class="text-end" style="width:100px">Saldo</th>
        <th>Referensi</th>
        <th>Penerima &amp; Bukti</th>
        <th>Catatan</th>
        <th class="pe-3" style="width:130px">Oleh</th>
    </tr></thead>
    <tbody>
    <?php if (empty($entries)): ?>
    <tr><td colspan="8" class="text-center text-muted py-4 kb-empty">Belum ada mutasi pada periode ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($entries as $e):
        $masuk = $e['tipe'] === 'masuk';
    ?>
    <tr>
        <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
        <td><span class="badge bg-<?= $masuk ? 'success' : 'danger' ?>-subtle text-<?= $masuk ? 'success' : 'danger' ?>"><?= $masuk ? 'Masuk' : 'Keluar' ?></span></td>
        <td class="text-end fw-semibold <?= $masuk ? 'text-success' : 'text-danger' ?>"><?= ($masuk ? '+' : '-') . sf_n((int)$e['jumlah']) ?></td>
        <td class="text-end"><?= sf_n((int)$e['stok_sesudah']) ?></td>
        <td class="small text-muted"><?= esc($e['referensi_tipe'] ?? '—') ?><?= $e['referensi_id'] ? ' #'.$e['referensi_id'] : '' ?></td>
        <td class="small kb-rec">
            <?php $rl = $e['realisasi'] ?? null; if ($rl): $n = count($rl['rows']); ?>
                <?php if ($rl['mode'] === 'ambigu'): ?>
                <div class="text-warning-emphasis small mb-1" title="Pada hari &amp; program yang sama jumlah log dan realisasi tidak cocok satu-satu, jadi tidak dipasangkan.">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $n ?> penerima hari itu</div>
                <?php endif; ?>
                <?php foreach ($rl['rows'] as $r): ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?php if ($r['foto_ada']): $furl = base_url('uploads/loyalty-realisasi/' . $r['foto']); ?>
                    <a href="<?= $furl ?>" class="kb-thumb" data-kb-img="<?= esc($furl) ?>" data-kb-cap="<?= esc(($r['nama_penerima'] ?: 'Tanpa nama') . ' · ' . date('d M Y', strtotime($r['tanggal']))) ?>"><img src="<?= $furl ?>" alt="Bukti" loading="lazy"></a>
                    <?php else: ?>
                    <span class="kb-thumb kb-miss" title="<?= $r['foto'] ? 'Berkas foto tidak ditemukan' : 'Tidak ada foto' ?>"><i class="bi bi-image"></i></span>
                    <?php endif; ?>
                    <span class="fw-medium"><?= esc(trim($r['nama_penerima'] ?? '') ?: '—') ?><?= $r['jumlah_dibagikan'] > 1 ? ' <span class="text-muted">×' . (int) $r['jumlah_dibagikan'] . '</span>' : '' ?></span>
                </div>
                <?php endforeach; ?>
            <?php elseif ($e['tipe'] === 'keluar' && in_array($e['referensi_tipe'], ['loyalty_program', 'event_loyalty'], true)): ?>
                <span class="text-muted">Realisasi tidak ditemukan</span>
            <?php endif; ?>
        </td>
        <td class="small"><?= esc($e['catatan'] ?? '') ?></td>
        <td class="pe-3 small text-muted"><i class="bi bi-person me-1"></i><?= esc($e['pengisi'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<style>
.kb-thumb { width: 38px; height: 38px; flex: none; border-radius: 8px; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(148,163,184,.35); background: rgba(148,163,184,.12); }
.kb-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.kb-miss { opacity: .55; }
a.kb-thumb { cursor: zoom-in; }
/* Ponsel (<576px): tiap mutasi jadi kartu ringkas; 'Penerima & Bukti' pindah ke baris kedua di bawah baris mutasi, tanpa geser horizontal. */
@media (max-width: 575.98px) {
    .kb-tbl, .kb-tbl tbody { display: block; width: 100%; }
    .kb-tbl thead { display: none; }
    .kb-tbl tr { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .75rem; padding: .6rem .75rem; border-bottom: 1px solid var(--bs-border-color); }
    .kb-tbl td { display: block; width: auto; padding: 0 !important; border: 0 !important; text-align: left !important; background: transparent !important; box-shadow: none !important; }
    .kb-tbl td.kb-empty { width: 100%; text-align: center !important; padding: 1.5rem 0 !important; }
    .kb-tbl td:nth-child(1) { order: 1; font-weight: 600; }
    .kb-tbl td:nth-child(2) { order: 2; }
    .kb-tbl td:nth-child(3) { order: 3; }
    .kb-tbl td:nth-child(4) { order: 4; margin-left: auto; text-align: right !important; }
    .kb-tbl td:nth-child(4)::before { content: 'Saldo '; color: var(--bs-secondary-color); font-size: .75rem; }
    .kb-tbl td:nth-child(5) { order: 5; }
    .kb-tbl td.kb-rec { order: 6; flex: 0 0 100%; min-width: 0; padding-top: .35rem !important; }
    .kb-tbl td.kb-rec:empty, .kb-tbl td.kb-rec:not(:has(*)) { display: none; }
    .kb-tbl td:nth-child(7) { order: 7; flex: 0 0 100%; }
    .kb-tbl td:nth-child(7):empty { display: none; }
    .kb-tbl td:nth-child(8) { order: 8; flex: 0 0 100%; }
}
@media print { .btn, form, .modal { display: none !important; } .kb-thumb { width: 26px; height: 26px; } }
</style>

<div class="modal fade" id="kbLightbox" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title" id="kbCap"></h6><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body text-center p-2"><img id="kbImg" src="" alt="Bukti realisasi" style="max-width:100%;max-height:75vh"></div>
</div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/* Cetak selalu di kertas terang: tema gelap membuat teks nyaris putih di atas kertas putih. */
(function () {
    var h = document.documentElement, t = null;
    window.addEventListener('beforeprint', function () { t = [h.getAttribute('data-theme'), h.getAttribute('data-bs-theme')]; h.setAttribute('data-theme', 'light'); h.setAttribute('data-bs-theme', 'light'); });
    window.addEventListener('afterprint', function () { if (t) { h.setAttribute('data-theme', t[0]); h.setAttribute('data-bs-theme', t[1]); t = null; } });
})();
</script>
<script>
document.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-kb-img]');
    if (!a) return;
    e.preventDefault();
    document.getElementById('kbImg').src = a.getAttribute('data-kb-img');
    document.getElementById('kbCap').textContent = a.getAttribute('data-kb-cap') || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('kbLightbox')).show();
});
</script>
<?= $this->endSection() ?>
