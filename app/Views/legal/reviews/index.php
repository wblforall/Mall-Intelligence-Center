<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','in_review'=>'In Review','revision'=>'Perlu Revisi','final'=>'Final','signed'=>'Signed'];
$statusBadge = ['draft'=>'secondary','in_review'=>'primary','revision'=>'warning','final'=>'success','signed'=>'success'];
?>
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">Review Kontrak</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Review Kontrak</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/reviews/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Review</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab==='all'?'active':'' ?>" href="?tab=all">Semua</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='action'?'active':'' ?>" href="?tab=action">Perlu Tindakan Saya</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='assigned'?'active':'' ?>" href="?tab=assigned">Di-assign ke Saya</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='mine'?'active':'' ?>" href="?tab=mine">Saya Buat</a></li>
    </ul>

    <div class="card"><div class="card-body p-0">
        <?php if (empty($reviews)): ?>
        <p class="text-muted text-center py-4 mb-0">Tidak ada review kontrak.</p>
        <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Judul</th><th>Tipe</th><th>Status</th><th>Versi</th><th>Reviewer</th><th>Terakhir Update</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $r): ?>
            <tr>
                <td><a href="<?= base_url('legal/reviews/'.$r['id']) ?>" class="fw-medium text-decoration-none"><?= esc($r['judul']) ?></a>
                    <?php if ($r['ext_link_active']): ?><span class="badge bg-info-subtle text-info ms-1 small"><i class="bi bi-link-45deg"></i>Link Aktif</span><?php endif; ?>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary"><?= $r['entity_type'] === 'standalone' ? 'Standalone' : ucfirst($r['entity_type']) ?></span></td>
                <td><span class="badge bg-<?= $statusBadge[$r['status']] ?>-subtle text-<?= $statusBadge[$r['status']] ?>"><?= $statusLabel[$r['status']] ?></span></td>
                <td class="text-muted small"><?= $r['versi_terkini'] ? 'v'.$r['versi_terkini'] : '—' ?></td>
                <td class="text-muted small"><?= esc($r['creator_name']) ?></td>
                <td class="text-muted small"><?= $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—' ?></td>
                <td><a href="<?= base_url('legal/reviews/'.$r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div></div>
</div>
<?= $this->endSection() ?>
