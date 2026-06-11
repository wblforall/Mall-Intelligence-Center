<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('stock/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-card-list me-2"></i>Kartu Stok — <?= esc($barang['nama_barang']) ?></h4>
        <small class="text-muted">Satuan <?= esc($barang['satuan']) ?> · Stok tersedia saat ini: <strong><?= number_format((int)$barang['stok_tersedia']) ?></strong></small>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
</form>
</div></div>

<div class="card">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0 align-middle">
    <thead class="table-light"><tr>
        <th class="ps-3" style="width:110px">Tanggal</th>
        <th style="width:90px">Jenis</th>
        <th class="text-end" style="width:90px">Jumlah</th>
        <th class="text-end" style="width:100px">Saldo</th>
        <th>Referensi</th>
        <th class="pe-3">Catatan</th>
    </tr></thead>
    <tbody>
    <?php if (empty($entries)): ?>
    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada mutasi pada periode ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($entries as $e):
        $masuk = $e['tipe'] === 'masuk';
    ?>
    <tr>
        <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
        <td><span class="badge bg-<?= $masuk ? 'success' : 'danger' ?>-subtle text-<?= $masuk ? 'success' : 'danger' ?>"><?= $masuk ? 'Masuk' : 'Keluar' ?></span></td>
        <td class="text-end fw-semibold <?= $masuk ? 'text-success' : 'text-danger' ?>"><?= ($masuk ? '+' : '-') . number_format((int)$e['jumlah']) ?></td>
        <td class="text-end"><?= number_format((int)$e['stok_sesudah']) ?></td>
        <td class="small text-muted"><?= esc($e['referensi_tipe'] ?? '—') ?><?= $e['referensi_id'] ? ' #'.$e['referensi_id'] : '' ?></td>
        <td class="pe-3 small"><?= esc($e['catatan'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?= $this->endSection() ?>
