<?php
/**
 * Daftar program kerja tab Arsip / Dihapus (tabel sederhana, tanpa aksi update).
 * Param: $items, $scope ('archived'|'deleted'), $statusLabel,
 *        $showOrg (bool — tampilkan kolom divisi/dept, utk admin & deputy),
 *        $canRestore (bool — admin, tab dihapus), $canUnarchive (bool),
 *        $detailBase (url prefix halaman detail, tanpa trailing slash).
 */
$isDeleted = $scope === 'deleted';
?>
<?php if (empty($items)): ?>
<div class="text-center text-muted py-5">
    <i class="bi <?= $isDeleted ? 'bi-trash3' : 'bi-archive' ?> fs-1 d-block mb-2"></i>
    <?= $isDeleted ? 'Tidak ada program kerja yang dihapus.' : 'Arsip kosong. Program berstatus Selesai/Dibatalkan otomatis masuk ke sini setelah 30 hari.' ?>
</div>
<?php else: ?>
<?php if (! $isDeleted): ?>
<p class="text-muted mb-2" style="font-size:.72rem"><i class="bi bi-info-circle me-1"></i>Program berstatus Selesai/Dibatalkan otomatis diarsipkan setelah 30 hari sejak update terakhir, selain yang diarsipkan manual.</p>
<?php endif; ?>
<div class="card">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0" style="font-size:.78rem">
<thead>
<tr>
    <th>Program Kerja</th>
    <?php if (! empty($showOrg)): ?><th style="width:16%">Divisi / Dept</th><?php endif; ?>
    <th style="width:10%">Status Terakhir</th>
    <th style="width:13%">PIC</th>
    <th style="width:18%"><?= $isDeleted ? 'Dihapus' : 'Diarsipkan' ?></th>
    <th style="width:14%" class="text-end">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item):
    $st   = $item['latest_status'] ?? null;
    $info = $st ? ($statusLabel[$st] ?? null) : null;
?>
<tr>
    <td>
        <div class="fw-semibold"><?= esc($item['judul']) ?></div>
        <?php if (! empty($item['created_by_name'])): ?>
        <div class="text-muted" style="font-size:.68rem">Dibuat: <?= esc($item['created_by_name']) ?></div>
        <?php endif; ?>
    </td>
    <?php if (! empty($showOrg)): ?>
    <td style="font-size:.72rem">
        <?= esc($item['divisi_name'] ?? '—') ?><br>
        <span class="text-muted"><?= esc($item['dept_name'] ?? 'Program Level Divisi') ?></span>
    </td>
    <?php endif; ?>
    <td>
        <?php if ($info): ?>
        <span class="badge <?= $info['badge'] ?>" style="font-size:.65rem"><?= $info['label'] ?></span>
        <?php else: ?>
        <span class="badge bg-secondary" style="font-size:.65rem">Belum ada</span>
        <?php endif; ?>
    </td>
    <td><?= esc($item['pic_name'] ?? '—') ?></td>
    <td style="font-size:.72rem">
        <?php if ($isDeleted): ?>
            <?= ! empty($item['deleted_at']) ? date('d M Y H:i', strtotime($item['deleted_at'])) : '—' ?><br>
            <span class="text-muted">oleh <?= esc($item['deleted_by_name'] ?? '—') ?></span>
        <?php elseif (! empty($item['archived_at'])): ?>
            <?= date('d M Y H:i', strtotime($item['archived_at'])) ?><br>
            <span class="text-muted">oleh <?= esc($item['archived_by_name'] ?? '—') ?></span>
        <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem"><i class="bi bi-magic me-1"></i>Otomatis (&gt;<?= \App\Models\WorkInitiativeModel::AUTO_ARCHIVE_DAYS ?> hari selesai)</span>
        <?php endif; ?>
    </td>
    <td class="text-end">
        <?php if (! empty($detailBase)): ?>
        <a href="<?= $detailBase . '/' . $item['id'] . '/detail' ?>" class="btn btn-outline-info btn-sm py-0 px-1" style="font-size:.7rem" title="Riwayat">
            <i class="bi bi-clock-history"></i>
        </a>
        <?php endif; ?>
        <?php if (! $isDeleted && ! empty($canUnarchive) && ! empty($item['archived_at'])): ?>
        <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/unarchive') ?>" class="d-inline"
              onsubmit="return confirm('Kembalikan program ini ke daftar aktif?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-1" style="font-size:.7rem" title="Batal arsip">
                <i class="bi bi-arrow-counterclockwise"></i> Batal Arsip
            </button>
        </form>
        <?php endif; ?>
        <?php if ($isDeleted && ! empty($canRestore)): ?>
        <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/restore') ?>" class="d-inline"
              onsubmit="return confirm('Pulihkan program kerja ini ke daftar aktif?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-success btn-sm py-0 px-1" style="font-size:.7rem" title="Pulihkan">
                <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
            </button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
