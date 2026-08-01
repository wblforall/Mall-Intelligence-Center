<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('kanban') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-person-check me-2"></i>Kartu Saya</h4>
        <small class="text-muted">Semua kartu yang ditugaskan ke Anda, lintas board — urut tenggat.</small>
    </div>
</div>

<?php if (! $cards): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="bi bi-check2-circle" style="font-size:2.2rem; opacity:.4"></i>
    <div class="mt-2 fw-semibold">Tidak ada kartu yang ditugaskan ke Anda.</div>
</div></div>
<?php else: ?>
<div class="card"><div class="card-body p-0">
<?php foreach ($cards as $c):
    $dueCls = '';
    if ($c['due_date']) {
        if ($c['due_done']) $dueCls = 'text-success';
        elseif (strtotime($c['due_date']) < time()) $dueCls = 'text-danger';
        else $dueCls = 'text-warning';
    }
?>
    <a href="<?= base_url('kanban/' . $c['bid']) ?>" class="d-flex align-items-center gap-3 px-3 py-2 border-bottom border-secondary-subtle text-decoration-none">
        <span class="d-inline-block rounded flex-shrink-0" style="width:10px;height:26px;background:<?= esc($c['board_color'] ?: '#e8415a') ?>"></span>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-truncate"><?= esc($c['judul']) ?></div>
            <div class="small text-muted"><?= esc($c['board_nama']) ?> · kolom <?= esc($c['list_nama']) ?></div>
        </div>
        <?php if ($c['due_date']): ?>
        <span class="small fw-semibold <?= $dueCls ?> flex-shrink-0">
            <i class="bi bi-clock me-1"></i><?= date('j M', strtotime($c['due_date'])) ?><?= $c['due_done'] ? ' ✓' : '' ?>
        </span>
        <?php endif; ?>
        <i class="bi bi-chevron-right text-muted flex-shrink-0"></i>
    </a>
<?php endforeach; ?>
</div></div>
<?php endif; ?>

<?= $this->endSection() ?>
