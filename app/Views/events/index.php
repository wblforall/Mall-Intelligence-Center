<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.anim-fade-up { opacity: 0; animation: fadeUp .45s cubic-bezier(.22,.68,0,1.15) forwards; }
.anim-fade-in { opacity: 0; animation: fadeIn .35s ease forwards; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 anim-fade-up" style="animation-delay:.05s">
    <h4 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2"></i>Daftar Event</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('events/compare') ?>" class="btn btn-outline-secondary btn-sm" id="compareBtn" style="display:none!important">
            <i class="bi bi-arrow-left-right me-1"></i> Bandingkan
        </a>
        <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
        <a href="<?= base_url('events/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Buat Event
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (($pendingCount ?? 0) > 0): ?>
<div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3 anim-fade-in" style="animation-delay:.10s" role="alert">
    <i class="bi bi-hourglass-split fs-5"></i>
    <div>
        <strong><?= $pendingCount ?> event</strong> menunggu persetujuan Anda.
        Cari baris berstatus <span class="badge bg-info text-white">Pending</span> di bawah.
    </div>
</div>
<?php endif; ?>
<?php if (($incompleteCount ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3 anim-fade-in" style="animation-delay:.15s" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong><?= $incompleteCount ?> event</strong> sudah selesai tapi data belum dilengkapi.
        Cari baris berstatus <span class="badge bg-warning text-dark">Waiting Data</span> di bawah.
    </div>
</div>
<?php endif; ?>

<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
?>

<div class="card anim-fade-up" style="animation-delay:.18s">
    <div class="card-body p-0">
        <?php if (empty($events)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-inbox display-3 d-block mb-3"></i>
            <p>Belum ada event. Mulai dengan membuat event baru.</p>
            <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
            <a href="<?= base_url('events/create') ?>" class="btn btn-primary">Buat Event Pertama</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr>
                    <th style="width:36px"><input type="checkbox" id="checkAll" title="Pilih semua"></th>
                    <th>#</th><th>Event</th><th>Tema</th><th>Mall</th><th>Periode</th><th>Status</th>
                    <?php if ($canApprove ?? false): ?><th>Approval</th><?php endif; ?>
                    <th>Aksi</th>
                </tr></thead>
                <tbody>
                <?php foreach ($events as $i => $e): ?>
                <?php $sc = ['draft'=>'warning','active'=>'success','waiting_data'=>'warning','completed'=>'secondary'][$e['status']] ?? 'secondary' ?>
                <?php $sl = ['draft'=>'Draft','active'=>'Active','waiting_data'=>'Waiting Data','completed'=>'Completed'][$e['status']] ?? ucfirst($e['status']) ?>
                <?php $isPending  = ($e['approval_status'] ?? 'approved') === 'pending' ?>
                <?php $isRejected = ($e['approval_status'] ?? 'approved') === 'rejected' ?>
                <tr class="<?= $e['status'] === 'waiting_data' ? 'table-warning' : ($isPending ? 'table-info' : ($isRejected ? 'table-danger' : '')) ?> anim-fade-up"
                    style="animation-delay:<?= (.22 + $i * .06) ?>s"><?php /* staggered row entrance */ ?>
                    <td><input type="checkbox" class="evt-check" value="<?= $e['id'] ?>"></td>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= esc($e['name']) ?></td>
                    <td class="small text-muted"><?= esc($e['tema'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary"><?= $mallLabels[$e['mall']] ?? esc($e['mall']) ?></span></td>
                    <td class="small">
                        <?php if ($e['start_date']): ?>
                        <?php $endDate = date('Y-m-d', strtotime($e['start_date'] . ' +' . ($e['event_days'] - 1) . ' days')) ?>
                        <?= date('d M Y', strtotime($e['start_date'])) ?>
                        <?php if ($endDate !== $e['start_date']): ?>
                        – <?= date('d M Y', strtotime($endDate)) ?>
                        <?php endif; ?>
                        <div class="text-muted" style="font-size:.72rem"><?= $e['event_days'] ?> hari</div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= $sl ?></span>
                        <?php if ($e['status'] === 'waiting_data'): ?>
                        <i class="bi bi-exclamation-circle text-warning ms-1" title="Data belum lengkap"></i>
                        <?php endif; ?>
                    </td>
                    <?php if ($canApprove ?? false): ?>
                    <td>
                        <?php if ($isPending): ?>
                        <span class="badge bg-info text-white">Pending</span>
                        <?php elseif ($isRejected): ?>
                        <span class="badge bg-danger" title="<?= esc($e['rejection_reason'] ?? '') ?>">Ditolak</span>
                        <?php else: ?>
                        <span class="badge bg-success-subtle text-success">Disetujui</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="<?= base_url('events/'.$e['id'].'/summary') ?>" class="btn btn-sm <?= $isPending ? 'btn-outline-info' : 'btn-primary' ?> me-1"
                           title="<?= $isPending ? 'Buka & Review' : 'Lihat Summary' ?>">
                            <i class="bi bi-<?= $isPending ? 'eye' : 'speedometer2' ?>"></i>
                        </a>
                        <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="<?= base_url('events/'.$e['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?= base_url('events/'.$e['id'].'/delete') ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Hapus event ini beserta semua datanya?')">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Compare sticky bar -->
<div id="compareBar" class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3 d-none" style="z-index:1050;transition:transform .3s cubic-bezier(.22,.68,0,1.2)">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <span><i class="bi bi-arrow-left-right me-2"></i><strong id="compareCount">0</strong> event dipilih untuk dibandingkan <span class="text-warning small">(maks. 3)</span></span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-light" onclick="clearCompare()">Batal</button>
            <a href="#" class="btn btn-sm btn-warning fw-bold" id="goCompare">Bandingkan Sekarang</a>
        </div>
    </div>
</div>

<script>
const checkAll  = document.getElementById('checkAll');
const checks    = document.querySelectorAll('.evt-check');
const bar       = document.getElementById('compareBar');
const countEl   = document.getElementById('compareCount');
const goBtn     = document.getElementById('goCompare');
const compareBtn = document.getElementById('compareBtn');

// Cap row stagger delays so late rows don't wait > 600ms
document.querySelectorAll('tbody tr.anim-fade-up').forEach((tr, i) => {
    if (i > 8) tr.style.animationDelay = (.22 + 8 * .06) + 's';
});

function updateCompare() {
    const selected = [...checks].filter(c => c.checked);
    countEl.textContent = selected.length;
    if (selected.length >= 2) {
        bar.classList.remove('d-none');
        bar.style.transform = 'translateY(100%)';
        requestAnimationFrame(() => { bar.style.transform = 'translateY(0)'; });
        const ids = selected.map(c => 'ids[]=' + c.value).join('&');
        goBtn.href = '<?= base_url('events/compare') ?>?' + ids;
    } else {
        bar.style.transform = 'translateY(100%)';
        setTimeout(() => bar.classList.add('d-none'), 280);
    }
}

function clearCompare() {
    checks.forEach(c => c.checked = false);
    if (checkAll) checkAll.checked = false;
    updateCompare();
}

checks.forEach(c => c.addEventListener('change', function() {
    const selected = [...checks].filter(ch => ch.checked);
    if (selected.length > 3) { this.checked = false; return; }
    updateCompare();
}));

if (checkAll) {
    checkAll.addEventListener('change', function() {
        const all = [...checks];
        if (this.checked && all.length > 3) {
            all.slice(0, 3).forEach(c => c.checked = true);
            all.slice(3).forEach(c => c.checked = false);
            this.checked = false;
        } else {
            all.forEach(c => c.checked = this.checked);
        }
        updateCompare();
    });
}
</script>

<?= $this->endSection() ?>
