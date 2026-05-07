<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Traffic dari Excel</h4>
        <small class="text-muted">Upload file checklist harian (.xlsx)</small>
    </div>
    <a href="<?= base_url('traffic') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if (! $preview && ! $bulkItems): /* ── UPLOAD FORM ── */ ?>

<div class="card mb-4" style="max-width:540px">
<div class="card-body">
    <form method="POST" action="<?= base_url('traffic/import') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Mall</label>
            <select name="mall" class="form-select">
                <option value="ewalk">eWalk</option>
                <option value="pentacity">Pentacity</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">File Excel (.xlsx)</label>
            <input type="file" name="excel_file[]" class="form-control" accept=".xlsx,.xls" multiple required>
            <div class="form-text">Bisa pilih lebih dari 1 file sekaligus. Format: Checklist Harian Jumlah Pengunjung.</div>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-2"></i>Baca & Preview
        </button>
    </form>
</div>
</div>

<!-- Mapping Info — Tabs -->
<div style="max-width:540px">
<ul class="nav nav-tabs" id="mappingTabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mapEwalk">eWalk</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mapPentacity">Pentacity</button></li>
</ul>
<div class="tab-content border border-top-0 rounded-bottom">

<div class="tab-pane fade show active p-0" id="mapEwalk">
<table class="table table-sm mb-0">
<thead class="table-light"><tr><th>Nama di Excel</th><th>Nama di Sistem</th></tr></thead>
<tbody>
<?php
$mappingEwalk = [
    'Pintu Utama'                                => 'GF Lobby Utama (Utara)',
    'Pintu GF Timur'                             => 'GF Lobby Timur',
    'Pintu GF Barat'                             => 'GF Lobby Barat',
    'Pintu GF Selatan'                           => 'GF Lobby Selatan',
    'Pintu LG Timur'                             => 'LG lobby Timur',
    'Pintu LG Barat'                             => 'LG Lobby Barat',
    'Pintu UG Funstation / Lantai UG Funstation' => 'UG Bridge Funstation 1',
    'Pintu Lt. 1 XX1 / Lantai 1 XX1'            => 'FF Bridge XXI 1',
];
foreach ($mappingEwalk as $excel => $sys):
?>
<tr>
    <td class="small text-muted"><?= $excel ?></td>
    <td class="small fw-medium"><?= $sys ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade p-0" id="mapPentacity">
<table class="table table-sm mb-0">
<thead class="table-light"><tr><th>Nama di Excel</th><th>Nama di Sistem</th></tr></thead>
<tbody>
<?php
$mappingPenta = [
    'Pintu Utama'              => 'GF Lobby Utama',
    'Pintu GF Flying Tiger'    => 'GF Lobby Flying Tiger',
    'Pintu GF Beach Gate'      => 'GF Lobby Beach Gate',
    'Pintu GF Pentacity Hotel' => 'GF Pentacity hotel',
    'Pintu H&M'                => 'GF H&M',
    'Pintu GF Travelator'      => 'GF Travelator',
    'Pintu LG Solaria'         => 'LG Lobby Solaria',
    'Pintu LG KFC'             => 'LG Lobby KFC',
    'Pintu LG Miniso'          => 'LG Lobby Miniso',
    'Pintu LG Hypermart'       => 'LG Lobby Hypermart',
    'Pintu FF Lift Aquaboom'   => 'FF Lift Aquaboom',
    'Pintu P3 Otomatis'        => 'P3 Pintu otomatis',
    'Pintu P3 Kidzoona'        => 'P3 Kidzoona',
    'Pintu P4 Masjid'          => 'P4 Masjid',
    'Pintu P4 Mezanin'         => 'P4 mezzanine',
    'Pintu P5 Mezanin'         => 'P5 Mezzanine',
    'Pintu P5 Office'          => 'P5 Office',
    'PSV "People Count UG" col D (IN Penta 1)'   => 'UG Bridge Funstation 1',
    'PSV "People Count UG" col E (IN eWalk 1)'   => 'UG Bridge eWalk 1',
    'PSV "People Count UG" col F (IN Penta 2)'   => 'UG Bridge Funstation 2',
    'PSV "People Count UG" col G (IN eWalk 2)'   => 'UG Bridge eWalk 2',
    'PSV "People Count FF XXI" col D (IN Penta 1)' => 'FF Bridge XXI 1',
    'PSV "People Count FF XXI" col E (IN eWalk 1)' => 'FF Bridge eWalk 1',
    'PSV "People Count FF XXI" col F (IN Penta 2)' => 'FF Bridge XXI 2',
    'PSV "People Count FF XXI" col G (IN eWalk 2)' => 'FF Bridge eWalk 2',
];
foreach ($mappingPenta as $excel => $sys):
?>
<tr>
    <td class="small text-muted"><?= $excel ?></td>
    <td class="small fw-medium"><?= $sys ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div><!-- tab-content -->
</div>

<!-- Jam Conversion Note -->
<div class="alert alert-info mt-3 small" style="max-width:540px">
    <i class="bi bi-info-circle me-1"></i>
    Format jam Excel <code>"10.00 - 11.00"</code> dikonversi ke jam <code>10</code> (angka awal).
    Baris subtotal (TOTAL 12:00–15:00, TOTAL KESELURUHAN, dst) otomatis dilewati.
</div>

<?php elseif ($bulkItems): /* ── BULK PREVIEW ── */ ?>

<?php if (! empty($bulkErrors)): ?>
<div class="alert alert-warning small mb-3">
    <i class="bi bi-exclamation-triangle me-1"></i><strong>Beberapa file gagal dibaca:</strong><br>
    <?= implode('<br>', array_map('esc', $bulkErrors)) ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center gap-3 mb-3">
    <div class="card text-center px-4 py-3">
        <div class="small text-muted">Mall</div>
        <div class="fw-bold text-primary"><?= strtoupper(esc($mall)) ?></div>
    </div>
    <div class="card text-center px-4 py-3">
        <div class="small text-muted">Jumlah File</div>
        <div class="fw-bold"><?= count($bulkItems) ?> file</div>
    </div>
    <div class="card text-center px-4 py-3">
        <div class="small text-muted">Total Pengunjung</div>
        <div class="fw-bold text-success"><?= number_format(array_sum(array_column($bulkItems, 'totalVisitor'))) ?></div>
    </div>
</div>

<form method="POST" action="<?= base_url('traffic/import/bulk-save') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="mall" value="<?= esc($mall) ?>">

    <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Preview Bulk Import</h6>
        <span class="small text-muted"><?= count($bulkItems) ?> file siap diproses</span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
    <thead class="table-light">
    <tr>
        <th>#</th>
        <th>Nama File</th>
        <th>Tanggal Terdeteksi</th>
        <th class="text-end"><?= strtoupper(esc($mall)) ?></th>
        <?php if ($mall === 'pentacity'): ?>
        <th class="text-end text-primary">eWalk UG/FF</th>
        <?php endif; ?>
        <th class="text-center">Jam Terisi</th>
        <th class="text-center">Pintu</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($bulkItems as $i => $item): ?>
    <tr>
        <td class="text-muted small"><?= $i + 1 ?></td>
        <td class="small" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($item['origName']) ?>">
            <?= esc($item['origName']) ?>
        </td>
        <td>
            <input type="hidden" name="tmp_files[]" value="<?= esc($item['tmpPath']) ?>">
            <input type="date" name="tanggals[]" class="form-control form-control-sm"
                   value="<?= esc($item['tanggal'] ?? '') ?>"
                   style="width:150px"
                   <?= $item['tanggal'] ? '' : 'required' ?>>
            <?php if (! $item['tanggal']): ?>
            <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle"></i> Isi manual</div>
            <?php endif; ?>
        </td>
        <td class="text-end fw-semibold text-success"><?= number_format($item['totalVisitor']) ?></td>
        <?php if ($mall === 'pentacity'): ?>
        <td class="text-end small text-primary"><?= $item['totalEwalk'] > 0 ? number_format($item['totalEwalk']) : '—' ?></td>
        <?php endif; ?>
        <td class="text-center"><?= $item['jamCount'] ?></td>
        <td class="text-center"><?= $item['doorCount'] ?></td>
        <td>
            <?php if (! empty($item['warnings'])): ?>
            <span class="badge bg-warning text-dark" title="<?= esc(implode('; ', $item['warnings'])) ?>">
                <i class="bi bi-exclamation-triangle"></i> <?= count($item['warnings']) ?> peringatan
            </span>
            <?php else: ?>
            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> OK</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4"
                onclick="return confirm('Simpan <?= count($bulkItems) ?> file sekaligus? Data yang sudah ada untuk tanggal tersebut akan diganti.')">
            <i class="bi bi-check-circle me-2"></i>Simpan Semua (<?= count($bulkItems) ?> file)
        </button>
        <a href="<?= base_url('traffic/import') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x me-1"></i>Batal
        </a>
    </div>
</form>

<?php else: /* ── SINGLE FILE PREVIEW ── */ ?>

<div class="row g-3 mb-3">
    <div class="col-auto">
        <div class="card text-center px-4 py-3">
            <div class="small text-muted">Mall</div>
            <div class="fw-bold text-primary"><?= strtoupper(esc($preview['mall'])) ?></div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card text-center px-4 py-3">
            <div class="small text-muted">Tanggal Terdeteksi</div>
            <div class="fw-bold"><?= $preview['tanggal'] ? date('d M Y', strtotime($preview['tanggal'])) : '<span class="text-danger">Tidak terdeteksi</span>' ?></div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card text-center px-4 py-3">
            <div class="small text-muted">Total Pengunjung</div>
            <div class="fw-bold text-success"><?= number_format($preview['totalVisitor']) ?></div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card text-center px-4 py-3">
            <div class="small text-muted">Jam Terisi</div>
            <div class="fw-bold"><?= count($preview['rows']) ?> jam</div>
        </div>
    </div>
    <?php if (! empty($preview['ewalkRows'])): ?>
    <div class="col-auto">
        <div class="card text-center px-4 py-3 border-primary">
            <div class="small text-primary fw-semibold">eWalk UG/FF</div>
            <div class="fw-bold text-primary"><?= number_format($preview['totalEwalk']) ?></div>
            <div class="small text-muted">akan di-override</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (! empty($preview['warnings'])): ?>
<div class="alert alert-warning small mb-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <?= implode('<br>', array_map('esc', $preview['warnings'])) ?>
</div>
<?php endif; ?>

<!-- Preview Table -->
<div class="card mb-4">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Preview Data</h6>
    <span class="small text-muted"><?= count($preview['rows']) ?> baris × <?= count($preview['colToDoor']) ?> pintu</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead class="table-light">
<tr>
    <th>Jam</th>
    <?php foreach (array_values($preview['colToDoor']) as $door): ?>
    <th class="text-end small"><?= esc($door) ?></th>
    <?php endforeach; ?>
    <th class="text-end fw-semibold">Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($preview['rows'] as $row): ?>
<tr>
    <td class="fw-medium"><?= $row['jam'] . '-' . ($row['jam'] + 1) ?></td>
    <?php foreach (array_values($preview['colToDoor']) as $door): ?>
    <td class="text-end small"><?= number_format($row['doors'][$door] ?? 0) ?></td>
    <?php endforeach; ?>
    <td class="text-end fw-semibold"><?= number_format($row['total']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <td class="fw-semibold">Total</td>
    <?php
    $doorTotals = [];
    foreach ($preview['rows'] as $row) {
        foreach ($row['doors'] as $door => $val) {
            $doorTotals[$door] = ($doorTotals[$door] ?? 0) + $val;
        }
    }
    foreach (array_values($preview['colToDoor']) as $door):
    ?>
    <td class="text-end fw-semibold"><?= number_format($doorTotals[$door] ?? 0) ?></td>
    <?php endforeach; ?>
    <td class="text-end fw-bold text-success"><?= number_format($preview['totalVisitor']) ?></td>
</tr>
</tfoot>
</table>
</div>
</div>
</div>

<!-- Confirm Form -->
<form method="POST" action="<?= base_url('traffic/import/save') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="mall"     value="<?= esc($preview['mall']) ?>">
    <input type="hidden" name="tmp_file" value="<?= esc($tmpFile) ?>">

    <div class="d-flex align-items-end gap-3 mb-3">
        <div>
            <label class="form-label small fw-semibold mb-1">Tanggal</label>
            <input type="date" name="tanggal" class="form-control form-control-sm"
                   value="<?= $preview['tanggal'] ?? '' ?>" required>
            <div class="form-text">Koreksi jika tanggal terdeteksi salah</div>
        </div>
        <button type="submit" class="btn btn-success px-4" onclick="return confirm('Simpan data ini? Data yang sudah ada untuk tanggal ini akan diganti.')">
            <i class="bi bi-check-circle me-2"></i>Simpan ke Database
        </button>
        <a href="<?= base_url('traffic/import') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x me-1"></i>Batal
        </a>
    </div>
</form>

<?php endif; ?>

<?= $this->endSection() ?>
