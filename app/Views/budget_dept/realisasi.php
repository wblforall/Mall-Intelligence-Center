<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.bd-tx { font-size:.84rem; }
.bd-tx td { vertical-align:top; }
.bd-tx .nilai { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; font-weight:600; }
.bd-tx thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--bs-secondary-color); font-weight:600; white-space:nowrap; }
.bd-tx tfoot td { font-weight:700; border-top:2px solid var(--bs-border-color); }
.bd-tx .c-uraian { min-width:240px; }
.bd-tx .c-pos { min-width:140px; }
.bd-tx .ket { white-space:pre-line; font-size:.78rem; color:var(--bs-secondary-color); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
use App\Libraries\BudgetRekap;
$BLN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$posAktif = array_filter($pos, fn ($p) => ! empty($p['aktif']));
$total = array_sum(array_map(fn ($r) => (float) $r['nilai'], $rows));
$kembali = (string) current_url(true);
$tglBawaan = (int) date('Y') === (int) $tahun ? date('Y-m-d') : ($bulan ? sprintf('%d-%02d-01', $tahun, $bulan) : "$tahun-01-01");
$fmtAsal = function (?string $ym) use ($BLN) {
    if (! $ym) return '';
    [$y, $m] = explode('-', $ym);
    return substr($BLN[(int) $m], 0, 3) . ' ' . $y;
};
?>

<form method="GET" class="row g-2 align-items-end mb-3">
    <?php if ($depts): ?><input type="hidden" name="dept" value="<?= $dept['id'] ?>"><?php endif; ?>
    <input type="hidden" name="tahun" value="<?= $tahun ?>">
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="f-bulan">Bulan</label>
        <select id="f-bulan" name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">Setahun</option>
            <?php foreach ($BLN as $m => $n): ?>
            <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="f-pos">Pos</label>
        <select id="f-pos" name="pos" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua pos</option>
            <?php foreach ($pos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === (int) $posId ? 'selected' : '' ?>><?= esc($p['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small mb-1" for="f-q">Cari</label>
        <input id="f-q" type="search" name="q" value="<?= esc($cari) ?>" class="form-control form-control-sm" placeholder="Uraian, keterangan, no. memo/PR">
    </div>
    <div class="col-12 col-md-3 text-md-end">
        <?php if ($canEdit && $posAktif): ?>
        <button type="button" class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#mTx" data-tx="">
            <i class="bi bi-plus-lg me-1"></i>Catat Realisasi
        </button>
        <?php endif; ?>
    </div>
</form>

<?php if (! $pos): ?>
<div class="alert alert-info small">Belum ada pos budget. <a href="<?= $url('pos') ?>">Buat pos dulu</a> sebelum mencatat realisasi.</div>
<?php endif; ?>

<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0 bd-tx">
    <thead><tr>
        <th>Tanggal</th><th>Pos</th><th>Uraian</th>
        <?php if ($pisahMal): ?><th>Mal</th><?php endif; ?>
        <th>No. Memo/PR</th><th class="text-end">Nilai (Rp)</th>
        <?php if ($canEdit): ?><th></th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php if (! $rows): ?>
    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi pada filter ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td class="text-nowrap">
            <?= date('d/m/Y', strtotime($r['tanggal'])) ?>
            <?php if ($r['periode_asal']): ?>
            <div><span class="badge text-bg-warning-subtle text-warning-emphasis" title="Dibebankan bulan ini, kejadian/tagihan bulan lain">tagihan <?= $fmtAsal($r['periode_asal']) ?></span></div>
            <?php endif; ?>
        </td>
        <td class="c-pos"><?= esc($r['pos_nama']) ?></td>
        <td class="c-uraian">
            <?= esc($r['uraian']) ?>
            <?php if ($r['keterangan']): ?><div class="ket"><?= esc($r['keterangan']) ?></div><?php endif; ?>
        </td>
        <?php if ($pisahMal): ?>
        <td class="text-nowrap small"><?= $r['mall'] === 'both' ? 'eWalk + Pentacity' : esc(BudgetRekap::MAL[$r['mall']] ?? '–') ?></td>
        <?php endif; ?>
        <td class="small"><?= esc($r['referensi'] ?? '') ?></td>
        <td class="nilai"><?= number_format((float) $r['nilai'], 0, ',', '.') ?></td>
        <?php if ($canEdit): ?>
        <td class="text-nowrap text-end">
            <button type="button" class="btn btn-sm btn-link p-0 me-2" title="Ubah" aria-label="Ubah"
                    data-bs-toggle="modal" data-bs-target="#mTx"
                    data-tx="<?= esc(json_encode(array_intersect_key($r, array_flip(['id','pos_id','tanggal','periode_asal','uraian','keterangan','nilai','mall','referensi'])))) ?>">
                <i class="bi bi-pencil"></i></button>
            <form method="POST" action="<?= $url('realisasi/' . $r['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?')">
                <?= csrf_field() ?><input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                <button class="btn btn-sm btn-link p-0 text-danger" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($rows): ?>
    <tfoot><tr>
        <td colspan="<?= $pisahMal ? 5 : 4 ?>"><?= count($rows) ?> transaksi</td>
        <td class="nilai"><?= number_format($total, 0, ',', '.') ?></td>
        <?php if ($canEdit): ?><td></td><?php endif; ?>
    </tr></tfoot>
    <?php endif; ?>
</table>
</div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="mTx" tabindex="-1" aria-labelledby="mTxJudul" aria-hidden="true">
<div class="modal-dialog modal-lg">
<form method="POST" action="<?= $url('realisasi/simpan') ?>" class="modal-content">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= esc(old('id') ?? '') ?>">
    <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
    <div class="modal-header">
        <h6 class="modal-title" id="mTxJudul">Catat Realisasi</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body row g-3">
        <div class="col-md-6">
            <label class="form-label small" for="tx-pos">Pos budget</label>
            <select id="tx-pos" name="pos_id" class="form-select form-select-sm" required>
                <option value="">— pilih —</option>
                <?php foreach ($pos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= empty($p['aktif']) ? 'data-nonaktif' : '' ?>><?= esc($p['nama']) ?><?= empty($p['aktif']) ? ' (nonaktif)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="tx-tgl">Tanggal pembebanan</label>
            <input id="tx-tgl" type="date" name="tanggal" class="form-control form-control-sm" required min="<?= \App\Controllers\BudgetDept::TAHUN_MULAI ?>-01-01" value="<?= esc(old('tanggal') ?? $tglBawaan) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="tx-asal">Periode asal <span class="text-muted">(opsional)</span></label>
            <input id="tx-asal" type="month" name="periode_asal" class="form-control form-control-sm" value="<?= esc(old('periode_asal') ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small" for="tx-uraian">Uraian</label>
            <input id="tx-uraian" type="text" name="uraian" maxlength="255" class="form-control form-control-sm" required
                   placeholder="mis. E-voucher PAM Plus opening Auntie Anne 11–24 Mar" value="<?= esc(old('uraian') ?? '') ?>">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label small" for="tx-nilai">Nilai (Rp)</label>
            <input id="tx-nilai" type="text" inputmode="numeric" name="nilai" class="form-control form-control-sm text-end" required value="<?= esc(old('nilai') ?? '') ?>">
        </div>
        <?php if ($pisahMal): ?>
        <div class="col-6 col-md-4">
            <label class="form-label small" for="tx-mal">Mal</label>
            <select id="tx-mal" name="mall" class="form-select form-select-sm" required>
                <?php foreach (BudgetRekap::MAL_REALISASI as $k => $l): ?>
                <option value="<?= $k ?>" <?= $k === 'both' ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-4">
            <label class="form-label small" for="tx-ref">No. Memo / PR</label>
            <input id="tx-ref" type="text" name="referensi" maxlength="100" class="form-control form-control-sm" value="<?= esc(old('referensi') ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small" for="tx-ket">Rincian <span class="text-muted">(opsional)</span></label>
            <textarea id="tx-ket" name="keterangan" rows="3" class="form-control form-control-sm"
                      placeholder="mis. Auntie Anne 310.000; Blibli 50.000"><?= esc(old('keterangan') ?? '') ?></textarea>
        </div>
        <div class="col-12 small text-muted">
            Tanggal pembebanan menentukan bulan realisasi. Isi periode asal bila tagihan untuk bulan lain (mis. reimburse poin Desember dibayar Januari).
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-floppy me-1"></i>Simpan</button>
    </div>
</form>
</div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($canEdit): ?>
<script>
(function () {
    const m = document.getElementById('mTx');
    const f = m.querySelector('form');
    const fmt = v => { const n = parseFloat(String(v).replace(/[^\d,]/g, '').replace(',', '.')); return n ? n.toLocaleString('id-ID', {maximumFractionDigits: 2}) : ''; };
    const bawaan = { tanggal: '<?= $tglBawaan ?>', mall: 'both' };

    m.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        if (!btn) return;                               // dibuka ulang otomatis setelah gagal simpan
        const d = btn.dataset.tx ? JSON.parse(btn.dataset.tx) : null;
        f.reset();
        f.elements['id'].value = d ? d.id : '';
        f.pos_id.value = d ? d.pos_id : (<?= (int) $posId ?> || '');
        f.tanggal.value = d ? d.tanggal : bawaan.tanggal;
        f.periode_asal.value = d && d.periode_asal ? d.periode_asal : '';
        f.uraian.value = d ? d.uraian : '';
        // d.nilai dari DB berformat "22125000.00" (titik = desimal) — jangan
        // dilewatkan ke fmt() yang menganggap titik sebagai pemisah ribuan.
        f.nilai.value = d ? Number(d.nilai).toLocaleString('id-ID', {maximumFractionDigits: 2}) : '';
        if (f.mall) f.mall.value = d ? d.mall : bawaan.mall;
        f.referensi.value = d && d.referensi ? d.referensi : '';
        f.keterangan.value = d && d.keterangan ? d.keterangan : '';
        document.getElementById('mTxJudul').textContent = d ? 'Ubah Realisasi' : 'Catat Realisasi';
        // Pos nonaktif hanya boleh dipilih untuk transaksi lama yang memang di pos itu.
        f.pos_id.querySelectorAll('option[data-nonaktif]').forEach(o => o.hidden = !(d && String(d.pos_id) === o.value));
    });
    m.addEventListener('shown.bs.modal', () => f.uraian.focus());
    f.nilai.addEventListener('blur', () => f.nilai.value = fmt(f.nilai.value));

    <?php if (old('uraian') !== null): ?>
    // Gagal simpan: buka lagi dengan isian terakhir.
    f.pos_id.value = '<?= esc(old('pos_id') ?? '', 'js') ?>';
    if (f.mall) f.mall.value = '<?= esc(old('mall') ?? 'both', 'js') ?>';
    bootstrap.Modal.getOrCreateInstance(m).show();
    <?php endif; ?>
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
