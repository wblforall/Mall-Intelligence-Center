<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-pc-display me-2"></i>Rekap per Komputer</h4>
        <small class="text-muted">Pemakaian Claude Code per laptop &mdash; <?= tgl_indo($tanggal) ?></small>
    </div>
    <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-people me-1"></i>Rekap per Karyawan
    </a>
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
    <th>Komputer</th>
    <th>Pemilik</th>
    <th class="text-center">Status</th>
    <th>Lapor terakhir</th>
    <th class="text-center">Sesi hari ini</th>
    <th class="text-center">Prompt hari ini</th>
    <th class="text-end">Token hari ini</th>
    <th class="text-center">7 hari</th>
</tr>
</thead>
<tbody>
<?php if (! $rows): ?>
<tr><td colspan="8" class="text-center text-muted py-5">
    <i class="bi bi-pc-display d-block fs-1 mb-2 opacity-25"></i>Belum ada perangkat terdaftar
</td></tr>
<?php else: foreach ($rows as $r): ?>
<tr>
    <td>
        <a href="<?= base_url('ai-monitor/komputer/' . (int) $r['device_id']) ?>" class="fw-medium text-decoration-none">
            <i class="bi bi-laptop me-1"></i><?= esc($r['label']) ?>
        </a>
        <?php if (! empty($r['host_terakhir'])): ?>
        <span class="d-block small text-muted"><i class="bi bi-pc-display me-1"></i><?= esc($r['host_terakhir']) ?></span>
        <?php endif; ?>
    </td>
    <td>
        <?= esc($r['pemilik']) ?>
        <?php if (! empty($r['dept'])): ?>
        <span class="d-block small text-muted"><?= esc($r['dept']) ?></span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php switch ($r['status']):
            case 'aktif': ?>
            <span class="badge bg-success">Aktif</span>
            <?php break; case 'pending': ?>
            <span class="badge bg-warning text-dark">Menunggu persetujuan</span>
            <?php break; case 'diblokir': ?>
            <span class="badge bg-danger">Diblokir</span>
            <?php break; default: ?>
            <span class="badge bg-secondary">Dinonaktifkan</span>
        <?php endswitch; ?>
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
