<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-check2-square me-2"></i>Kotak Persetujuan</h4>
        <small class="text-muted">Semua hal yang menunggu keputusan Anda — dari seluruh modul, dalam satu tempat.</small>
    </div>
</div>

<?php if (! $items): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="bi bi-check2-circle text-success" style="font-size:2.4rem"></i>
    <div class="mt-2 fw-semibold">Tidak ada yang menunggu keputusan Anda.</div>
    <div class="text-muted small mt-1">Semua pengajuan sudah ditindaklanjuti.</div>
</div></div>
<?php else: ?>

<!-- Ringkasan -->
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Menunggu Anda</div>
            <div class="fs-3 fw-bold lh-1 mt-1"><?= count($items) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted">Perlu segera</div>
            <div class="fs-3 fw-bold lh-1 mt-1 <?= $urgent > 0 ? 'text-danger' : '' ?>"><?= $urgent ?></div>
            <div class="text-muted" style="font-size:.7rem">menunggu &ge; 7 hari / giliran Anda</div>
        </div></div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100"><div class="card-body py-3">
            <div class="small text-muted mb-2">Sebaran modul</div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($perModul as $mod => $n): [$label, $icon] = $modules[$mod] ?? [$mod, 'bi-dot']; ?>
                <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi <?= $icon ?> me-1"></i><?= esc($label) ?> <b><?= $n ?></b></span>
                <?php endforeach; ?>
            </div>
        </div></div>
    </div>
</div>

<!-- Filter -->
<div class="d-flex gap-2 flex-wrap mb-3" id="filterModul">
    <button class="btn btn-sm btn-primary" data-mod="">Semua (<?= count($items) ?>)</button>
    <?php foreach ($perModul as $mod => $n): [$label, $icon] = $modules[$mod] ?? [$mod, 'bi-dot']; ?>
    <button class="btn btn-sm btn-outline-secondary" data-mod="<?= esc($mod) ?>"><i class="bi <?= $icon ?> me-1"></i><?= esc($label) ?> (<?= $n ?>)</button>
    <?php endforeach; ?>
</div>

<!-- Daftar -->
<div class="card"><div class="card-body p-0" id="daftarPersetujuan">
    <?php foreach ($items as $it): [$label, $icon, $warna] = $modules[$it['module']] ?? [$it['module'], 'bi-dot', 'gray'];
        $umur = \App\Libraries\ApprovalInbox::ageDays($it['since']); ?>
    <a href="<?= base_url($it['url']) ?>" class="ap-item d-flex align-items-start gap-3 px-3 py-3 text-decoration-none border-bottom border-secondary-subtle" data-mod="<?= esc($it['module']) ?>">
        <span class="ap-icon ap-<?= esc($warna) ?>"><i class="bi <?= $icon ?>"></i></span>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-truncate"><?= esc($it['title']) ?></div>
            <?php if ($it['subtitle']): ?><div class="small text-muted text-truncate"><?= esc($it['subtitle']) ?></div><?php endif; ?>
            <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:.68rem"><?= esc($label) ?></span>
                <?php if ($it['urgent']): ?><span class="badge bg-danger-subtle text-danger-emphasis" style="font-size:.68rem"><i class="bi bi-exclamation-circle me-1"></i>Perlu segera</span><?php endif; ?>
                <span class="text-muted" style="font-size:.7rem">
                    <?= $it['since'] ? ($umur === 0 ? 'hari ini' : 'menunggu ' . $umur . ' hari') : '—' ?>
                </span>
            </div>
        </div>
        <i class="bi bi-chevron-right text-muted mt-1"></i>
    </a>
    <?php endforeach; ?>
</div></div>
<div class="text-center text-muted small py-4 d-none" id="kosongFilter">Tidak ada item pada modul ini.</div>
<?php endif; ?>

<style>
.ap-item:hover { background: rgba(255,255,255,.03); }
.ap-icon { width:38px; height:38px; border-radius:11px; display:grid; place-items:center; flex:none; font-size:1rem; }
.ap-crimson { background: rgba(232,65,90,.18);  color:#f8829a; }
.ap-pink    { background: rgba(248,130,154,.16);color:#f8829a; }
.ap-blue    { background: rgba(59,130,246,.16); color:#93c5fd; }
.ap-cyan    { background: rgba(34,211,238,.16); color:#67e8f9; }
.ap-orange  { background: rgba(249,115,22,.18); color:#fdba74; }
.ap-green   { background: rgba(16,185,129,.16); color:#6ee7b7; }
.ap-gold    { background: rgba(245,158,11,.16); color:#fbbf24; }
.ap-gray    { background: rgba(255,255,255,.06);color:rgba(180,210,255,.6); }
</style>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    const bar = document.getElementById('filterModul');
    if (!bar) return;
    bar.addEventListener('click', e => {
        const b = e.target.closest('button[data-mod]');
        if (!b) return;
        bar.querySelectorAll('button').forEach(x => { x.classList.remove('btn-primary'); x.classList.add('btn-outline-secondary'); });
        b.classList.remove('btn-outline-secondary'); b.classList.add('btn-primary');
        const mod = b.dataset.mod;
        let tampil = 0;
        document.querySelectorAll('.ap-item').forEach(it => {
            const ok = !mod || it.dataset.mod === mod;
            it.classList.toggle('d-none', !ok);
            if (ok) tampil++;
        });
        document.getElementById('kosongFilter').classList.toggle('d-none', tampil > 0);
    });
})();
</script>
<?= $this->endSection() ?>
