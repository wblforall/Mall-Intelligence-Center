<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = [
    'on_track'  => ['label' => 'On Track',  'badge' => 'bg-success',          'dot' => '#198754'],
    'at_risk'   => ['label' => 'At Risk',   'badge' => 'bg-warning text-dark', 'dot' => '#ffc107'],
    'delayed'   => ['label' => 'Delayed',   'badge' => 'bg-danger',            'dot' => '#dc3545'],
    'done'      => ['label' => 'Selesai',   'badge' => 'bg-primary',           'dot' => '#0d6efd'],
    'cancelled' => ['label' => 'Dibatalkan','badge' => 'bg-secondary',         'dot' => '#6c757d'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Progress Report</h4>
        <small class="text-muted">Ringkasan yang dikurasi Deputy per Divisi</small>
    </div>
    <?php if (! empty($byDivisi)): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="togglePilihSemua(this)" id="btnPilihSemua">
            <i class="bi bi-check2-square me-1"></i>Pilih Semua
        </button>
        <button class="btn btn-success btn-sm" onclick="salinLaporan(this)" id="btnSalin">
            <i class="bi bi-clipboard me-1"></i>Salin Laporan
        </button>
    </div>
    <?php endif; ?>
</div>


<?php if (empty($byDivisi)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-flag fs-1 d-block mb-2"></i>
    Belum ada inisiatif yang ditampilkan Deputy.
</div>
<?php else: ?>

<?php foreach ($byDivisi as $divisiName => $divisiItems): ?>
<div class="card mb-4">
<div class="card-header py-2" style="background:linear-gradient(90deg,var(--bs-primary-bg-subtle),transparent)">
    <h6 class="mb-0 fw-bold"><i class="bi bi-layers me-2 text-primary"></i><?= esc($divisiName) ?></h6>
    <?php
    $deputies = array_unique(array_filter(array_column($divisiItems, 'deputy_name')));
    if ($deputies):
    ?>
    <small class="text-muted"><i class="bi bi-person-badge me-1"></i><?= esc(implode(', ', $deputies)) ?></small>
    <?php endif; ?>
</div>
<div class="card-body p-0">

<?php
// Kelompokkan per dept dalam divisi
$byDept = [];
foreach ($divisiItems as $item) {
    $key = $item['dept_name'] ?? 'Tanpa Dept';
    $byDept[$key][] = $item;
}
?>

<?php foreach ($byDept as $deptName => $deptItems): ?>
<div class="px-3 pt-3 pb-1">
    <div class="text-muted fw-semibold mb-2" style="font-size:.73rem;text-transform:uppercase;letter-spacing:.06em">
        <i class="bi bi-building me-1"></i><?= esc($deptName) ?>
    </div>

    <?php foreach ($deptItems as $item):
        $st   = $item['latest_status'] ?? null;
        $info = $st ? ($statusLabel[$st] ?? $statusLabel['on_track']) : null;
        $overdue = ! empty($item['target_selesai']) && $item['target_selesai'] < date('Y-m-d') && $st !== 'done' && $st !== 'cancelled';
        $thread = $threads[$item['id']] ?? [];
    ?>
    <div class="border rounded mb-3 p-3" id="initiative-<?= $item['id'] ?>">
        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
            <div class="form-check me-1 mt-1 flex-shrink-0">
                <input class="form-check-input initiative-check" type="checkbox" value="<?= $item['id'] ?>" id="chk-<?= $item['id'] ?>">
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">
                    <?= esc($item['judul']) ?>
                    <?php if ($info): ?><span class="badge <?= $info['badge'] ?> ms-1" style="font-size:.65rem"><?= $info['label'] ?></span><?php endif; ?>
                    <?php if ($overdue): ?><span class="badge bg-danger ms-1" style="font-size:.65rem"><i class="bi bi-exclamation-triangle me-1"></i>Terlambat</span><?php endif; ?>
                    <?php if (! empty($gmUnread[$item['id']])): ?><span class="badge rounded-pill bg-danger ms-1" style="font-size:.65rem;min-width:1.4em"><?= $gmUnread[$item['id']] ?></span><?php endif; ?>
                </div>
                <?php if (! empty($item['deskripsi'])): ?>
                <div class="text-muted small mb-1"><?= esc(mb_substr($item['deskripsi'], 0, 150)) ?></div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-3" style="font-size:.7rem;color:var(--bs-secondary-color)">
                    <?php if (! empty($item['created_by_name'])): ?><span class="text-muted"><i class="bi bi-pencil me-1"></i><?= esc($item['created_by_name']) ?></span><?php endif; ?>
                    <?php if (! empty($item['pic_name'])): ?><span><i class="bi bi-person-check me-1"></i>PIC: <?= esc($item['pic_name']) ?></span><?php endif; ?>
                    <?php if (! empty($item['target_selesai'])): ?><span><i class="bi bi-calendar-check me-1"></i><?= date('d M Y', strtotime($item['target_selesai'])) ?></span><?php endif; ?>
                    <?php if (! empty($item['deputy_name'])): ?><span><i class="bi bi-person-badge me-1 text-primary"></i>Dikurasi: <?= esc($item['deputy_name']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (! empty($item['latest_catatan'])): ?>
        <div class="p-2 rounded mb-2" style="background:var(--bs-secondary-bg);font-size:.75rem">
            <strong>Progress:</strong> <?= nl2br(esc($item['latest_catatan'])) ?>
        </div>
        <?php endif; ?>

        <?php if (! empty($item['latest_hambatan'])): ?>
        <div class="p-2 rounded mb-2 border-start border-warning border-3" style="background:var(--bs-secondary-bg);font-size:.75rem">
            <i class="bi bi-cone-striped text-warning me-1"></i><strong>Hambatan:</strong> <?= nl2br(esc($item['latest_hambatan'])) ?>
        </div>
        <?php endif; ?>

        <!-- Thread GM ↔ Deputy -->
        <div class="border-top pt-2 mt-2">
            <div class="d-flex align-items-center gap-1 mb-2">
                <i class="bi bi-chat-dots text-warning" style="font-size:.8rem"></i>
                <span style="font-size:.68rem;font-weight:600;color:var(--bs-secondary-color)">CATATAN GM ↔ DEPUTY</span>
            </div>

            <?php if (! empty($thread)): ?>
            <div class="mb-2" style="max-height:180px;overflow-y:auto">
                <?php foreach ($thread as $c): ?>
                <div class="d-flex gap-2 mb-2">
                    <div class="flex-grow-1 p-2 rounded" style="background:var(--bs-secondary-bg);font-size:.73rem">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><?= esc($c['author_name'] ?? '—') ?></strong>
                            <small class="text-muted"><?= date('d M H:i', strtotime($c['created_at'])) ?></small>
                        </div>
                        <?= nl2br(esc($c['body'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Form catatan GM -->
            <form method="POST" action="<?= base_url('work-report/gm/' . $item['id'] . '/note') ?>">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="text" name="body" class="form-control form-control-sm" placeholder="Tambah catatan ke Deputy…" required>
                    <button type="submit" class="btn btn-warning btn-sm flex-shrink-0 text-dark">
                        <i class="bi bi-send me-1"></i>Kirim
                    </button>
                </div>
                <div class="form-text" style="font-size:.65rem">Hanya terlihat oleh Deputy dan GM.</div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

</div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php
// Embed initiative data as JS for dynamic clipboard building
$statusText = ['on_track'=>'On Track','at_risk'=>'At Risk','delayed'=>'Delayed','done'=>'Selesai','cancelled'=>'Dibatalkan'];
$initiativeData = [];
foreach ($byDivisi as $divisiName => $divisiItems) {
    foreach ($divisiItems as $it) {
        $initiativeData[$it['id']] = [
            'id'           => $it['id'],
            'divisi'       => $divisiName,
            'dept'         => $it['dept_name'] ?? 'Tanpa Dept',
            'deputy'       => $it['deputy_name'] ?? null,
            'judul'        => $it['judul'],
            'status'       => $statusText[$it['latest_status'] ?? ''] ?? '-',
            'progress'     => $it['latest_progress'],
            'catatan'      => $it['latest_catatan'] ?? null,
            'hambatan'     => $it['latest_hambatan'] ?? null,
            'target'       => ! empty($it['target_selesai']) ? date('d M Y', strtotime($it['target_selesai'])) : null,
        ];
    }
}
?>

<!-- Toast notifikasi -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
    <div id="toastSalin" class="toast align-items-center text-bg-success border-0" role="alert" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-check2-circle me-2"></i>Laporan berhasil disalin!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const _initiatives = <?= json_encode(array_values($initiativeData)) ?>;

function copyFallback(txt, onCopied) {
    const ta = document.createElement('textarea');
    ta.value = txt;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try {
        if (document.execCommand('copy')) { onCopied(); }
        else { alert('Gagal menyalin teks.'); }
    } catch(e) { alert('Gagal menyalin teks.'); }
    document.body.removeChild(ta);
}

function togglePilihSemua(btn) {
    const checks = document.querySelectorAll('.initiative-check');
    const allChecked = [...checks].every(c => c.checked);
    checks.forEach(c => c.checked = !allChecked);
    btn.innerHTML = !allChecked
        ? '<i class="bi bi-x-square me-1"></i>Batal Pilih'
        : '<i class="bi bi-check2-square me-1"></i>Pilih Semua';
}

function salinLaporan(btn) {
    const checkedIds = new Set(
        [...document.querySelectorAll('.initiative-check:checked')].map(c => +c.value)
    );
    if (checkedIds.size === 0) {
        alert('Pilih minimal satu inisiatif untuk disalin.');
        return;
    }
    const selected = _initiatives.filter(i => checkedIds.has(i.id));

    // Kelompokkan divisi → dept
    const byDivisi = {};
    selected.forEach(i => {
        byDivisi[i.divisi] = byDivisi[i.divisi] || {};
        byDivisi[i.divisi][i.dept] = byDivisi[i.divisi][i.dept] || [];
        byDivisi[i.divisi][i.dept].push(i);
    });

    const lines = ['*PROGRESS REPORT*', 'Per: ' + new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})];
    for (const [divisi, depts] of Object.entries(byDivisi)) {
        lines.push('');
        lines.push('*' + divisi.toUpperCase() + '*');
        const deps = [...new Set(Object.values(depts).flat().map(i => i.deputy).filter(Boolean))];
        if (deps.length) lines.push('Deputy: ' + deps.join(', '));
        for (const [dept, items] of Object.entries(depts)) {
            lines.push('');
            lines.push('_' + dept + '_');
            items.forEach((it, idx) => {
                const pct = it.progress !== null ? ' | ' + it.progress + '%' : '';
                lines.push((idx + 1) + '. ' + it.judul + ' (' + it.status + pct + ')');
                if (it.catatan)  lines.push('   ' + it.catatan);
                if (it.hambatan) lines.push('   ⚠️ ' + it.hambatan);
                if (it.target)   lines.push('   Target: ' + it.target);
            });
        }
    }

    const txt = lines.join('\n');

    function onCopied() {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin!';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
        const toast = new bootstrap.Toast(document.getElementById('toastSalin'), {delay: 3000});
        toast.show();
    }

    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(txt).then(onCopied).catch(() => copyFallback(txt, onCopied));
        } else {
            copyFallback(txt, onCopied);
        }
    } catch(e) {
        copyFallback(txt, onCopied);
    }
}
</script>

<?= $this->endSection() ?>
