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

<?php
// Filter bulan = tombol pil (sekali klik), membawa saringan pos & kata cari yang sedang aktif.
$_qBulan = fn (int $m) => array_filter(['tahun' => $tahun, 'bulan' => $m ?: null, 'pos' => $posId ?: null, 'q' => $cari !== '' ? $cari : null]);
?>
<div class="d-flex flex-wrap gap-1 mb-2" role="tablist" aria-label="Bulan">
    <a href="<?= $url('realisasi', $_qBulan(0)) ?>" class="btn btn-sm btn-outline-secondary rb-bln <?= $bulan === 0 ? 'active' : '' ?>" role="tab" aria-selected="<?= $bulan === 0 ? 'true' : 'false' ?>">Setahun</a>
    <?php foreach ($BLN as $m => $n): ?>
    <a href="<?= $url('realisasi', $_qBulan($m)) ?>" class="btn btn-sm btn-outline-secondary rb-bln <?= $m === $bulan ? 'active' : '' ?>" role="tab" aria-selected="<?= $m === $bulan ? 'true' : 'false' ?>"><?= substr($n, 0, 3) ?></a>
    <?php endforeach; ?>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <?php if ($depts): ?><input type="hidden" name="dept" value="<?= $dept['id'] ?>"><?php endif; ?>
    <input type="hidden" name="tahun" value="<?= $tahun ?>">
    <?php if ($bulan): ?><input type="hidden" name="bulan" value="<?= $bulan ?>"><?php endif; ?>
    <div class="col-12 col-md-4">
        <label class="form-label small mb-1" for="f-pos">Pos</label>
        <select id="f-pos" name="pos" class="form-select form-select-sm bd-ts" onchange="this.form.submit()">
            <option value="">Semua pos</option>
            <?php foreach ($pos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === (int) $posId ? 'selected' : '' ?>><?= esc($p['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-5">
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
            <?php if (($r['sumber'] ?? '') === 'event'): ?>
            <a href="<?= base_url('events/' . $r['event_id'] . '/content#item-' . $r['item_id']) ?>" class="badge text-bg-info-subtle text-info-emphasis text-decoration-none me-1"
               title="Dicatat di modul Events (Event Content) — ubah/hapus dari sana"><i class="bi bi-calendar-event me-1"></i>Event</a>
            <?php endif; ?>
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
            <?php if (($r['sumber'] ?? '') === 'event'): ?>
            <a href="<?= base_url('events/' . $r['event_id'] . '/content#item-' . $r['item_id']) ?>" class="btn btn-sm btn-link p-0" title="Buka di Event Content"><i class="bi bi-box-arrow-up-right"></i></a>
            <?php else: ?>
            <button type="button" class="btn btn-sm btn-link p-0 me-2" title="Ubah" aria-label="Ubah"
                    data-bs-toggle="modal" data-bs-target="#mTx"
                    data-tx="<?= esc(json_encode(array_intersect_key($r, array_flip(['id','pos_id','tanggal','periode_asal','uraian','keterangan','nilai','mall','referensi'])))) ?>">
                <i class="bi bi-pencil"></i></button>
            <form method="POST" action="<?= $url('realisasi/' . $r['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?')">
                <?= csrf_field() ?><input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                <button class="btn btn-sm btn-link p-0 text-danger" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
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
            <select id="tx-pos" name="pos_id" class="form-select form-select-sm bd-ts" required>
                <option value="">— pilih —</option>
                <?php foreach ($pos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= empty($p['aktif']) ? 'data-nonaktif' : '' ?>><?= esc($p['nama']) ?><?= empty($p['aktif']) ? ' (nonaktif)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="tx-tgl">Tanggal pembebanan</label>
            <input id="tx-tgl" type="date" name="tanggal" class="form-control form-control-sm" required min="<?= (int) $tahunMulai ?>-01-01" value="<?= esc(old('tanggal') ?? $tglBawaan) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="tx-asal">Periode asal <span class="text-muted">(opsional)</span></label>
            <div class="bd-asal">
                <input id="tx-asal" type="text" name="periode_asal" class="form-control form-control-sm" placeholder="Pilih bulan" value="<?= esc(old('periode_asal') ?? '') ?>">
                <button type="button" class="bd-asal-hapus" id="tx-asal-hapus" title="Kosongkan periode asal" aria-label="Kosongkan periode asal"><i class="bi bi-x-lg"></i></button>
            </div>
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
            <select id="tx-mal" name="mall" class="form-select form-select-sm bd-ts" required>
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
<link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>
<script>
(function () {
    const m = document.getElementById('mTx');
    const f = m.querySelector('form');
    const fmt = v => { const n = parseFloat(String(v).replace(/[^\d,]/g, '').replace(',', '.')); return n ? n.toLocaleString('id-ID', {maximumFractionDigits: 2}) : ''; };
    const bawaan = { tanggal: '<?= $tglBawaan ?>', mall: 'both' };

    // Periode asal: pemilih bulan berbentuk grid 12 bulan (nilai tersimpan "YYYY-MM").
    const asal = window.flatpickr ? flatpickr(f.periode_asal, {
        locale: 'id', static: true, disableMobile: true,
        plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m', altFormat: 'F Y' })],
        altInput: true, altInputClass: 'form-control form-control-sm', allowInput: false,
        onChange: (_, teks) => document.getElementById('tx-asal-hapus').hidden = !teks,
        onReady:  (_, teks) => document.getElementById('tx-asal-hapus').hidden = !teks,
    }) : null;
    const setAsal = v => asal ? (v ? asal.setDate(v, false, 'Y-m') : asal.clear()) : (f.periode_asal.value = v || '');
    document.getElementById('tx-asal-hapus').addEventListener('click', () => setAsal(''));

    // Pilihan pos: pos nonaktif hanya muncul untuk transaksi lama yang memang di pos itu.
    function aturPos(d, nilai) {
        const sel = f.pos_id, ts = sel.tomselect;
        sel.querySelectorAll('option[data-nonaktif]').forEach(o => o.hidden = !(d && String(d.pos_id) === o.value));
        if (ts) {
            ts.clearOptions();
            [...sel.options].filter(o => o.value && !o.hidden).forEach(o => ts.addOption({ value: o.value, text: o.text }));
            ts.refreshOptions(false);
        }
        bdSetNilai(sel, nilai);
    }

    m.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        if (!btn) return;                               // dibuka ulang otomatis setelah gagal simpan
        const d = btn.dataset.tx ? JSON.parse(btn.dataset.tx) : null;
        f.reset();
        f.elements['id'].value = d ? d.id : '';
        aturPos(d, d ? d.pos_id : (<?= (int) $posId ?> || ''));
        f.tanggal.value = d ? d.tanggal : bawaan.tanggal;
        setAsal(d && d.periode_asal ? d.periode_asal : '');
        f.uraian.value = d ? d.uraian : '';
        // d.nilai dari DB berformat "22125000.00" (titik = desimal) — jangan
        // dilewatkan ke fmt() yang menganggap titik sebagai pemisah ribuan.
        f.nilai.value = d ? Number(d.nilai).toLocaleString('id-ID', {maximumFractionDigits: 2}) : '';
        if (f.mall) bdSetNilai(f.mall, d ? d.mall : bawaan.mall);
        f.referensi.value = d && d.referensi ? d.referensi : '';
        f.keterangan.value = d && d.keterangan ? d.keterangan : '';
        document.getElementById('mTxJudul').textContent = d ? 'Ubah Realisasi' : 'Catat Realisasi';
    });
    m.addEventListener('shown.bs.modal', () => f.uraian.focus());
    f.nilai.addEventListener('blur', () => f.nilai.value = fmt(f.nilai.value));

    <?php if (old('uraian') !== null): ?>
    // Gagal simpan: buka lagi dengan isian terakhir (Tom Select dipasang saat DOMContentLoaded).
    document.addEventListener('DOMContentLoaded', () => {
        aturPos(null, '<?= esc(old('pos_id') ?? '', 'js') ?>');
        if (f.mall) bdSetNilai(f.mall, '<?= esc(old('mall') ?? 'both', 'js') ?>');
        bootstrap.Modal.getOrCreateInstance(m).show();
    });
    <?php endif; ?>
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
