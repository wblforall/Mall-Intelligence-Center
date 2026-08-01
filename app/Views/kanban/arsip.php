<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('kanban/' . $board['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-archive me-2"></i>Arsip — <?= esc($board['nama']) ?></h5>
        <small class="text-muted">Kolom & kartu terarsip. Pulihkan untuk mengembalikan ke papan.</small>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header py-2"><h6 class="mb-0 fw-semibold">Kolom terarsip (<?= count($lists) ?>)</h6></div>
            <div class="card-body p-0">
                <?php if (! $lists): ?><div class="p-3 small text-muted">Tidak ada.</div><?php endif; ?>
                <?php foreach ($lists as $l): ?>
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom border-secondary-subtle">
                    <div class="flex-grow-1 fw-semibold small"><?= esc($l['nama']) ?></div>
                    <?php if ($canEdit): ?>
                    <button class="btn btn-sm btn-outline-success kb-restore-list" data-id="<?= $l['id'] ?>"><i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header py-2"><h6 class="mb-0 fw-semibold">Kartu terarsip (<?= count($cards) ?>)</h6></div>
            <div class="card-body p-0">
                <?php if (! $cards): ?><div class="p-3 small text-muted">Tidak ada.</div><?php endif; ?>
                <?php foreach ($cards as $c): ?>
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom border-secondary-subtle">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold small text-truncate"><?= esc($c['judul']) ?></div>
                        <div class="text-muted" style="font-size:.72rem">kolom <?= esc($c['list_nama']) ?><?= $c['list_archived'] ? ' (kolom juga terarsip)' : '' ?></div>
                    </div>
                    <?php if ($canEdit): ?>
                    <button class="btn btn-sm btn-outline-success kb-restore-card" data-id="<?= $c['id'] ?>" <?= $c['list_archived'] ? 'disabled title="Pulihkan kolomnya dulu"' : '' ?>><i class="bi bi-arrow-counterclockwise"></i></button>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                    <button class="btn btn-sm btn-outline-danger kb-delete-card" data-id="<?= $c['id'] ?>" title="Hapus permanen"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    const BASE = '<?= rtrim(base_url(), '/') ?>';
    const csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';
    function post(url) {
        const body = new FormData();
        body.append(csrfName, csrfHash);
        return fetch(BASE + url, { method: 'POST', body }).then(r => r.json())
            .then(d => { if (d.csrf) csrfHash = d.csrf; if (!d.success) alert(d.message || 'Gagal.'); return d; });
    }
    document.addEventListener('click', e => {
        const rl = e.target.closest('.kb-restore-list');
        if (rl) { post(`/kanban/lists/${rl.dataset.id}/restore`).then(d => { if (d.success) location.reload(); }); return; }
        const rc = e.target.closest('.kb-restore-card');
        if (rc && !rc.disabled) { post(`/kanban/cards/${rc.dataset.id}/restore`).then(d => { if (d.success) location.reload(); }); return; }
        const dc = e.target.closest('.kb-delete-card');
        if (dc && confirm('HAPUS PERMANEN kartu ini (beserta checklist/komentar/lampiran)?')) {
            post(`/kanban/cards/${dc.dataset.id}/delete`).then(d => { if (d.success) location.reload(); });
        }
    });
})();
</script>
<?= $this->endSection() ?>
