<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'exhibitors', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Exhibition by Casual Leasing</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#targetModal">
            <i class="bi bi-bullseye me-1"></i> Set Target
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Exhibition
        </button>
    </div>
    <?php endif; ?>
</div>

<?php
$totalDealing = array_sum(array_column($exhibitors, 'nilai_dealing'));
$byKategori   = [];
foreach ($exhibitors as $e) { $byKategori[$e['kategori']][] = $e; }
ksort($byKategori);

// Generate daftar tanggal selama periode event
$eventDates = [];
$startDt    = new DateTime($event['start_date']);
for ($i = 0; $i < (int)$event['event_days']; $i++) {
    $d = clone $startDt;
    $d->modify("+{$i} days");
    $eventDates[] = $d->format('Y-m-d');
}
?>

<!-- Summary bar -->
<?php
$tgtJumlah = (int)($target['target_jumlah']        ?? 0);
$tgtNilai  = (int)($target['target_nilai_dealing']  ?? 0);
$hasTarget = $tgtJumlah > 0 || $tgtNilai > 0;

$pctJumlah = ($tgtJumlah > 0) ? min(100, round(count($exhibitors) / $tgtJumlah * 100)) : null;
$pctNilai  = ($tgtNilai  > 0) ? min(100, round($totalDealing       / $tgtNilai  * 100)) : null;

$colorJumlah = $pctJumlah === null ? 'primary' : ($pctJumlah >= 100 ? 'success' : ($pctJumlah >= 60 ? 'primary' : ($pctJumlah >= 30 ? 'warning' : 'danger')));
$colorNilai  = $pctNilai  === null ? 'success' : ($pctNilai  >= 100 ? 'success' : ($pctNilai  >= 60 ? 'primary' : ($pctNilai  >= 30 ? 'warning' : 'danger')));
?>
<?php if (! empty($exhibitors) || $hasTarget): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="fw-bold fs-4"><?= count($exhibitors) ?></div>
            <small class="text-muted">Total Exhibition</small>
            <?php if ($tgtJumlah > 0): ?>
            <div class="progress mt-2" style="height:5px">
                <div class="progress-bar bg-<?= $colorJumlah ?>" style="width:<?= $pctJumlah ?>%"></div>
            </div>
            <small class="text-<?= $colorJumlah ?> fw-semibold"><?= $pctJumlah ?>%</small>
            <small class="text-muted"> dari target <?= $tgtJumlah ?></small>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body py-3">
            <div class="fw-bold fs-4"><?= count($byKategori) ?></div>
            <small class="text-muted">Kategori</small>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body py-3">
            <div class="small text-muted">Total Nilai Dealing</div>
            <div class="fw-bold fs-5 text-<?= $colorNilai ?>">Rp <?= number_format($totalDealing,0,',','.') ?></div>
            <?php if ($tgtNilai > 0): ?>
            <div class="progress mt-2" style="height:5px">
                <div class="progress-bar bg-<?= $colorNilai ?>" style="width:<?= $pctNilai ?>%"></div>
            </div>
            <small class="text-<?= $colorNilai ?> fw-semibold"><?= $pctNilai ?>%</small>
            <small class="text-muted"> dari target Rp <?= number_format($tgtNilai,0,',','.') ?></small>
            <?php endif; ?>
        </div></div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
<div class="col-12">

<?php if (empty($exhibitors)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-shop display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada exhibition untuk event ini.</p>
</div></div>
<?php else: ?>
<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr>
    <th>Nama Exhibition</th>
    <th>Lokasi Booth</th>
    <th>Program</th>
    <th class="text-end">Nilai Dealing</th>
    <?php if ($canEdit): ?><th class="text-end" style="width:100px"></th><?php endif; ?>
</tr></thead>
<tbody>
<?php foreach ($byKategori as $kat => $items): ?>
<tr class="table-secondary">
    <td colspan="<?= $canEdit ? 5 : 4 ?>" class="small fw-semibold text-uppercase text-muted py-1 ps-3">
        <i class="bi bi-tag me-1"></i><?= esc($kat) ?>
        <span class="badge bg-secondary ms-1"><?= count($items) ?></span>
        <span class="float-end me-2 text-body fw-bold">Rp <?= number_format(array_sum(array_column($items,'nilai_dealing')),0,',','.') ?></span>
    </td>
</tr>
<?php foreach ($items as $ex): ?>
<?php $exPrograms = $programs[$ex['id']] ?? []; ?>
<tr id="ex-<?= $ex['id'] ?>">
    <td class="fw-medium">
        <?= esc($ex['nama_exhibitor']) ?>
        <?php if ($ex['catatan']): ?><br><small class="text-muted"><?= esc($ex['catatan']) ?></small><?php endif; ?>
    </td>
    <td class="small"><?= esc($ex['lokasi_booth']) ?: '—' ?></td>
    <td>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#programsModal-<?= $ex['id'] ?>">
            <i class="bi bi-calendar-event me-1"></i>
            <?php if (count($exPrograms) > 0): ?>
                <span class="badge bg-primary rounded-pill"><?= count($exPrograms) ?></span>
            <?php else: ?>
                <span class="text-muted small">Tambah</span>
            <?php endif; ?>
        </button>
        <?php if (!empty($exPrograms)): ?>
        <div class="mt-1">
        <?php foreach ($exPrograms as $ep): ?>
        <?php
            $jam = '';
            if ($ep['jam_mulai'])   $jam  = substr($ep['jam_mulai'], 0, 5);
            if ($ep['jam_selesai']) $jam .= '–' . substr($ep['jam_selesai'], 0, 5);
        ?>
        <div class="text-muted" style="font-size:.72rem; line-height:1.4">
            <i class="bi bi-dot"></i><?= esc($ep['nama_program']) ?>
            <?php if ($ep['tanggal_mulai']): ?>
            <span class="text-primary"><?= date('d/m', strtotime($ep['tanggal_mulai'])) ?><?= ($ep['tanggal_selesai'] && $ep['tanggal_selesai'] !== $ep['tanggal_mulai']) ? '–'.date('d/m', strtotime($ep['tanggal_selesai'])) : '' ?></span>
            <?php endif; ?>
            <?php if ($jam): ?><span class="text-secondary"><?= $jam ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </td>
    <td class="text-end fw-medium">Rp <?= number_format($ex['nilai_dealing'],0,',','.') ?></td>
    <?php if ($canEdit): ?>
    <td class="text-end">
        <button class="btn btn-sm btn-outline-secondary edit-btn me-1"
            data-id="<?= $ex['id'] ?>"
            data-nama="<?= esc($ex['nama_exhibitor'], 'attr') ?>"
            data-kategori="<?= esc($ex['kategori'], 'attr') ?>"
            data-nilai="<?= number_format($ex['nilai_dealing'],0,',','.') ?>"
            data-lokasi="<?= esc($ex['lokasi_booth'], 'attr') ?>"
            data-catatan="<?= esc($ex['catatan'], 'attr') ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/'.$ex['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus exhibitor ini?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="table-light fw-bold">
    <td colspan="3">Total</td>
    <td class="text-end text-success">Rp <?= number_format($totalDealing,0,',','.') ?></td>
    <?php if ($canEdit): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
</div>
</div>
<?php endif; ?>
</div>

</div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Exhibitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Exhibition <span class="text-danger">*</span></label>
        <input type="text" name="nama_exhibitor" class="form-control" required></div>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select name="kategori" class="form-select kategori-select" required data-other-target="addKategoriCustom">
                <option value="">— Pilih —</option>
                <?php foreach ($kategoriOptions as $k): ?>
                <option value="<?= esc($k, 'attr') ?>"><?= esc($k) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="kategori_custom" id="addKategoriCustom" class="form-control mt-1 d-none" placeholder="Tulis kategori...">
        </div>
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Lokasi Booth</label>
            <input type="text" name="lokasi_booth" class="form-control" placeholder="A1, Lantai 1..."></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Nilai Dealing (Rp)</label>
        <input type="text" name="nilai_dealing" class="form-control currency-input" value="0"></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Exhibitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Exhibition</label>
        <input type="text" name="nama_exhibitor" id="eNama" class="form-control" required></div>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Kategori</label>
            <select name="kategori" id="eKat" class="form-select kategori-select" required data-other-target="editKategoriCustom">
                <option value="">— Pilih —</option>
                <?php foreach ($kategoriOptions as $k): ?>
                <option value="<?= esc($k, 'attr') ?>"><?= esc($k) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="kategori_custom" id="editKategoriCustom" class="form-control mt-1 d-none" placeholder="Tulis kategori...">
        </div>
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Lokasi Booth</label>
            <input type="text" name="lokasi_booth" id="eLok" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Nilai Dealing (Rp)</label>
        <input type="text" name="nilai_dealing" id="eNilai" class="form-control currency-input"></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="eCatatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<!-- Target Modal -->
<?php if ($canEdit): ?>
<div class="modal fade" id="targetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/save-target') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold"><i class="bi bi-bullseye me-2 text-primary"></i>Set Target Exhibition</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p class="text-muted small mb-3">Target digunakan sebagai pembanding di halaman ini dan di Summary Event.</p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Jumlah Exhibitor</label>
        <input type="number" name="target_jumlah" class="form-control" min="0"
               value="<?= (int)($target['target_jumlah'] ?? 0) ?>" placeholder="0">
        <div class="form-text">Berapa booth exhibitor yang direncanakan.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Total Nilai Dealing (Rp)</label>
        <input type="text" name="target_nilai_dealing" class="form-control currency-input"
               value="<?= ($target['target_nilai_dealing'] ?? 0) > 0 ? number_format((int)$target['target_nilai_dealing'],0,',','.') : '' ?>"
               placeholder="0">
        <div class="form-text">Target total revenue dari semua exhibitor.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Target</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<!-- Program Modals (one per exhibitor) -->
<?php foreach ($exhibitors as $ex): ?>
<?php $exPrograms = $programs[$ex['id']] ?? []; ?>
<div class="modal fade" id="programsModal-<?= $ex['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <div>
        <h5 class="modal-title fw-semibold"><i class="bi bi-calendar-event me-2"></i>Program Exhibitor</h5>
        <small class="text-muted"><?= esc($ex['nama_exhibitor']) ?></small>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <?php if (empty($exPrograms)): ?>
    <p class="text-muted text-center py-3"><i class="bi bi-calendar-x d-block fs-3 mb-2 opacity-25"></i>Belum ada program.</p>
    <?php else: ?>
    <table class="table table-sm mb-4">
    <thead><tr><th>Nama Program</th><th>Periode</th><th>Jam</th><th>Deskripsi</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($exPrograms as $p): ?>
    <?php
        // Format periode tanggal
        if ($p['tanggal_mulai'] && $p['tanggal_selesai'] && $p['tanggal_mulai'] !== $p['tanggal_selesai']) {
            $periodeTgl = date('d M', strtotime($p['tanggal_mulai'])) . ' – ' . date('d M Y', strtotime($p['tanggal_selesai']));
        } elseif ($p['tanggal_mulai']) {
            $periodeTgl = date('d M Y', strtotime($p['tanggal_mulai']));
        } else {
            $periodeTgl = '—';
        }
        // Format jam
        $jam = '';
        if ($p['jam_mulai'])   $jam  = substr($p['jam_mulai'], 0, 5);
        if ($p['jam_selesai']) $jam .= ' – ' . substr($p['jam_selesai'], 0, 5);
        if (!$jam)             $jam  = '—';
    ?>
    <tr>
        <td class="fw-medium"><?= esc($p['nama_program']) ?></td>
        <td class="small text-nowrap"><?= $periodeTgl ?></td>
        <td class="small text-nowrap"><?= $jam ?></td>
        <td class="small text-muted"><?= esc($p['deskripsi']) ?: '—' ?></td>
        <?php if ($canEdit): ?>
        <td>
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/'.$ex['id'].'/programs/'.$p['id'].'/delete') ?>" onsubmit="return confirm('Hapus program ini?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>

    <?php if ($canEdit): ?>
    <hr class="my-3">
    <h6 class="fw-semibold mb-3">Tambah Program Baru</h6>
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/'.$ex['id'].'/programs/add') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
            <input type="text" name="nama_program" class="form-control" required placeholder="Contoh: Diskon 30%, Demo Produk, Buy 1 Get 1...">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                <select name="tanggal_mulai" class="form-select form-select-sm tgl-mulai-select">
                    <option value="">— Pilih tanggal —</option>
                    <?php foreach ($eventDates as $d): ?>
                    <option value="<?= $d ?>"><?= date('D, d M Y', strtotime($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                <select name="tanggal_selesai" class="form-select form-select-sm tgl-selesai-select">
                    <option value="">— Sama dengan mulai —</option>
                    <?php foreach ($eventDates as $d): ?>
                    <option value="<?= $d ?>"><?= date('D, d M Y', strtotime($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control form-control-sm">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Deskripsi</label>
            <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Detail program (opsional)">
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Program</button>
        </div>
    </form>
    <?php else: ?>
    <div class="text-end"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    <?php endif; ?>
</div>
</div>
</div>
</div>
<?php endforeach; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if ($canEdit): ?>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action = '<?= base_url('events/'.$event['id'].'/exhibitors/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eNama').value    = this.dataset.nama;
        document.getElementById('eNilai').value   = this.dataset.nilai;
        document.getElementById('eLok').value     = this.dataset.lokasi;
        document.getElementById('eCatatan').value = this.dataset.catatan;
        setKategoriSelect(document.getElementById('eKat'), this.dataset.kategori, document.getElementById('editKategoriCustom'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

<?php endif; ?>

// Kategori select: show/hide custom input for "Lainnya"
function toggleKategoriCustom(select, customInput) {
    const isOther = select.value === 'Lainnya';
    customInput.classList.toggle('d-none', !isOther);
    customInput.required = isOther;
    if (!isOther) customInput.value = '';
}

function setKategoriSelect(select, value, customInput) {
    const opt = [...select.options].find(o => o.value === value);
    if (opt) {
        select.value = value;
        toggleKategoriCustom(select, customInput);
    } else {
        select.value = 'Lainnya';
        customInput.classList.remove('d-none');
        customInput.required = true;
        customInput.value = value;
    }
}

document.querySelectorAll('.kategori-select').forEach(sel => {
    const customInput = document.getElementById(sel.dataset.otherTarget);
    sel.addEventListener('change', () => toggleKategoriCustom(sel, customInput));
    sel.closest('form').addEventListener('submit', function() {
        if (sel.value === 'Lainnya' && customInput.value.trim()) {
            sel.value = customInput.value.trim();
        }
    });
});

// Currency input formatting
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('currency-input')) return;
    const n = parseInt(e.target.value.replace(/[^0-9]/g, '')) || 0;
    e.target.value = n.toLocaleString('id-ID');
});

// Tanggal Selesai otomatis ikut Tanggal Mulai jika belum dipilih
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('tgl-mulai-select')) return;
    const form     = e.target.closest('form');
    const selesai  = form.querySelector('.tgl-selesai-select');
    if (selesai && !selesai.value) selesai.value = e.target.value;
});
</script>
<?= $this->endSection() ?>
