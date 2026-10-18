<?php helper('tanggal'); ?><?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$eventDates = [];
for ($i = 0; $i < $event['event_days']; $i++) {
    $d = date('Y-m-d', strtotime($event['start_date'] . " +{$i} days"));
    $eventDates[$d] = 'Hari ' . ($i + 1) . ' — ' . tgl_indo_pendek($d);
}
?>

<div class="page-evs">

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'content', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<!-- Header -->
<div class="ev-head">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="ev-iconbtn ev-back" title="Kembali ke Summary event" aria-label="Kembali ke Summary event"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title">Rundown</h1>
        <div class="ev-subtitle"><?= esc($event['name']) ?> · <span id="evSesiCount">0</span> sesi · <?= (int) $event['event_days'] ?> hari</div>
    </div>
    <div class="ev-head-act">
        <a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="ev-btn"><i class="bi bi-collection-play"></i><span class="d-none d-sm-inline">Content Event</span></a>
        <a href="<?= base_url('events/'.$event['id'].'/rundown/print') ?>" target="_blank" class="ev-btn"><i class="bi bi-printer"></i><span class="d-none d-sm-inline">Print / Save PDF</span></a>
        <?php if ($canEdit): ?>
        <button type="button" class="ev-btn ev-btn-primary" id="addRowBtn"><i class="bi bi-plus-lg"></i>Tambah Sesi</button>
        <?php endif; ?>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>

<div class="ev-day" id="evDays"></div>

<?php if ($canEdit): ?>
<div class="ev-note"><i class="bi bi-info-circle"></i><div class="min-w-0">Seret ikon <i class="bi bi-grip-vertical"></i> untuk mengubah urutan. Baris biru berasal dari <a href="<?= base_url('events/'.$event['id'].'/content') ?>">Content Event</a> dan diubah di sana. Klik <strong>Simpan Rundown</strong> untuk menyimpan.</div></div>
<?php endif; ?>

<div class="ev-panel">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/rundown/save') ?>">
<?= csrf_field() ?>
<div class="ev-tablewrap">
<table class="ev-rd" id="rundownTable">
<thead>
<tr>
    <?php if ($canEdit): ?><th style="width:34px"><span class="visually-hidden">Urutan</span></th><?php endif; ?>
    <th class="col-tgl">Tanggal</th>
    <th class="col-jam">Mulai</th>
    <th class="col-jam">Selesai</th>
    <th class="col-sesi">Sesi / Acara</th>
    <th class="col-desk">Deskripsi</th>
    <th class="col-pic">PIC</th>
    <th class="col-lok">Lokasi</th>
    <?php if ($canEdit): ?><th style="width:44px"><span class="visually-hidden">Hapus</span></th><?php endif; ?>
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
    <?php if ($canEdit): ?><td><button type="button" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad remove-row" title="Hapus sesi" aria-label="Hapus sesi"><i class="bi bi-trash"></i></button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($grouped as $hariKe => $rows): ?>
<?php foreach ($rows as $r):
    $fromContent = ! empty($r['content_item_id']);
?>
<tr class="<?= $fromContent ? 'is-content' : '' ?>">
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
        <span class="col-tgl small d-block"><?= array_key_exists($r['tanggal'], $eventDates) ? $eventDates[$r['tanggal']] : $r['tanggal'] ?></span>
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
        <span class="small fw-medium"><?= esc($r['sesi']) ?> <span class="ev-chip ev-chip-blue ms-1">Content</span></span>
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
        <span class="small text-muted"><?= esc($r['lokasi'] ?: '—') ?></span>
        <?php else: ?>
        <input type="text" name="lokasi[]" class="form-control form-control-sm" value="<?= esc($r['lokasi']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
        <?php endif; ?>
    </td>
    <?php if ($canEdit): ?>
    <td>
        <?php if (! $fromContent): ?>
        <button type="button" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad remove-row" title="Hapus sesi" aria-label="Hapus sesi"><i class="bi bi-trash"></i></button>
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
<div class="ev-panel-f" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap">
    <span class="ev-mute">Perubahan baru tersimpan setelah klik tombol ini.</span>
    <button type="submit" class="ev-btn ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan Rundown</button>
</div>
<?php endif; ?>
</form>
</div>

</div><!-- /page-evs -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?= base_url('js/events-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-summary.js') ?: 1 ?>"></script>
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
        <td><button type="button" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad remove-row" title="Hapus sesi" aria-label="Hapus sesi"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('rundownBody').insertAdjacentHTML('beforeend', row);
});

document.getElementById('rundownBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) e.target.closest('tr').remove();
});

Sortable.create(document.getElementById('rundownBody'), {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'sortable-ghost',
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
