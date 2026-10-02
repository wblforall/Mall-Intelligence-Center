<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i><?= esc($emp['nama']) ?></h4>
        <?php if (! empty($emp['dept'])): ?>
        <small class="text-muted"><?= esc($emp['dept']) ?></small>
        <?php endif; ?>
    </div>
    <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?= $this->include('ai_monitor/_periode', ['printScope' => '&employee_id=' . (int) $scope_id]) ?>

<?= $this->include('ai_monitor/_analisa_panel') ?>

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
        <?php $sub = $s['ringkasan'] ?? null; if (empty($sub)) { $sub = $s['klasifikasi_tema'] ?? null; } ?>
        <?php if (! empty($sub)): ?>
        <div class="small text-muted text-truncate" style="max-width:32rem" title="<?= esc($sub) ?>"><?= esc(mb_strimwidth($sub, 0, 90, '…')) ?></div>
        <?php endif; ?>
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

<?= $this->section('scripts') ?>
<?= $this->include('ai_monitor/_analisa_js') ?>
<?= $this->endSection() ?>
