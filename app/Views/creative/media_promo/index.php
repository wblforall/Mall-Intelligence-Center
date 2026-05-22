<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-megaphone me-2"></i>Media Promo</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('creative/media-promo/gantt') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart-gantt me-1"></i>Gantt
        </a>
        <a href="<?= base_url('creative/media-promo/my') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-list-check me-1"></i>Request Saya
        </a>
        <?php if ($canApprove): ?>
        <a href="<?= base_url('creative/media-promo/pending') ?>" class="btn btn-sm btn-warning">
            <i class="bi bi-hourglass-split me-1"></i>Pending Approval
        </a>
        <?php endif; ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBuatRequest">
            <i class="bi bi-plus-lg me-1"></i>Buat Request
        </button>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTambahTitik">
            <i class="bi bi-pin-map me-1"></i>Tambah Titik
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Media Cetak -->
<h6 class="fw-semibold text-muted mb-2"><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</h6>
<?php if (empty($cetak)): ?>
<div class="card mb-4"><div class="card-body text-center text-muted py-4 small">Belum ada titik media cetak.</div></div>
<?php else: ?>
<div class="row g-2 mb-4">
<?php foreach ($cetak as $spot):
    $occupied = !empty($spot['active_usage']);
    $isPending = $occupied && $spot['active_usage']['status'] === 'pending';
?>
<div class="col-md-3 col-sm-4">
<div class="card h-100 border-<?= $occupied ? ($isPending ? 'warning' : 'danger') : 'success' ?>">
<div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-secondary small"><?= esc($spot['kode']) ?></span>
        <?php
        $tipeBadgeMap = ['t_banner'=>'primary','hanging'=>'info','sticker_lift'=>'warning','totem_stainless'=>'secondary'];
        $tipeLabelMap = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift','totem_stainless'=>'Totem Stainless'];
        ?>
        <span class="badge bg-<?= $tipeBadgeMap[$spot['tipe']] ?? 'secondary' ?> text-dark small">
            <?= $tipeLabelMap[$spot['tipe']] ?? esc($spot['tipe']) ?>
        </span>
    </div>
    <div class="fw-semibold small"><?= esc($spot['nama']) ?></div>
    <?php if ($spot['area']): ?><div class="text-muted" style="font-size:.72rem"><?= esc($spot['area']) ?></div><?php endif; ?>
    <?php if ($spot['ukuran']): ?><div class="text-muted" style="font-size:.72rem"><?= esc($spot['ukuran']) ?></div><?php endif; ?>
    <hr class="my-2">
    <?php if ($occupied): ?>
    <div class="small">
        <span class="badge bg-<?= $isPending ? 'warning text-dark' : 'danger' ?> mb-1">
            <?= $isPending ? 'Pending' : 'Terpakai' ?>
        </span>
        <div class="fw-semibold text-truncate"><?= esc($spot['active_usage']['nama_materi']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= esc($spot['active_usage']['dept']) ?></div>
        <div class="text-muted" style="font-size:.72rem">
            <?= date('d M', strtotime($spot['active_usage']['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($spot['active_usage']['tanggal_selesai'])) ?>
        </div>
    </div>
    <?php else: ?>
    <div class="text-success small"><i class="bi bi-check-circle me-1"></i>Tersedia</div>
    <?php endif; ?>
    <?php if ($canEdit): ?>
    <div class="mt-2 d-flex gap-1">
        <button class="btn btn-xs btn-outline-secondary py-0 px-1"
            onclick="openEditSpot(<?= htmlspecialchars(json_encode($spot)) ?>)" title="Edit">
            <i class="bi bi-pencil" style="font-size:.7rem"></i>
        </button>
        <a href="<?= base_url('creative/media-promo/spots/'.$spot['id'].'/delete') ?>"
           onclick="return confirm('Hapus titik ini?')"
           class="btn btn-xs btn-outline-danger py-0 px-1" title="Hapus">
            <i class="bi bi-trash" style="font-size:.7rem"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Media Digital -->
<h6 class="fw-semibold text-muted mb-2"><i class="bi bi-display me-1"></i>Media Digital</h6>
<?php if (empty($digital)): ?>
<div class="card"><div class="card-body text-center text-muted py-4 small">Belum ada titik media digital.</div></div>
<?php else: ?>
<div class="row g-2">
<?php foreach ($digital as $spot):
    $usedCount = count($spot['used_slots'] ?? []);
    $totalSlots = (int)$spot['total_slots'];
    $pct = $totalSlots > 0 ? round($usedCount / $totalSlots * 100) : 0;
    $barClass = $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'success');
?>
<div class="col-md-4 col-sm-6">
<div class="card h-100">
<div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-secondary small"><?= esc($spot['kode']) ?></span>
        <span class="badge bg-dark small">Digital</span>
    </div>
    <div class="fw-semibold small"><?= esc($spot['nama']) ?></div>
    <?php if ($spot['area']): ?><div class="text-muted" style="font-size:.72rem"><?= esc($spot['area']) ?></div><?php endif; ?>
    <hr class="my-2">
    <div class="d-flex justify-content-between small mb-1">
        <span><?= $usedCount ?>/<?= $totalSlots ?> slot terpakai</span>
        <span class="text-<?= $spot['sisa_slots'] > 0 ? 'success' : 'danger' ?>"><?= $spot['sisa_slots'] ?> bebas</span>
    </div>
    <div class="progress mb-2" style="height:6px">
        <div class="progress-bar bg-<?= $barClass ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <!-- Slot grid -->
    <div class="d-flex flex-wrap gap-1">
    <?php for ($s = 1; $s <= $totalSlots; $s++): ?>
        <span class="badge bg-<?= in_array($s, $spot['used_slots']) ? 'danger' : 'success' ?>"
              style="font-size:.65rem;min-width:22px"><?= $s ?></span>
    <?php endfor; ?>
    </div>
    <?php if ($canEdit): ?>
    <div class="mt-2 d-flex gap-1">
        <button class="btn btn-xs btn-outline-secondary py-0 px-1"
            onclick="openEditSpot(<?= htmlspecialchars(json_encode($spot)) ?>)" title="Edit">
            <i class="bi bi-pencil" style="font-size:.7rem"></i>
        </button>
        <a href="<?= base_url('creative/media-promo/spots/'.$spot['id'].'/delete') ?>"
           onclick="return confirm('Hapus titik ini?')"
           class="btn btn-xs btn-outline-danger py-0 px-1" title="Hapus">
            <i class="bi bi-trash" style="font-size:.7rem"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Tambah Titik -->
<div class="modal fade" id="modalTambahTitik" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/media-promo/spots/store') ?>"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Titik Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <label class="form-label small fw-semibold">Kode <span class="text-danger">*</span></label>
            <input type="text" name="kode" class="form-control text-uppercase" required maxlength="20">
        </div>
        <div class="col-8">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tipe <span class="text-danger">*</span></label>
            <select name="tipe" class="form-select" required>
                <option value="">-- Pilih --</option>
                <option value="t_banner">T-Banner</option>
                <option value="hanging">Hanging Banner</option>
                <option value="sticker_lift">Sticker Lift</option>
                <option value="totem_stainless">Totem Stainless</option>
                <option value="digital">Digital</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Area/Lokasi</label>
            <input type="text" name="area" class="form-control" placeholder="Lantai 1 - Area A">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Ukuran</label>
        <input type="text" name="ukuran" class="form-control" placeholder="60x160cm">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form></div></div></div>

<!-- Modal Edit Titik -->
<div class="modal fade" id="modalEditTitik" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" id="formEditTitik" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Titik Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <label class="form-label small fw-semibold">Kode <span class="text-danger">*</span></label>
            <input type="text" name="kode" id="edit_spot_kode" class="form-control text-uppercase" required maxlength="20">
        </div>
        <div class="col-8">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="edit_spot_nama" class="form-control" required>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tipe</label>
            <input type="text" id="edit_spot_tipe_label" class="form-control" readonly>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Area/Lokasi</label>
            <input type="text" name="area" id="edit_spot_area" class="form-control">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Ukuran</label>
        <input type="text" name="ukuran" id="edit_spot_ukuran" class="form-control">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_spot_catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form></div></div></div>

<!-- Modal Buat Request -->
<div class="modal fade" id="modalBuatRequest" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/media-promo/usage/store') ?>" id="formBuatRequest"><?= csrf_field() ?>
<input type="hidden" name="req_mode" id="reqMode" value="cetak">
<div class="modal-header"><h5 class="modal-title">Buat Request Media Promo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <!-- Mode toggle -->
    <div class="d-flex gap-4 mb-3 pb-3 border-bottom">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="req_mode_ui" id="modeCetak" value="cetak" checked onchange="switchReqMode('cetak')">
            <label class="form-check-label fw-semibold" for="modeCetak"><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="req_mode_ui" id="modeDigital" value="digital" onchange="switchReqMode('digital')">
            <label class="form-check-label fw-semibold" for="modeDigital"><i class="bi bi-display me-1"></i>Media Digital</label>
        </div>
    </div>
    <!-- Shared fields -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Materi <span class="text-danger">*</span></label>
            <input type="text" name="nama_materi" class="form-control" required placeholder="Contoh: Banner Promo Lebaran 2026">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Requested By</label>
            <input type="text" name="requested_by" class="form-control" placeholder="Nama pemohon">
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Departemen <span class="text-danger">*</span></label>
            <select name="dept" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?= esc($d) ?>"><?= esc($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_mulai" id="reqTglMulai" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_selesai" id="reqTglSelesai" class="form-control" required>
        </div>
    </div>
    <!-- Cetak: checkbox list -->
    <div id="cetakSection">
        <label class="form-label small fw-semibold">Pilih Titik Media Cetak <span class="text-danger">*</span></label>
        <?php if (empty($cetak)): ?>
        <div class="border rounded p-3 text-center text-muted small">Belum ada titik media cetak.</div>
        <?php else: ?>
        <div class="border rounded p-2" style="max-height:200px;overflow-y:auto">
        <?php foreach ($cetak as $s):
            $occ = !empty($s['active_usage']);
            $isPend = $occ && $s['active_usage']['status'] === 'pending';
        ?>
            <div class="form-check py-1 border-bottom">
                <input class="form-check-input cetak-check" type="checkbox" name="spot_ids[]"
                       value="<?= $s['id'] ?>" id="spotCk_<?= $s['id'] ?>">
                <label class="form-check-label d-flex align-items-center gap-2 small w-100" for="spotCk_<?= $s['id'] ?>">
                    <span class="badge bg-secondary font-monospace"><?= esc($s['kode']) ?></span>
                    <span><?= esc($s['nama']) ?></span>
                    <?php if ($s['area']): ?><span class="text-muted">· <?= esc($s['area']) ?></span><?php endif; ?>
                    <span class="badge bg-<?= $occ ? ($isPend ? 'warning text-dark' : 'danger') : 'success' ?> ms-auto avail-badge">
                        <?= $occ ? ($isPend ? 'Pending' : 'Terpakai') : 'Tersedia' ?>
                    </span>
                </label>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div id="cetakNoCheck" class="text-danger small mt-1" style="display:none">Pilih minimal 1 titik.</div>
    </div>
    <!-- Digital: spot + slot grid -->
    <div id="digitalSection" style="display:none">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Titik Digital <span class="text-danger">*</span></label>
            <select name="spot_id" id="reqSpotId" class="form-select">
                <option value="">-- Pilih Titik --</option>
                <?php foreach ($digital as $s): ?>
                <option value="<?= $s['id'] ?>">
                    [<?= esc($s['kode']) ?>] <?= esc($s['nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label small fw-semibold">Pilih Slot <span class="text-danger">*</span></label>
            <div id="slotGrid" class="d-flex flex-wrap gap-1 border rounded p-2 bg-light" style="min-height:42px">
                <span class="text-muted small align-self-center">Pilih titik dan isi tanggal terlebih dahulu.</span>
            </div>
            <div class="form-text small text-muted" id="slotInfo"></div>
            <div id="digitalNoSlot" class="text-danger small mt-1" style="display:none">Pilih minimal 1 slot.</div>
        </div>
    </div>
    <!-- Shared bottom -->
    <div class="row g-3 mt-1">
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Sumber Materi <span class="text-danger">*</span></label>
            <select name="sumber" class="form-select" required>
                <option value="internal">Internal Manajemen</option>
                <option value="tenant">Tenant Mall</option>
                <option value="external">External Client</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end pb-1">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_berbayar" id="reqIsBerbayar" role="switch">
                <label class="form-check-label fw-semibold" for="reqIsBerbayar">Berbayar</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Deskripsi Materi</label>
            <textarea name="deskripsi_materi" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <textarea name="catatan_pemohon" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan sebagai Draft</button>
</div>
</form></div></div></div>

<script>
const BASE_URL = '<?= base_url() ?>';

function openEditSpot(spot) {
    const tipeLabel = {t_banner: 'T-Banner', hanging: 'Hanging Banner', sticker_lift: 'Sticker Lift', totem_stainless: 'Totem Stainless', digital: 'Digital'};
    document.getElementById('edit_spot_kode').value        = spot.kode;
    document.getElementById('edit_spot_nama').value        = spot.nama;
    document.getElementById('edit_spot_tipe_label').value  = tipeLabel[spot.tipe] || spot.tipe;
    document.getElementById('edit_spot_area').value        = spot.area || '';
    document.getElementById('edit_spot_ukuran').value      = spot.ukuran || '';
    document.getElementById('edit_spot_catatan').value     = spot.catatan || '';
    document.getElementById('formEditTitik').action        = BASE_URL + 'creative/media-promo/spots/' + spot.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditTitik')).show();
}

function switchReqMode(mode) {
    document.getElementById('reqMode').value = mode;
    document.getElementById('cetakSection').style.display   = mode === 'cetak' ? '' : 'none';
    document.getElementById('digitalSection').style.display = mode === 'digital' ? '' : 'none';
}

document.getElementById('reqSpotId').addEventListener('change', loadAvailableSlots);

function loadAvailableSlots() {
    const spotId     = document.getElementById('reqSpotId').value;
    const tglMulai   = document.getElementById('reqTglMulai').value;
    const tglSelesai = document.getElementById('reqTglSelesai').value;
    const grid       = document.getElementById('slotGrid');

    if (!spotId || !tglMulai || !tglSelesai) {
        grid.innerHTML = '<span class="text-muted small align-self-center">Pilih titik dan isi tanggal terlebih dahulu.</span>';
        document.getElementById('slotInfo').textContent = '';
        return;
    }

    grid.innerHTML = '<span class="text-muted small align-self-center"><i class="bi bi-hourglass-split me-1"></i>Memuat...</span>';
    fetch(BASE_URL + 'creative/media-promo/spots/' + spotId + '/slots?tgl_mulai=' + tglMulai + '&tgl_selesai=' + tglSelesai)
        .then(r => r.json())
        .then(data => {
            const available = new Set(data.available);
            let html = '';
            for (let s = 1; s <= data.total; s++) {
                const ok = available.has(s);
                html += `<div>
                    <input type="checkbox" class="btn-check slot-check" name="slot_numbers[]"
                           id="slotCk_${s}" value="${s}" ${ok ? '' : 'disabled'} autocomplete="off">
                    <label class="btn btn-sm ${ok ? 'btn-outline-success' : 'btn-outline-danger disabled'}"
                           for="slotCk_${s}" style="min-width:38px">${s}</label>
                </div>`;
            }
            grid.innerHTML = html || '<span class="text-muted small">Tidak ada slot.</span>';
            document.getElementById('slotInfo').textContent =
                data.available.length + ' dari ' + data.total + ' slot tersedia';
        });
}

function checkCetakDates() {
    const tglMulai   = document.getElementById('reqTglMulai').value;
    const tglSelesai = document.getElementById('reqTglSelesai').value;
    if (!tglMulai || !tglSelesai || tglMulai > tglSelesai) return;

    fetch(BASE_URL + 'creative/media-promo/spots/check-cetak?tgl_mulai=' + tglMulai + '&tgl_selesai=' + tglSelesai)
        .then(r => r.json())
        .then(data => {
            const occupied = new Set(data.occupied.map(String));
            document.querySelectorAll('.cetak-check').forEach(chk => {
                const isOccupied = occupied.has(String(chk.value));
                const badge = chk.closest('.form-check').querySelector('.avail-badge');
                chk.disabled = isOccupied;
                if (isOccupied) {
                    chk.checked = false;
                    if (badge) { badge.className = 'badge bg-danger ms-auto avail-badge'; badge.textContent = 'Terpakai'; }
                } else {
                    if (badge) { badge.className = 'badge bg-success ms-auto avail-badge'; badge.textContent = 'Tersedia'; }
                }
            });
        });
}

['reqTglMulai', 'reqTglSelesai'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        const mode = document.getElementById('reqMode').value;
        if (mode === 'cetak') checkCetakDates();
        if (mode === 'digital') loadAvailableSlots();
    });
});

document.getElementById('formBuatRequest').addEventListener('submit', function(e) {
    const mode = document.getElementById('reqMode').value;
    if (mode === 'cetak') {
        const checked    = document.querySelectorAll('.cetak-check:checked');
        const noCheckDiv = document.getElementById('cetakNoCheck');
        if (checked.length === 0) {
            e.preventDefault();
            noCheckDiv.style.display = '';
            return;
        }
        noCheckDiv.style.display = 'none';
    } else {
        const noSpot = !document.getElementById('reqSpotId').value;
        const noSlot = document.querySelectorAll('.slot-check:checked').length === 0;
        if (noSpot || noSlot) {
            e.preventDefault();
            document.getElementById('digitalNoSlot').style.display = noSlot ? '' : 'none';
            return;
        }
        document.getElementById('digitalNoSlot').style.display = 'none';
    }
});
</script>

<?= $this->endSection() ?>
