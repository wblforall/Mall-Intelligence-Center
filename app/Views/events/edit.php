<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Edit Event</h4>
</div>

<div class="row justify-content-center">
<div class="col-md-7">
<div class="card">
<div class="card-body p-4">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/edit') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Nama Event <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= old('name', $event['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Tema Event</label>
        <input type="text" name="tema" class="form-control" value="<?= old('tema', $event['tema'] ?? '') ?>" placeholder="IP Character, Seasonal, Brand...">
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Mall / Lokasi <span class="text-danger">*</span></label>
        <select name="mall" class="form-select">
            <option value="ewalk" <?= ($event['mall'] ?? '') === 'ewalk' ? 'selected' : '' ?>>eWalk</option>
            <option value="pentacity" <?= ($event['mall'] ?? '') === 'pentacity' ? 'selected' : '' ?>>Pentacity</option>
            <option value="keduanya" <?= ($event['mall'] ?? '') === 'keduanya' ? 'selected' : '' ?>>eWalk & Pentacity</option>
        </select>
    </div>
    <?php
    $editEndDate = old('end_date', $event['start_date']
        ? date('Y-m-d', strtotime($event['start_date'] . ' +' . (max(1, (int)$event['event_days']) - 1) . ' days'))
        : '');
    ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= old('start_date', $event['start_date']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $editEndDate ?>" required>
            <div class="form-text" id="days_info"></div>
        </div>
    </div>
    <!-- Lokasi Event -->
    <div class="mb-3" id="lokasi_wrap">
        <label class="form-label fw-semibold">Lokasi Event</label>
        <div id="lokasi_list" class="d-flex flex-wrap gap-2">
            <?php
            $oldLocs    = old('location_ids', null);
            $savedLocs  = $oldLocs !== null ? (array)$oldLocs : $selectedLocationIds;
            foreach ($locations as $loc):
                $checked = in_array($loc['id'], $savedLocs) ? 'checked' : '';
            ?>
            <div class="form-check form-check-inline lokasi-item" data-mall="<?= $loc['mall'] ?>">
                <input class="form-check-input" type="checkbox" name="location_ids[]"
                       id="loc_<?= $loc['id'] ?>" value="<?= $loc['id'] ?>" <?= $checked ?>>
                <label class="form-check-label small" for="loc_<?= $loc['id'] ?>"><?= esc($loc['nama']) ?></label>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($locations)): ?>
        <small class="text-muted">Belum ada lokasi. <a href="<?= base_url('event-locations') ?>">Tambah di Master Lokasi</a>.</small>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
        <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
</div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const startEl = document.getElementById('start_date');
const endEl   = document.getElementById('end_date');
const infoEl  = document.getElementById('days_info');

function updateDaysInfo() {
    const s = startEl.value, e = endEl.value;
    if (!s || !e) { infoEl.textContent = ''; return; }
    const diff = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
    if (diff < 1) {
        infoEl.textContent = 'Tanggal selesai harus setelah tanggal mulai';
        infoEl.className = 'form-text text-danger';
    } else {
        infoEl.textContent = diff + ' hari';
        infoEl.className = 'form-text text-muted';
    }
    endEl.min = s;
}

startEl.addEventListener('change', function () {
    if (endEl.value && endEl.value < this.value) endEl.value = this.value;
    endEl.min = this.value;
    updateDaysInfo();
});
endEl.addEventListener('change', updateDaysInfo);
updateDaysInfo();

// Filter lokasi berdasarkan mall
const mallSel = document.querySelector('[name="mall"]');
function filterLokasi() {
    const mall = mallSel.value;
    document.querySelectorAll('.lokasi-item').forEach(el => {
        const elMall = el.dataset.mall;
        const show   = mall === 'keduanya' || elMall === mall;
        el.style.display = show ? '' : 'none';
        if (!show) el.querySelector('input').checked = false;
    });
}
mallSel.addEventListener('change', filterLokasi);
filterLokasi();
</script>
<?= $this->endSection() ?>
