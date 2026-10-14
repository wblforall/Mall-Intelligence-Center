<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.rab-tabel { font-size: .82rem; }
.rab-tabel td, .rab-tabel th { vertical-align: top; }
.rab-tabel .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.rab-tabel tr.ev td { background: var(--bs-tertiary-bg); font-weight: 600; }
.rab-tabel tr.kel td { font-style: italic; color: var(--bs-secondary-color); padding-top: .15rem; padding-bottom: .15rem; }
.rab-tabel tr.sub td { font-weight: 700; border-top: 2px solid var(--bs-border-color); }
.rab-tabel tr.hapus td { text-decoration: line-through; color: var(--bs-secondary-color); }
.bd-lewat, .table td.bd-lewat { color: var(--bs-danger) !important; font-weight: 600; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
use App\Libraries\RabSusun;

$rp  = fn ($v) => number_format((float) $v, 0, ',', '.');
$num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$STATUS = ['draf' => ['secondary', 'Draf'], 'diajukan' => ['warning', 'Diajukan — menunggu TTD'], 'disetujui' => ['success', 'Disetujui'],
           'digantikan' => ['light', 'Digantikan revisi'], 'batal' => ['light', 'Batal']];
$posNama = array_column($snap['pos'], 'nama', 'id');
$bulanP  = RabSusun::daftarBulan($snap['periode']['mulai'], $snap['periode']['selesai']);

// Kelompokkan item: bulan → mal → event.
$grup = [];
foreach ($snap['items'] as $it) $grup[$it['bulan']][$it['mall']][$it['event_id']][] = $it;
$draf = $d['status'] === 'draf';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="<?= $url('rab') ?>" class="btn btn-sm btn-outline-secondary" aria-label="Kembali ke daftar RAB"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="fw-bold">RAB <?= RabSusun::labelPeriode($d['periode_mulai'], $d['periode_selesai']) ?></div>
        <div class="small text-muted"><?= esc($d['nomor'] ?: 'Versi ' . $d['versi'] . ' — draf') ?></div>
    </div>
    <span class="badge text-bg-<?= $STATUS[$d['status']][0] ?>"><?= $STATUS[$d['status']][1] ?></span>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <a href="<?= $url('rab/' . $d['id'] . '/cetak') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i><?= $draf ? 'Pratinjau PDF' : 'Cetak PDF untuk e-Sign' ?></a>
        <?php if ($draf && $canEdit): ?>
        <form method="POST" action="<?= $url('rab/' . $d['id'] . '/ajukan') ?>" onsubmit="return confirm('Ajukan RAB ini? Isinya dibekukan; angka item yang tercantum terkunci sampai ada revisi.')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-primary" <?= $periksa['halang'] ? 'disabled title="Selesaikan dulu hal yang menghalangi"' : '' ?>><i class="bi bi-send me-1"></i>Ajukan</button>
        </form>
        <form method="POST" action="<?= $url('rab/' . $d['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus draf ini?')">
            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus draf</button>
        </form>
        <?php endif; ?>
        <?php if ($d['status'] === 'diajukan' && $bolehSetujui): ?>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#mUnggah"
                data-aksi="<?= $url('rab/' . $d['id'] . '/unggah') ?>" data-nomor="<?= esc($d['nomor'], 'attr') ?>"><i class="bi bi-upload me-1"></i>Unggah TTD</button>
        <?php endif; ?>
        <?php if ($d['file_ttd']): ?>
        <a href="<?= $url('rab/' . $d['id'] . '/berkas') ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check me-1"></i>Berkas TTD</a>
        <?php endif; ?>
        <?php if ($terbaru && in_array($d['status'], ['diajukan', 'disetujui'], true) && $canEdit): ?>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mRevisi"><i class="bi bi-pencil-square me-1"></i>Revisi</button>
        <?php endif; ?>
    </div>
</div>

<?php if (count($rantai) > 1): ?>
<div class="small mb-3">Versi:
    <?php foreach (array_reverse($rantai) as $v): ?>
    <a href="<?= $url('rab/' . $v['id']) ?>" class="badge text-decoration-none <?= (int) $v['id'] === (int) $d['id'] ? 'text-bg-primary' : 'text-bg-light' ?>">
        v<?= $v['versi'] ?> · <?= $STATUS[$v['status']][1] ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($d['catatan']): ?>
<div class="alert alert-light border small py-2"><i class="bi bi-chat-left-text me-1"></i><strong>Alasan revisi:</strong> <?= esc($d['catatan']) ?></div>
<?php endif; ?>
<?php if ($draf): ?>
<div class="alert alert-info small py-2"><i class="bi bi-arrow-repeat me-1"></i>Draf ini <strong>pratinjau hidup</strong> dari Event Content — ubah item di halaman event-nya, lalu muat ulang halaman ini.</div>
<?php if ($periksa['halang']): ?>
<div class="alert alert-danger small">
    <strong><i class="bi bi-x-octagon me-1"></i>Belum bisa diajukan:</strong>
    <ul class="mb-0 mt-1"><?php foreach ($periksa['halang'] as $h): ?><li><?= esc($h) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<?php endif; ?>
<?php if ($periksa['info']): ?>
<div class="alert alert-warning small">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Perlu diperhatikan:</strong>
    <ul class="mb-0 mt-1"><?php foreach ($periksa['info'] as $h): ?><li><?= esc($h) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- Ringkasan budget vs RAB -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Budget vs RAB per Pos</h6></div>
    <div class="table-responsive">
    <table class="table table-sm mb-0 rab-tabel">
        <thead><tr><th>Bulan</th><th>Mal</th><th>Pos</th><th class="num">Budget</th><th class="num">RAB</th><th class="num">Sisa</th></tr></thead>
        <tbody>
        <?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity'] as $m): $first = true;
            foreach ($snap['pos'] as $p): $v = $ringkas['sel'][$b][$m][$p['id']] ?? ['budget' => 0, 'rab' => 0];
                if ($v['budget'] == 0 && $v['rab'] == 0) continue; ?>
        <tr>
            <td><?= $first ? RabSusun::labelBulan($b) : '' ?></td>
            <td><?= $first ? RabSusun::MAL[$m] : '' ?></td>
            <td><?= esc($p['nama']) ?></td>
            <td class="num"><?= $rp($v['budget']) ?></td>
            <td class="num"><?= $rp($v['rab']) ?></td>
            <td class="num <?= $v['rab'] > $v['budget'] ? 'bd-lewat' : '' ?>"><?= $rp($v['budget'] - $v['rab']) ?></td>
        </tr>
        <?php $first = false; endforeach; endforeach; endforeach; ?>
        </tbody>
        <tfoot><tr class="sub">
            <td colspan="3">Total periode</td>
            <td class="num"><?= $rp($ringkas['total']['budget']) ?></td>
            <td class="num"><?= $rp($ringkas['total']['rab']) ?></td>
            <td class="num <?= $ringkas['total']['rab'] > $ringkas['total']['budget'] ? 'bd-lewat' : '' ?>"><?= $rp($ringkas['total']['budget'] - $ringkas['total']['rab']) ?></td>
        </tr></tfoot>
    </table>
    </div>
    <?php if ($ringkas['tanpaPos'] > 0): ?>
    <div class="card-footer small text-danger">Rp <?= $rp($ringkas['tanpaPos']) ?> biaya belum punya pos — belum terhitung di tabel ini.</div>
    <?php endif; ?>
</div>

<!-- Rincian -->
<?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity', 'both'] as $m): if (empty($grup[$b][$m])) continue; $sub = 0; ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between"><h6 class="mb-0 fw-semibold"><?= RabSusun::MAL[$m] ?> — <?= RabSusun::labelBulan($b) ?></h6></div>
    <div class="table-responsive">
    <table class="table table-sm mb-0 rab-tabel">
        <thead><tr><th>Rincian</th><th class="num">Qty</th><th>Satuan</th><th class="num">Freq</th><th>Per</th><th class="num">Harga satuan</th><th class="num">Total</th><th>Pos</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php foreach ($grup[$b][$m] as $evId => $rows): $ev = $snap['events'][$evId] ?? null; $kel = null; ?>
        <tr class="ev"><td colspan="9">
            <?php if ($draf): ?><a href="<?= base_url('events/' . $evId . '/content') ?>"><?= esc($ev['nama'] ?? 'Event #' . $evId) ?></a><?php else: ?><?= esc($ev['nama'] ?? 'Event #' . $evId) ?><?php endif; ?>
            <?php if (($ev['jenis'] ?? '') === 'rutin'): ?><span class="badge text-bg-secondary ms-1">Rutin</span><?php endif; ?>
            <span class="fw-normal text-muted ms-2"><?= $ev ? date('j M', strtotime($ev['mulai'])) . ' – ' . date('j M Y', strtotime($ev['akhir'])) : '' ?><?= ! empty($ev['lokasi']) ? ' · ' . esc($ev['lokasi']) : '' ?></span>
        </td></tr>
        <?php foreach ($rows as $it): $sub += $it['total'];
            if ($it['kelompok'] !== '' && $it['kelompok'] !== $kel): $kel = $it['kelompok']; ?>
        <tr class="kel"><td colspan="9"><?= esc($kel) ?></td></tr>
        <?php endif; $tanda = $beda['tanda'][$it['id']] ?? null; ?>
        <tr>
            <td><?= esc($it['nama']) ?>
                <?php if ($tanda === 'baru'): ?><span class="badge text-bg-success ms-1">Baru</span><?php elseif ($tanda === 'berubah'): ?><span class="badge text-bg-warning ms-1">Berubah</span><?php endif; ?>
            </td>
            <td class="num"><?= $num($it['qty']) ?></td><td><?= esc($it['satuan']) ?></td>
            <td class="num"><?= $num($it['freq']) ?></td><td><?= esc($it['satuan_freq']) ?></td>
            <td class="num"><?= $it['harga_satuan'] !== null ? $rp($it['harga_satuan']) : '' ?></td>
            <td class="num fw-semibold"><?= $rp($it['total']) ?></td>
            <td class="text-nowrap"><?= $it['pos_id'] ? esc($posNama[$it['pos_id']] ?? '#' . $it['pos_id']) : '<span class="text-danger">tanpa pos</span>' ?></td>
            <td class="small"><?= esc($it['keterangan']) ?><?= $it['tanggal'] ? '<div class="text-muted">' . date('d/m/Y', strtotime($it['tanggal'])) . '</div>' : '' ?></td>
        </tr>
        <?php endforeach; endforeach; ?>
        <tr class="sub"><td colspan="6">Subtotal <?= RabSusun::MAL[$m] ?> <?= RabSusun::labelBulan($b) ?></td><td class="num"><?= $rp($sub) ?></td><td colspan="2"></td></tr>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; endforeach; ?>

<?php if ($beda['dihapus']): ?>
<div class="card mb-3 border-danger-subtle">
    <div class="card-header"><h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-dash-circle me-2"></i>Dihapus dibanding versi sebelumnya</h6></div>
    <table class="table table-sm mb-0 rab-tabel">
        <?php foreach ($beda['dihapus'] as $it): ?>
        <tr class="hapus"><td><?= esc(($snap['events'][$it['event_id']]['nama'] ?? 'Event') . ' — ' . $it['nama']) ?></td><td><?= RabSusun::labelBulan($it['bulan']) ?></td><td class="num"><?= $rp($it['total']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<?php if (! $snap['items']): ?>
<div class="card"><div class="card-body text-center text-muted py-4">Belum ada item biaya di periode ini. Tambahkan program/biaya di <a href="<?= base_url('events') ?>">modul Events</a> → Content.</div></div>
<?php endif; ?>

<?php if ($terbaru && $canEdit): ?>
<div class="modal fade" id="mRevisi" tabindex="-1" aria-labelledby="mRevisiJudul" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= $url('rab/' . $d['id'] . '/revisi') ?>">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title fw-semibold" id="mRevisiJudul">Buat revisi v<?= (int) $d['versi'] + 1 ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body">
        <p class="small text-muted">Item event jadi bisa diubah lagi. <?= $d['status'] === 'disetujui' ? 'Versi ini tetap berlaku sampai revisi disetujui.' : 'Versi ini (belum disetujui) dibatalkan.' ?></p>
        <label class="form-label small fw-semibold" for="rAlasan">Alasan revisi</label>
        <textarea name="catatan" id="rAlasan" class="form-control" rows="3" required placeholder="Mis. Bazar Multi Produk jadi diadakan, tambah biaya MC & konsumsi"></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Buat revisi</button></div>
</form>
</div></div></div>
<?php endif; ?>
<?= view('budget_dept/_rab_unggah') ?>
<?= $this->endSection() ?>
