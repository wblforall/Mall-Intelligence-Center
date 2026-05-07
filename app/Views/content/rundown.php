<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$eventDates = [];
for ($i = 0; $i < $event['event_days']; $i++) {
    $d = date('Y-m-d', strtotime($event['start_date'] . " +{$i} days"));
    $eventDates[$d] = 'Hari ' . ($i + 1) . ' — ' . date('D, d M Y', strtotime($d));
}
?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'content', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Rundown</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-outline-primary ms-auto" id="addRowBtn">
        <i class="bi bi-plus-lg me-1"></i> Tambah Sesi
    </button>
    <?php else: ?>
    <div class="ms-auto"></div>
    <?php endif; ?>
    <a href="<?= base_url('events/'.$event['id'].'/rundown/print') ?>" target="_blank"
       class="btn btn-sm btn-outline-secondary <?= $canEdit ? '' : 'ms-auto' ?>">
        <i class="bi bi-printer me-1"></i> Print / Save PDF
    </a>
</div>

<div class="card">
<div class="card-body p-0">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/rundown/save') ?>">
<?= csrf_field() ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0" id="rundownTable">
<thead>
<tr>
    <?php if ($canEdit): ?><th style="width:28px"></th><?php endif; ?>
    <th style="width:190px">Tanggal</th>
    <th style="width:90px">Mulai</th>
    <th style="width:90px">Selesai</th>
    <th>Sesi / Acara</th>
    <th>Deskripsi</th>
    <th style="width:110px">PIC</th>
    <th style="width:110px">Lokasi</th>
    <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
</tr>
</thead>
<tbody id="rundownBody">
<?php if (empty($grouped)): ?>
<?php foreach ($eventDates as $dateVal => $dateLabel): ?>
<tr>
    <?php if ($canEdit): ?><td class="drag-handle text-muted text-center" style="cursor:grab"><i class="bi bi-grip-vertical"></i></td><?php endif; ?>
    <td>
        <select name="tanggal[]" class="form-select form-select-sm" <?= !$canEdit ? 'disabled' : '' ?>>
            <?php foreach ($eventDates as $dv => $dl): ?>
            <option value="<?= $dv ?>" <?= $dv === $dateVal ? 'selected' : '' ?>><?= $dl ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td><input type="time" name="waktu_mulai[]" class="form-control form-control-sm" <?= !$canEdit ? 'readonly' : '' ?>></td>
    <td><input type="time" name="waktu_selesai[]" class="form-control form-control-sm" <?= !$canEdit ? 'readonly' : '' ?>></td>
    <td><input type="text" name="sesi[]" class="form-control form-control-sm" placeholder="Opening, Performance, dll" <?= !$canEdit ? 'readonly' : '' ?>></td>
    <td><input type="text" name="deskripsi[]" class="form-control form-control-sm" placeholder="Detail singkat" <?= !$canEdit ? 'readonly' : '' ?>></td>
    <td><input type="text" name="pic[]" class="form-control form-control-sm" placeholder="Nama PIC" <?= !$canEdit ? 'readonly' : '' ?>></td>
    <td><input type="text" name="lokasi[]" class="form-control form-control-sm" placeholder="Stage, Lobby..." <?= !$canEdit ? 'readonly' : '' ?>></td>
    <?php if ($canEdit): ?><td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($grouped as $hariKe => $rows): ?>
<?php foreach ($rows as $r):
    $fromContent = ! empty($r['content_item_id']);
?>
<tr class="<?= $fromContent ? 'table-primary' : '' ?>">
    <?php if ($canEdit): ?>
    <td class="text-muted text-center" style="cursor:<?= $fromContent ? 'default' : 'grab' ?>">
        <?php if ($fromContent): ?>
        <i class="bi bi-link-45deg text-primary" title="Dari Content Event"></i>
        <?php else: ?>
        <i class="bi bi-grip-vertical drag-handle"></i>
        <?php endif; ?>
    </td>
    <?php endif; ?>
    <td>
        <?php if ($fromContent): ?>
        <span class="form-control-sm d-block"><?= array_key_exists($r['tanggal'], $eventDates) ? $eventDates[$r['tanggal']] : $r['tanggal'] ?></span>
        <?php else: ?>
        <select name="tanggal[]" class="form-select form-select-sm" <?= !$canEdit ? 'disabled' : '' ?>>
            <?php foreach ($eventDates as $dv => $dl): ?>
            <option value="<?= $dv ?>" <?= $r['tanggal'] === $dv ? 'selected' : '' ?>><?= $dl ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small"><?= $r['waktu_mulai'] ? date('H:i', strtotime($r['waktu_mulai'])) : '—' ?></span>
        <?php else: ?>
        <input type="time" name="waktu_mulai[]" class="form-control form-control-sm" value="<?= $r['waktu_mulai'] ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small"><?= $r['waktu_selesai'] ? date('H:i', strtotime($r['waktu_selesai'])) : '—' ?></span>
        <?php else: ?>
        <input type="time" name="waktu_selesai[]" class="form-control form-control-sm" value="<?= $r['waktu_selesai'] ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small fw-medium"><?= esc($r['sesi']) ?> <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem">Content</span></span>
        <?php else: ?>
        <input type="text" name="sesi[]" class="form-control form-control-sm" value="<?= esc($r['sesi']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small text-muted"><?= esc($r['deskripsi'] ?: '—') ?></span>
        <?php else: ?>
        <input type="text" name="deskripsi[]" class="form-control form-control-sm" value="<?= esc($r['deskripsi']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small text-muted"><?= esc($r['pic'] ?: '—') ?></span>
        <?php else: ?>
        <input type="text" name="pic[]" class="form-control form-control-sm" value="<?= esc($r['pic']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($fromContent): ?>
        <span class="small text-muted">—</span>
        <?php else: ?>
        <input type="text" name="lokasi[]" class="form-control form-control-sm" value="<?= esc($r['lokasi']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <?php if ($canEdit): ?>
    <td>
        <?php if (! $fromContent): ?>
        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<?php if ($canEdit): ?>
<div class="p-3 border-top">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Simpan Rundown
    </button>
</div>
<?php endif; ?>
</form>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
<?php if ($canEdit): ?>
const dateOptions = <?= json_encode(array_map(
    fn($dv, $dl) => ['value' => $dv, 'label' => $dl],
    array_keys($eventDates),
    array_values($eventDates)
), JSON_UNESCAPED_UNICODE) ?>;

function buildDateSelect(selected = null) {
    const first = selected ?? dateOptions[0]?.value;
    return '<select name="tanggal[]" class="form-select form-select-sm">'
        + dateOptions.map(o => `<option value="${o.value}"${o.value === first ? ' selected' : ''}>${o.label}</option>`).join('')
        + '</select>';
}

document.getElementById('addRowBtn').addEventListener('click', function() {
    const row = `<tr>
        <td class="drag-handle text-muted text-center" style="cursor:grab"><i class="bi bi-grip-vertical"></i></td>
        <td>${buildDateSelect()}</td>
        <td><input type="time" name="waktu_mulai[]" class="form-control form-control-sm"></td>
        <td><input type="time" name="waktu_selesai[]" class="form-control form-control-sm"></td>
        <td><input type="text" name="sesi[]" class="form-control form-control-sm" placeholder="Opening, Performance, dll"></td>
        <td><input type="text" name="deskripsi[]" class="form-control form-control-sm" placeholder="Detail singkat"></td>
        <td><input type="text" name="pic[]" class="form-control form-control-sm" placeholder="Nama PIC"></td>
        <td><input type="text" name="lokasi[]" class="form-control form-control-sm" placeholder="Stage, Lobby..."></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('rundownBody').insertAdjacentHTML('beforeend', row);
});

document.getElementById('rundownBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) e.target.closest('tr').remove();
});

Sortable.create(document.getElementById('rundownBody'), {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'table-active',
    chosenClass: 'table-primary',
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
