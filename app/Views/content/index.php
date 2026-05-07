<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.anim-fade-up {
    opacity: 0;
    animation: fadeUp .48s cubic-bezier(.22,.68,0,1.15) forwards;
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'content', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<div class="d-flex align-items-center gap-2 mb-4 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Content Event</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" onclick="setAddTipe('program')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Program
        </button>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" onclick="setAddTipe('biaya')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Biaya
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Deskripsi -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.1s">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-file-text me-2"></i>Deskripsi & Konsep Event</h6></div>
<div class="card-body">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/content/save-content') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <textarea name="content" class="form-control" rows="4"
              placeholder="Deskripsikan program dan konsep event secara umum..."
              <?= ! $canEdit ? 'readonly' : '' ?>><?= esc($event['content']) ?></textarea>
</div>
<?php if ($canEdit): ?>
<button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Deskripsi</button>
<?php endif; ?>
</form>
</div>
</div>

<!-- KPI -->
<?php if (! empty($items)): ?>
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.15s">
    <div class="col-md-2"><div class="card text-center h-100"><div class="card-body py-3">
        <div class="fw-bold fs-4"><?= count($items) ?></div>
        <small class="text-muted">Total Item</small>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="small text-muted">Total Budget</div>
        <div class="fw-bold text-danger fs-5">Rp <?= number_format($totalBudget,0,',','.') ?></div>
        <?php if ($budgetProgram > 0 && $budgetBiaya > 0): ?>
        <div class="mt-1 pt-1 border-top" style="font-size:.72rem">
            <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-collection-play me-1"></i>Program</span><span>Rp <?= number_format($budgetProgram,0,',','.') ?></span></div>
            <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-receipt me-1"></i>Biaya</span><span>Rp <?= number_format($budgetBiaya,0,',','.') ?></span></div>
        </div>
        <?php endif; ?>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <div class="small text-muted">Total Realisasi</div>
            <span class="badge bg-<?= $barGlobal ?>-subtle text-<?= $barGlobal ?> small"><?= $pctGlobal ?>%</span>
        </div>
        <div class="fw-bold text-<?= $barGlobal ?> fs-5">Rp <?= number_format($totalRealisasi,0,',','.') ?></div>
        <div class="progress mt-2" style="height:5px">
            <div class="progress-bar bg-<?= $barGlobal ?>" style="width:<?= $pctGlobal ?>%"></div>
        </div>
    </div></div></div>
</div>
<?php endif; ?>

<?php
// Reusable macro: render one item card
function renderItemCard(array $item, array $rList, array $realisasi, array $locations, array $event, bool $canEdit): void {
    $rTotal   = array_sum(array_column($rList, 'nilai'));
    $pct      = $item['budget'] > 0 ? min(100, round($rTotal / $item['budget'] * 100)) : 0;
    $barColor = $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'success');
    $waktu    = '';
    if (! empty($item['waktu_mulai'])) {
        $waktu = date('H:i', strtotime($item['waktu_mulai']));
        if (! empty($item['waktu_selesai'])) $waktu .= '–' . date('H:i', strtotime($item['waktu_selesai']));
    }
    $isBiaya = ($item['tipe'] ?? 'program') === 'biaya';
    $uploadBase = base_url('uploads/content-realisasi/' . $item['event_id'] . '/');
?>
<div class="card mb-3 anim-fade-up item-card" id="item-<?= $item['id'] ?>">
<div class="card-header d-flex align-items-start gap-2">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold"><?= esc($item['nama']) ?></span>
            <?php if ($item['jenis']): ?><span class="badge bg-secondary-subtle text-secondary"><?= esc($item['jenis']) ?></span><?php endif; ?>
        </div>
        <?php if (! $isBiaya): ?>
        <div class="small text-muted mt-1 d-flex flex-wrap gap-3">
            <?php if ($item['tanggal']): ?><span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($item['tanggal'])) ?><?= $waktu ? ' · ' . $waktu : '' ?></span><?php endif; ?>
            <?php if ($item['lokasi']): ?><span><i class="bi bi-geo-alt me-1"></i><?= esc($item['lokasi']) ?></span><?php endif; ?>
            <?php if ($item['pic']): ?><span><i class="bi bi-person me-1"></i><?= esc($item['pic']) ?></span><?php endif; ?>
        </div>
        <?php else: ?>
        <?php if ($item['pic']): ?><div class="small text-muted mt-1"><i class="bi bi-person me-1"></i><?= esc($item['pic']) ?></div><?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="text-end ms-2 flex-shrink-0">
        <div class="small text-muted">Budget</div>
        <div class="fw-bold text-danger"><?= $item['budget'] ? 'Rp ' . number_format($item['budget'],0,',','.') : '—' ?></div>
    </div>
    <?php if ($canEdit): ?>
    <div class="flex-shrink-0 d-flex gap-1">
        <button class="btn btn-sm btn-outline-secondary edit-item-btn"
            data-id="<?= $item['id'] ?>"
            data-tipe="<?= $item['tipe'] ?? 'program' ?>"
            data-nama="<?= esc($item['nama'], 'attr') ?>"
            data-tanggal="<?= esc($item['tanggal'] ?? '', 'attr') ?>"
            data-mulai="<?= esc($item['waktu_mulai'] ?? '', 'attr') ?>"
            data-selesai="<?= esc($item['waktu_selesai'] ?? '', 'attr') ?>"
            data-jenis="<?= esc($item['jenis'] ?? '', 'attr') ?>"
            data-pic="<?= esc($item['pic'] ?? '', 'attr') ?>"
            data-lokasi="<?= esc($item['lokasi'] ?? '', 'attr') ?>"
            data-budget="<?= number_format($item['budget'],0,',','.') ?>"
            data-keterangan="<?= esc($item['keterangan'] ?? '', 'attr') ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <a href="<?= base_url('events/'.$event['id'].'/content/'.$item['id'].'/delete-item') ?>"
           class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus item ini?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
<div class="card-body">
    <?php if ($item['keterangan']): ?>
    <p class="small text-muted mb-3"><?= esc($item['keterangan']) ?></p>
    <?php endif; ?>

    <?php if ($item['budget'] > 0): ?>
    <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">Realisasi</span>
        <span class="fw-semibold text-<?= $barColor ?>">Rp <?= number_format($rTotal,0,',','.') ?> / Rp <?= number_format($item['budget'],0,',','.') ?> (<?= $pct ?>%)</span>
    </div>
    <div class="progress mb-3" style="height:6px">
        <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <?php endif; ?>

    <?php if (! empty($rList)): ?>
    <div class="table-responsive mb-3">
    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
    <thead class="table-light"><tr>
        <th>Tanggal</th><th class="text-end">Nilai Realisasi</th><th>Catatan</th>
        <th class="text-center">Foto Dokumentasi</th><th class="text-center">Bukti Tanda Terima</th>
        <?php if ($canEdit): ?><th></th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($rList as $r): ?>
    <tr>
        <td class="text-nowrap"><?= $r['tanggal'] ? date('d M Y', strtotime($r['tanggal'])) : '—' ?></td>
        <td class="text-end fw-medium"><?= $r['nilai'] ? 'Rp ' . number_format($r['nilai'],0,',','.') : '—' ?></td>
        <td><?= esc($r['catatan'] ?: '—') ?></td>
        <td class="text-center">
            <?php if ($r['file_foto']): $ext = strtolower(pathinfo($r['file_foto'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
            <a href="<?= $uploadBase.$r['file_foto'] ?>" target="_blank">
                <img src="<?= $uploadBase.$r['file_foto'] ?>" style="height:40px;width:60px;object-fit:cover;border-radius:4px" alt="foto">
            </a>
            <?php else: ?>
            <a href="<?= $uploadBase.$r['file_foto'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark"></i></a>
            <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td class="text-center">
            <?php if ($r['file_terima']): $ext = strtolower(pathinfo($r['file_terima'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
            <a href="<?= $uploadBase.$r['file_terima'] ?>" target="_blank">
                <img src="<?= $uploadBase.$r['file_terima'] ?>" style="height:40px;width:60px;object-fit:cover;border-radius:4px" alt="terima">
            </a>
            <?php else: ?>
            <a href="<?= $uploadBase.$r['file_terima'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <?php if ($canEdit): ?>
        <td>
            <a href="<?= base_url('events/'.$event['id'].'/content/'.$item['id'].'/realisasi/'.$r['id'].'/delete') ?>"
               class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus realisasi ini?')">
                <i class="bi bi-trash"></i>
            </a>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>

    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-outline-primary" type="button"
            data-bs-toggle="collapse" data-bs-target="#formReal<?= $item['id'] ?>">
        <i class="bi bi-plus-lg me-1"></i> Input Realisasi
    </button>
    <div class="collapse mt-3" id="formReal<?= $item['id'] ?>">
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/content/'.$item['id'].'/realisasi/add') ?>"
          enctype="multipart/form-data" class="border rounded p-3 bg-light">
    <?= csrf_field() ?>
    <div class="row g-2 mb-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Tanggal</label>
            <input type="date" name="tanggal" class="form-control form-control-sm"
                   min="<?= $event['start_date'] ?>"
                   max="<?= date('Y-m-d', strtotime($event['start_date'] . ' +' . ($event['event_days'] - 1) . ' days')) ?>"
                   value="<?= $item['tanggal'] ?? '' ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Nilai Realisasi (Rp)</label>
            <input type="text" name="nilai" class="form-control form-control-sm currency-input" value="0">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Catatan</label>
            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Keterangan realisasi...">
        </div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-image me-1"></i>Foto Dokumentasi</label>
            <input type="file" name="file_foto" class="form-control form-control-sm" accept="image/*,.pdf">
            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-receipt me-1"></i>Bukti Tanda Terima</label>
            <input type="file" name="file_terima" class="form-control form-control-sm" accept="image/*,.pdf">
            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
        </div>
    </div>
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Realisasi</button>
    </form>
    </div>
    <?php endif; ?>
</div>
</div>
<?php } ?>

<!-- ===== SECTION: PROGRAM & AKTIVITAS ===== -->
<div class="d-flex align-items-center mb-3 anim-fade-up" style="animation-delay:.2s">
    <h6 class="fw-bold mb-0"><i class="bi bi-collection-play me-2 text-primary"></i>Program & Aktivitas</h6>
    <span class="badge bg-primary-subtle text-primary ms-2"><?= count($programs) ?></span>
</div>

<?php if (empty($programs)): ?>
<div class="card mb-4 anim-fade-up" style="animation-delay:.24s"><div class="card-body text-center py-4 text-muted">
    <i class="bi bi-collection opacity-25 display-5 d-block mb-2"></i>
    <p class="mb-0 small">Belum ada program atau aktivitas.</p>
</div></div>
<?php else: ?>
<?php foreach ($programs as $item): ?>
<?php renderItemCard($item, $realisasi[$item['id']] ?? [], $realisasi, $locations, $event, $canEdit); ?>
<?php endforeach; ?>
<?php endif; ?>

<!-- ===== SECTION: BIAYA PENUNJANG ===== -->
<div class="d-flex align-items-center mb-3 mt-2 anim-fade-up" id="biayaHeader">
    <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-warning"></i>Biaya Penunjang</h6>
    <span class="badge bg-warning-subtle text-warning ms-2"><?= count($biayaItems) ?></span>
</div>

<?php if (empty($biayaItems)): ?>
<div class="card mb-4 anim-fade-up" id="biayaEmptyCard"><div class="card-body text-center py-4 text-muted">
    <i class="bi bi-receipt opacity-25 display-5 d-block mb-2"></i>
    <p class="mb-0 small">Belum ada biaya penunjang.</p>
</div></div>
<?php else: ?>
<?php foreach ($biayaItems as $item): ?>
<?php renderItemCard($item, $realisasi[$item['id']] ?? [], $realisasi, $locations, $event, $canEdit); ?>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/content/add-item') ?>">
<?= csrf_field() ?>
<input type="hidden" name="tipe" id="addTipe" value="program">
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="addModalTitle">Tambah Program / Aktivitas</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Live Performance, Fee Talent, Hotel Talent">
    </div>
    <!-- Program-only fields -->
    <div id="addProgramFields">
        <div class="row g-2 mb-3">
            <div class="col-4">
                <label class="form-label small fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control"
                       min="<?= $event['start_date'] ?>"
                       max="<?= date('Y-m-d', strtotime($event['start_date'] . ' +' . ($event['event_days'] - 1) . ' days')) ?>">
            </div>
            <div class="col-4">
                <label class="form-label small fw-semibold">Mulai</label>
                <input type="time" name="waktu_mulai" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label small fw-semibold">Selesai</label>
                <input type="time" name="waktu_selesai" class="form-control">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Lokasi</label>
            <select name="lokasi" class="form-select">
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= esc($loc['nama']) ?>"><?= esc($loc['nama']) ?><?= isset($loc['mall']) && $event['mall'] === 'keduanya' ? ' (' . ucfirst($loc['mall']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold" id="addJenisLabel">Jenis</label>
            <select name="jenis" id="addJenis" class="form-select">
                <option value="">— Pilih —</option>
                <optgroup label="Program" class="opt-program">
                    <option value="Performance">Performance</option>
                    <option value="Live Music">Live Music</option>
                    <option value="DJ Set">DJ Set</option>
                    <option value="Fashion Show">Fashion Show</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Talk Show">Talk Show</option>
                    <option value="Demo / Tasting">Demo / Tasting</option>
                    <option value="Games / Lomba">Games / Lomba</option>
                    <option value="Activation">Activation</option>
                    <option value="Pameran">Pameran</option>
                    <option value="Seremonial">Seremonial</option>
                </optgroup>
                <optgroup label="Biaya" class="opt-biaya">
                    <option value="Fee Talent">Fee Talent</option>
                    <option value="Hotel">Hotel</option>
                    <option value="Transport">Transport</option>
                    <option value="Konsumsi">Konsumsi</option>
                    <option value="Sewa Alat">Sewa Alat</option>
                    <option value="Dekorasi">Dekorasi</option>
                    <option value="Percetakan">Percetakan</option>
                    <option value="Dokumentasi">Dokumentasi</option>
                </optgroup>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">PIC</label>
            <input type="text" name="pic" class="form-control" placeholder="Nama PIC">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Budget (Rp)</label>
        <input type="text" name="budget" class="form-control currency-input" value="0">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2" placeholder="Detail tambahan..."></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editItemForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="editModalTitle">Edit Item</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="eNama" class="form-control" required>
    </div>
    <div id="editProgramFields">
        <div class="row g-2 mb-3">
            <div class="col-4">
                <label class="form-label small fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" id="eTanggal" class="form-control"
                       min="<?= $event['start_date'] ?>"
                       max="<?= date('Y-m-d', strtotime($event['start_date'] . ' +' . ($event['event_days'] - 1) . ' days')) ?>">
            </div>
            <div class="col-4">
                <label class="form-label small fw-semibold">Mulai</label>
                <input type="time" name="waktu_mulai" id="eMulai" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label small fw-semibold">Selesai</label>
                <input type="time" name="waktu_selesai" id="eSelesai" class="form-control">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Lokasi</label>
            <select name="lokasi" id="eLokasi" class="form-select">
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= esc($loc['nama']) ?>"><?= esc($loc['nama']) ?><?= isset($loc['mall']) && $event['mall'] === 'keduanya' ? ' (' . ucfirst($loc['mall']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Jenis / Kategori</label>
            <select name="jenis" id="eJenis" class="form-select">
                <option value="">— Pilih —</option>
                <optgroup label="Program">
                    <option value="Performance">Performance</option>
                    <option value="Live Music">Live Music</option>
                    <option value="DJ Set">DJ Set</option>
                    <option value="Fashion Show">Fashion Show</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Talk Show">Talk Show</option>
                    <option value="Demo / Tasting">Demo / Tasting</option>
                    <option value="Games / Lomba">Games / Lomba</option>
                    <option value="Activation">Activation</option>
                    <option value="Pameran">Pameran</option>
                    <option value="Seremonial">Seremonial</option>
                </optgroup>
                <optgroup label="Biaya">
                    <option value="Fee Talent">Fee Talent</option>
                    <option value="Hotel">Hotel</option>
                    <option value="Transport">Transport</option>
                    <option value="Konsumsi">Konsumsi</option>
                    <option value="Sewa Alat">Sewa Alat</option>
                    <option value="Dekorasi">Dekorasi</option>
                    <option value="Percetakan">Percetakan</option>
                    <option value="Dokumentasi">Dokumentasi</option>
                </optgroup>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">PIC</label>
            <input type="text" name="pic" id="ePic" class="form-control">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Budget (Rp)</label>
        <input type="text" name="budget" id="eBudget" class="form-control currency-input">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="keterangan" id="eKeterangan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseInt(this.value.replace(/[^0-9]/g,'')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});

function setAddTipe(tipe) {
    document.getElementById('addTipe').value = tipe;
    const isProgram = tipe === 'program';
    document.getElementById('addModalTitle').textContent = isProgram ? 'Tambah Program / Aktivitas' : 'Tambah Biaya Penunjang';
    document.getElementById('addProgramFields').style.display = isProgram ? '' : 'none';
    // toggle optgroups
    document.querySelectorAll('#addJenis .opt-program').forEach(el => el.style.display = isProgram ? '' : 'none');
    document.querySelectorAll('#addJenis .opt-biaya').forEach(el => el.style.display = isProgram ? 'none' : '');
    document.getElementById('addJenis').value = '';
}

// Init add modal on first open
document.getElementById('addItemModal')?.addEventListener('show.bs.modal', function() {
    const tipe = document.getElementById('addTipe').value || 'program';
    setAddTipe(tipe);
});

document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        const isProgram = d.tipe !== 'biaya';
        document.getElementById('editItemForm').action = '<?= base_url('events/'.$event['id'].'/content/') ?>' + d.id + '/edit-item';
        document.getElementById('editModalTitle').textContent = isProgram ? 'Edit Program / Aktivitas' : 'Edit Biaya Penunjang';
        document.getElementById('editProgramFields').style.display = isProgram ? '' : 'none';
        document.getElementById('eNama').value       = d.nama;
        document.getElementById('eTanggal').value    = d.tanggal;
        document.getElementById('eMulai').value      = d.mulai;
        document.getElementById('eSelesai').value    = d.selesai;
        document.getElementById('eJenis').value      = d.jenis;
        document.getElementById('ePic').value        = d.pic;
        document.getElementById('eLokasi').value     = d.lokasi;
        document.getElementById('eBudget').value     = d.budget;
        document.getElementById('eKeterangan').value = d.keterangan;
        new bootstrap.Modal(document.getElementById('editItemModal')).show();
    });
});

// Stagger item cards
document.querySelectorAll('.item-card').forEach((card, i) => {
    card.style.animationDelay = (.24 + Math.min(i, 8) * .07) + 's';
});
const afterProg = .24 + Math.min(<?= count($programs) ?>, 8) * .07 + .06;
const biayaHeader = document.getElementById('biayaHeader');
if (biayaHeader) biayaHeader.style.animationDelay = afterProg + 's';
const biayaEmptyCard = document.getElementById('biayaEmptyCard');
if (biayaEmptyCard) biayaEmptyCard.style.animationDelay = (afterProg + .07) + 's';
</script>
<?= $this->endSection() ?>
