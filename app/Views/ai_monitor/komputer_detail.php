<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-laptop me-2"></i><?= esc($dev['label']) ?></h4>
        <small class="text-muted">
            <?php if ($dev['pemilik'] !== '' && $dev['pemilik'] !== '—'): ?>
            <i class="bi bi-person me-1"></i><?= esc($dev['pemilik']) ?><?= ! empty($dev['dept']) ? ' · ' . esc($dev['dept']) : '' ?>
            <?php else: ?>
            <span class="text-muted">Belum ditautkan ke karyawan</span>
            <?php endif; ?>
            <?php if (! empty($dev['host_terakhir'])): ?>
            &middot; <i class="bi bi-pc-display me-1"></i><?= esc($dev['host_terakhir']) ?>
            <?php endif; ?>
        </small>
    </div>
    <a href="<?= base_url('ai-monitor/komputer') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card mb-3">
<div class="card-body py-2">
    <form method="GET" action="" class="row row-cols-auto g-2 align-items-end">
        <div class="col">
            <label class="form-label small mb-1 text-muted">Dari</label>
            <input type="date" name="dari" value="<?= esc($filter['dari']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col">
            <label class="form-label small mb-1 text-muted">Sampai</label>
            <input type="date" name="sampai" value="<?= esc($filter['sampai']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
        </div>
    </form>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Judul</th>
    <th>Proyek</th>
    <th>Model</th>
    <th>Mulai</th>
    <th class="text-center">Aktivitas</th>
    <th class="text-end">Token</th>
</tr>
</thead>
<tbody>
<?php if (! $sessions): ?>
<tr><td colspan="6" class="text-center text-muted py-5">
    <i class="bi bi-chat-square-text d-block fs-1 mb-2 opacity-25"></i>Belum ada sesi pada rentang ini
</td></tr>
<?php else: foreach ($sessions as $s): ?>
<tr>
    <td>
        <a href="<?= base_url('ai-monitor/sesi/' . $s['id']) ?>" class="fw-medium text-decoration-none">
            <?= $s['judul'] !== '' && $s['judul'] !== null ? esc($s['judul']) : '(tanpa judul)' ?>
        </a>
    </td>
    <td>
        <?php if (! empty($s['proyek'])): ?>
        <span><?= esc($s['proyek']) ?></span>
        <?php else: ?>
        <span class="text-muted">&mdash;</span>
        <?php endif; ?>
        <?php if (! empty($s['git_branch'])): ?>
        <span class="badge bg-secondary-subtle text-secondary ms-1"><i class="bi bi-git me-1"></i><?= esc($s['git_branch']) ?></span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (! empty($s['model'])): ?>
        <span class="badge bg-primary-subtle text-primary"><?= esc($s['model']) ?></span>
        <?php else: ?>
        <span class="text-muted">&mdash;</span>
        <?php endif; ?>
    </td>
    <td class="small"><?= tgl_indo_hari($s['mulai_at']) ?></td>
    <td class="text-center small">
        <?= number_format((int) $s['jml_prompt']) ?> prompt &middot; <?= number_format((int) $s['jml_alat']) ?> alat
    </td>
    <td class="text-end small">
        <span title="Token masuk"><i class="bi bi-arrow-down"></i> <?= number_format((int) $s['token_masuk']) ?></span>
        /
        <span title="Token keluar"><i class="bi bi-arrow-up"></i> <?= number_format((int) $s['token_keluar']) ?></span>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<?= $this->endSection() ?>
