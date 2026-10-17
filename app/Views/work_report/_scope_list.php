<?php
/**
 * Daftar program kerja tab Arsip / Dihapus (tabel sederhana, tanpa aksi update).
 * Param: $items, $scope ('archived'|'deleted'), $statusLabel,
 *        $showOrg (bool — tampilkan kolom divisi/dept, utk admin & deputy),
 *        $canRestore (bool — admin, tab dihapus), $canUnarchive (bool),
 *        $detailBase (url prefix halaman detail, tanpa trailing slash),
 *        $unread (array opsional — initiative_id => jumlah pesan belum dibaca).
 */
$isDeleted = $scope === 'deleted';
$unread    = $unread ?? [];
?>
<?php if (empty($items)): ?>
<div class="wr-empty">
    <i class="bi <?= $isDeleted ? 'bi-trash3' : 'bi-archive' ?>"></i>
    <h5><?= $isDeleted ? 'Tidak ada program kerja yang dihapus' : 'Arsip kosong' ?></h5>
    <p><?= $isDeleted ? 'Program yang dihapus akan muncul di sini dan bisa dipulihkan.' : 'Program berstatus Selesai/Dibatalkan otomatis masuk ke sini setelah 30 hari.' ?></p>
</div>
<?php else: ?>
<?php if (! $isDeleted): ?>
<p class="wr-hint wr-hint-info"><i class="bi bi-info-circle"></i>Program berstatus Selesai/Dibatalkan otomatis diarsipkan setelah 30 hari sejak update terakhir, selain yang diarsipkan manual.</p>
<?php endif; ?>
<div class="wr-card">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0 table-cardify wr-table">
<thead>
<tr>
    <th>Program Kerja</th>
    <?php if (! empty($showOrg)): ?><th style="width:16%">Divisi / Dept</th><?php endif; ?>
    <th style="width:10%">Status Terakhir</th>
    <th style="width:13%">PIC</th>
    <th style="width:18%"><?= $isDeleted ? 'Dihapus' : 'Diarsipkan' ?></th>
    <th style="width:16%" class="text-end">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item):
    $st   = $item['latest_status'] ?? null;
    $info = $st ? ($statusLabel[$st] ?? null) : null;
?>
<tr>
    <td class="cardify-title">
        <div class="fw-semibold">
            <?= esc($item['judul']) ?>
            <?php if (! empty($unread[$item['id']])): ?>
            <span class="wr-chip wr-chip-bad ms-1" title="Pesan belum dibaca"><?= $unread[$item['id']] ?> pesan baru</span>
            <?php endif; ?>
        </div>
        <?php if (! empty($item['created_by_name'])): ?>
        <div class="wr-mute small">Dibuat: <?= esc($item['created_by_name']) ?></div>
        <?php endif; ?>
    </td>
    <?php if (! empty($showOrg)): ?>
    <td class="small" data-label="Divisi / Dept">
        <?= esc($item['divisi_name'] ?? '—') ?><br>
        <span class="wr-mute"><?= esc($item['dept_name'] ?? \App\Models\WorkInitiativeModel::DIVISION_LEVEL_LABEL) ?></span>
    </td>
    <?php endif; ?>
    <td data-label="Status">
        <?php if ($info): ?><span class="wr-chip wr-st-<?= $st ?>"><?= $info['label'] ?></span>
        <?php else: ?><span class="wr-chip wr-chip-mute">Belum ada</span><?php endif; ?>
    </td>
    <td data-label="PIC"><?= esc($item['pic_name'] ?? '—') ?></td>
    <td class="small" data-label="<?= $isDeleted ? 'Dihapus' : 'Diarsipkan' ?>">
        <?php if ($isDeleted): ?>
            <?= ! empty($item['deleted_at']) ? date('d M Y H:i', strtotime($item['deleted_at'])) : '—' ?><br>
            <span class="wr-mute">oleh <?= esc($item['deleted_by_name'] ?? '—') ?></span>
        <?php elseif (! empty($item['archived_at'])): ?>
            <?= date('d M Y H:i', strtotime($item['archived_at'])) ?><br>
            <span class="wr-mute">oleh <?= esc($item['archived_by_name'] ?? '—') ?></span>
        <?php else: ?>
            <span class="wr-chip wr-chip-mute"><i class="bi bi-magic"></i>Otomatis (&gt;<?= \App\Models\WorkInitiativeModel::AUTO_ARCHIVE_DAYS ?> hari selesai)</span>
        <?php endif; ?>
    </td>
    <td class="text-end cardify-actions">
        <div class="wr-rowact">
        <?php if (! empty($detailBase)): ?>
        <a href="<?= $detailBase . '/' . $item['id'] . '/detail' ?>" class="wr-btn wr-btn-xs wr-btn-soft" title="Riwayat">
            <i class="bi bi-clock-history"></i>Riwayat
        </a>
        <?php endif; ?>
        <?php /* Tampil juga untuk item auto-arsip (archived_at kosong) — jalur pemulihan satu-satunya */ ?>
        <?php if (! $isDeleted && ! empty($canUnarchive)): ?>
        <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/unarchive') ?>" class="d-inline"
              onsubmit="return confirm('Kembalikan program ini ke daftar aktif?')">
            <?= csrf_field() ?>
            <button type="submit" class="wr-btn wr-btn-xs" title="Batal arsip"><i class="bi bi-arrow-counterclockwise"></i>Batal Arsip</button>
        </form>
        <?php endif; ?>
        <?php if ($isDeleted && ! empty($canRestore)): ?>
        <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/restore') ?>" class="d-inline"
              onsubmit="return confirm('Pulihkan program kerja ini ke daftar aktif?')">
            <?= csrf_field() ?>
            <button type="submit" class="wr-btn wr-btn-xs wr-btn-ok" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i>Pulihkan</button>
        </form>
        <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
