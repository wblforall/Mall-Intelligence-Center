<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-robot me-2"></i>Pemantauan AI</h4>
        <small class="text-muted">Pemakaian Claude Code tim &mdash; <?= tgl_indo($tanggal) ?></small>
    </div>
    <a href="<?= base_url('ai-monitor/rubrik') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-award me-1"></i>Cara Penilaian Mutu Prompt</a>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 py-2 small" role="alert">
    <i class="bi bi-shield-check mt-1"></i>
    <div>Pemantauan ini diketahui tim. Kata sandi dan token otomatis disamarkan sebelum disimpan.</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Nama</th>
    <th>Perangkat</th>
    <th>Lapor terakhir</th>
    <th class="text-center">Sesi hari ini</th>
    <th class="text-center">Prompt hari ini</th>
    <th class="text-end">Token hari ini</th>
    <th class="text-center">7 hari</th>
</tr>
</thead>
<tbody>
<?php if (! $rows): ?>
<tr><td colspan="7" class="text-center text-muted py-5">
    <i class="bi bi-robot d-block fs-1 mb-2 opacity-25"></i>Belum ada data pemantauan
</td></tr>
<?php else: foreach ($rows as $r): ?>
<tr>
    <td>
        <a href="<?= base_url('ai-monitor/karyawan/' . $r['employee_id']) ?>" class="fw-medium text-decoration-none">
            <?= esc($r['nama']) ?>
        </a>
        <?php if (! empty($r['dept'])): ?>
        <span class="d-block small text-muted"><?= esc($r['dept']) ?></span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (! empty($r['device_label'])): ?>
        <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-laptop me-1"></i><?= esc($r['device_label']) ?></span>
        <?php else: ?>
        <span class="text-muted">&mdash;</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (! empty($r['lapor_at'])): ?>
        <span class="small"><?= tgl_indo($r['lapor_at'], true) ?></span>
        <?php else: ?>
        <span class="badge bg-secondary-subtle text-secondary">Belum pernah</span>
        <?php endif; ?>
    </td>
    <td class="text-center fw-semibold"><?= (int) $r['sesi_hari_ini'] > 0 ? number_format((int) $r['sesi_hari_ini']) : '—' ?></td>
    <td class="text-center"><?= (int) $r['prompt_hari_ini'] > 0 ? number_format((int) $r['prompt_hari_ini']) : '—' ?></td>
    <td class="text-end"><?= (int) $r['token_hari_ini'] > 0 ? number_format((int) $r['token_hari_ini']) : '—' ?></td>
    <td class="text-center small text-muted">
        <?= number_format((int) $r['sesi_7hari']) ?> sesi &middot; <?= number_format((int) $r['prompt_7hari']) ?> prompt
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<?= $this->endSection() ?>
