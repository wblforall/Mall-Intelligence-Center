<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.ai-timeline { position: relative; }
.ai-entry { margin-bottom: .85rem; }
.ai-entry .ai-waktu { font-size: .7rem; color: var(--bs-secondary-color); }
.ai-bubble { border-radius: .6rem; padding: .7rem .9rem; font-size: .875rem; line-height: 1.55; }
.ai-bubble.prompt  { background: var(--bs-primary-bg-subtle); border: 1px solid var(--bs-primary-border-subtle); }
.ai-bubble.balasan { background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); }
.ai-bubble .ai-isi { white-space: normal; word-break: break-word; }
.ai-bubble .ai-isi.clamp { max-height: 320px; overflow-y: auto; }
.ai-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.ai-alat { font-size: .82rem; }
.ai-alat code { font-size: .8rem; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-chat-square-text me-2"></i>
            <?= $sesi['judul'] !== '' && $sesi['judul'] !== null ? esc($sesi['judul']) : '(tanpa judul)' ?>
        </h4>
        <small class="text-muted"><?= esc($sesi['nama']) ?></small>
    </div>
    <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card mb-3">
<div class="card-body py-3 d-flex flex-wrap gap-4">
    <div>
        <div class="small text-muted">Proyek</div>
        <div class="fw-medium">
            <?= ! empty($sesi['proyek']) ? esc($sesi['proyek']) : '—' ?>
            <?php if (! empty($sesi['git_branch'])): ?>
            <span class="badge bg-secondary-subtle text-secondary ms-1"><i class="bi bi-git me-1"></i><?= esc($sesi['git_branch']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="small text-muted">Model</div>
        <div><?php if (! empty($sesi['model'])): ?><span class="badge bg-primary-subtle text-primary"><?= esc($sesi['model']) ?></span><?php else: ?>&mdash;<?php endif; ?></div>
    </div>
    <div>
        <div class="small text-muted">Rentang waktu</div>
        <div class="fw-medium small"><?= tgl_indo($sesi['mulai_at'], true) ?> &ndash; <?= tgl_indo($sesi['terakhir_at'], true) ?></div>
    </div>
    <div>
        <div class="small text-muted">Aktivitas</div>
        <div class="fw-medium"><?= number_format((int) $sesi['jml_prompt']) ?> prompt &middot; <?= number_format((int) $sesi['jml_alat']) ?> alat</div>
    </div>
    <div>
        <div class="small text-muted">Token</div>
        <div class="fw-medium small">
            <i class="bi bi-arrow-down"></i> <?= number_format((int) $sesi['token_masuk']) ?>
            /
            <i class="bi bi-arrow-up"></i> <?= number_format((int) $sesi['token_keluar']) ?>
        </div>
    </div>
</div>
</div>

<?php if (! $entries): ?>
<div class="card">
<div class="card-body text-center text-muted py-5">
    <i class="bi bi-chat-square-dots d-block fs-1 mb-2 opacity-25"></i>Belum ada entri pada sesi ini
</div>
</div>
<?php else: ?>
<div class="ai-timeline">
<?php foreach ($entries as $e): ?>
    <?php if ($e['jenis'] === 'prompt'): ?>
    <div class="ai-entry">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-person text-primary"></i>
            <span class="ai-label text-primary">Prompt</span>
            <span class="ai-waktu ms-auto"><?= tgl_indo($e['waktu'], true) ?></span>
        </div>
        <div class="ai-bubble prompt">
            <div class="ai-isi"><?= nl2br(esc($e['isi'])) ?></div>
        </div>
    </div>
    <?php elseif ($e['jenis'] === 'balasan'): ?>
    <div class="ai-entry">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-robot text-success"></i>
            <span class="ai-label text-success">Claude</span>
            <span class="ai-waktu ms-auto"><?= tgl_indo($e['waktu'], true) ?></span>
        </div>
        <div class="ai-bubble balasan">
            <?php if (mb_strlen((string) $e['isi']) > 600): ?>
            <details>
                <summary class="small text-muted mb-1" style="cursor:pointer">Balasan panjang &mdash; klik untuk membuka</summary>
                <div class="ai-isi clamp mt-2"><?= nl2br(esc($e['isi'])) ?></div>
            </details>
            <?php else: ?>
            <div class="ai-isi"><?= nl2br(esc($e['isi'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="ai-entry ai-alat d-flex align-items-center gap-2">
        <i class="bi bi-tools text-muted"></i>
        <span class="fw-semibold"><?= esc($e['alat']) ?></span>
        <?php if (! empty($e['sasaran'])): ?>
        <span class="text-muted text-truncate"><?= esc($e['sasaran']) ?></span>
        <?php endif; ?>
        <span class="ai-waktu ms-auto flex-shrink-0"><?= tgl_indo($e['waktu'], true) ?></span>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
