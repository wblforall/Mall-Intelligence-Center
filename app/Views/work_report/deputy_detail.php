<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/work-report.css') ?>?v=<?= @filemtime(FCPATH . 'css/work-report.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
echo view('work_report/_wr_fn');
$statusLabel = [
    'on_track'  => ['label' => 'On Track',  'badge' => 'bg-success'],
    'at_risk'   => ['label' => 'At Risk',   'badge' => 'bg-warning text-dark'],
    'delayed'   => ['label' => 'Delayed',   'badge' => 'bg-danger'],
    'done'      => ['label' => 'Selesai',   'badge' => 'bg-primary'],
    'cancelled' => ['label' => 'Dibatalkan','badge' => 'bg-secondary'],
];
$deptName = '';
foreach ($depts as $d) { if ((int) $d['id'] === (int) ($item['dept_id'] ?? 0)) $deptName = $d['name']; }
$last = $history[0] ?? null;
$li   = $last ? array_merge($item, [
    'latest_status' => $last['status'], 'latest_updated_at' => $last['created_at'],
    'latest_progress' => $last['progress_pct'], 'is_flagged' => $isFlagged ? 1 : 0]) : array_merge($item, ['is_flagged' => $isFlagged ? 1 : 0]);
$m   = wrMeta($li);
$pct = ($last && $last['progress_pct'] !== null) ? (int) $last['progress_pct'] : null;
$info = $m['st'] ? ($statusLabel[$m['st']] ?? null) : null;
$gmN = count($gmThread ?? []); $dN = count($deptComments ?? []);
?>
<div class="page-wr" id="wrRoot" data-mode="detail">

<div class="wr-head">
    <a href="<?= base_url('work-report/division') ?>" class="wr-back" title="Kembali ke daftar divisi" aria-label="Kembali ke daftar divisi"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <nav class="wr-crumb" aria-label="Navigasi"><a href="<?= base_url('work-report/division') ?>">Progress Report Divisi</a><i class="bi bi-chevron-right"></i><span><?= esc($deptName ?: 'Detail') ?></span></nav>
        <h1 class="wr-title wr-title-sm"><?= esc($item['judul']) ?></h1>
        <div class="wr-chips wr-chips-head">
            <?php if ($info): ?><span class="wr-chip wr-st-<?= $m['st'] ?>"><?= $info['label'] ?></span><?php else: ?><span class="wr-chip wr-chip-mute">Belum ada status</span><?php endif; ?>
            <?php foreach ($m['reasons'] as $r): if ($r[2] === 'Delayed' || $r[2] === 'At Risk') continue; ?>
            <span class="wr-chip wr-chip-<?= $r[0] ?>"><i class="bi <?= $r[1] ?>"></i><?= esc($r[2]) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="wr-head-act">
        <?php if ($canFlag ?? true): ?>
        <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/flag') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="wr-btn <?= $isFlagged ? 'wr-btn-gold' : '' ?>">
                <i class="bi bi-flag<?= $isFlagged ? '-fill' : '' ?>"></i><?= $isFlagged ? 'Tampil di GM' : 'Flag ke GM' ?>
            </button>
        </form>
        <?php elseif ($isFlagged): ?>
        <span class="wr-chip wr-chip-gold"><i class="bi bi-flag-fill"></i>Tampil di GM</span>
        <?php endif; ?>
        <button class="wr-btn" data-bs-toggle="modal" data-bs-target="#modalEdit"><i class="bi bi-pencil"></i>Edit</button>
    </div>
</div>

<!-- Ringkasan -->
<div class="wr-kpis wr-k4">
    <div class="wr-kpi is-static"><span class="wr-kpi-l"><i class="bi bi-speedometer2"></i>Progres</span>
        <span class="wr-kpi-v"><?= $pct !== null ? $pct . '%' : '—' ?></span>
        <span class="wr-bar wr-bar-<?= wrBarCls($pct) ?>"><i style="width:<?= min(100, (int) $pct) ?>%"></i></span></div>
    <div class="wr-kpi is-static t-<?= $m['stale'] || $m['noUpdate'] ? 'warn' : '' ?>"><span class="wr-kpi-l"><i class="bi bi-arrow-repeat"></i>Update Terakhir</span>
        <span class="wr-kpi-v wr-kpi-v-sm"><?= wrAgo($m['updDays']) ?></span>
        <span class="wr-kpi-s"><?= $last ? date('d M Y', strtotime($last['created_at'])) : 'belum ada update' ?></span></div>
    <div class="wr-kpi is-static t-<?= $m['overdue'] ? 'bad' : '' ?>"><span class="wr-kpi-l"><i class="bi bi-calendar-check"></i>Target Selesai</span>
        <span class="wr-kpi-v wr-kpi-v-sm"><?= ! empty($item['target_selesai']) ? date('d M Y', strtotime($item['target_selesai'])) : '—' ?></span>
        <span class="wr-kpi-s"><?= $m['overdue'] ? 'terlambat ' . $m['overDays'] . ' hari' : ($m['leftDays'] !== null && ! $m['closed'] ? $m['leftDays'] . ' hari lagi' : '&nbsp;') ?></span></div>
    <div class="wr-kpi is-static"><span class="wr-kpi-l"><i class="bi bi-chat-dots"></i>Percakapan</span>
        <span class="wr-kpi-v wr-kpi-v-sm"><?= $gmN + $dN ?> pesan</span>
        <span class="wr-kpi-s"><?= $gmN ?> dengan GM &middot; <?= $dN ?> dengan Dept</span></div>
</div>
<?php if (! empty($item['deskripsi'])): ?>
<div class="wr-desc"><i class="bi bi-card-text"></i><?= nl2br(esc($item['deskripsi'])) ?></div>
<?php endif; ?>

<div class="row g-3">
<!-- Riwayat -->
<div class="col-12 col-lg-5">
    <div class="wr-card mb-3">
        <div class="wr-card-h"><h2><i class="bi bi-clock-history"></i>Riwayat Update</h2><span class="wr-mute small"><?= count($history) ?> update</span></div>
        <div class="wr-feed">
        <?php if (empty($history)): ?>
            <div class="wr-feed-empty"><i class="bi bi-journal-x"></i>Belum ada update.</div>
        <?php else: foreach ($history as $h):
            $hi = $statusLabel[$h['status']] ?? $statusLabel['on_track']; ?>
            <div class="wr-feed-i">
                <div class="wr-feed-top">
                    <span class="wr-chip wr-st-<?= $h['status'] ?>"><?= $hi['label'] ?></span>
                    <?php if ($h['progress_pct'] !== null): ?><span class="wr-chip wr-chip-mute"><?= $h['progress_pct'] ?>%</span><?php endif; ?>
                    <span class="wr-mute small ms-auto"><?= date('d M Y', strtotime($h['created_at'])) ?></span>
                </div>
                <?php if (! empty($h['catatan'])): ?><div class="wr-feed-t"><?= nl2br(esc($h['catatan'])) ?></div><?php endif; ?>
                <?php if (! empty($h['hambatan'])): ?>
                <div class="wr-note wr-note-warn"><i class="bi bi-cone-striped"></i><?= nl2br(esc($h['hambatan'])) ?></div>
                <?php endif; ?>
                <?= view('work_report/_update_images', ['images' => $h['images'] ?? []]) ?>
            </div>
        <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Percakapan -->
<div class="col-12 col-lg-7">
    <div class="wr-card mb-3 wr-card-gold">
        <div class="wr-card-h"><h2><i class="bi bi-chat-dots"></i>Thread GM &harr; Deputy</h2><span class="wr-mute small">Hanya terlihat GM + Deputy</span></div>
        <div class="wr-feed wr-thread">
        <?php if (empty($gmThread)): ?>
            <div class="wr-feed-empty"><i class="bi bi-chat"></i>Belum ada catatan dari GM.</div>
        <?php else: foreach ($gmThread as $c): $mine = (int) $c['author_id'] === (int) $emp['id']; ?>
            <div class="wr-msg<?= $mine ? ' is-mine' : '' ?>">
                <div class="wr-msg-h"><b><?= esc($c['author_name'] ?? '—') ?></b><span><?= date('d M Y H:i', strtotime($c['created_at'])) ?></span></div>
                <div class="wr-msg-b"><?= nl2br(esc($c['body'])) ?></div>
            </div>
        <?php endforeach; endif; ?>
        </div>
        <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/reply-gm') ?>" class="wr-reply">
            <?= csrf_field() ?>
            <?php $lastGm = ! empty($gmThread) ? end($gmThread) : null; ?>
            <?php if ($lastGm): ?><input type="hidden" name="parent_id" value="<?= $lastGm['id'] ?>"><?php endif; ?>
            <div class="wr-comment-row">
                <input type="text" name="body" class="wr-input" placeholder="Balas ke GM…" required aria-label="Balas ke GM">
                <button type="submit" class="wr-btn wr-btn-gold"><i class="bi bi-send"></i>Kirim</button>
            </div>
        </form>
    </div>

    <div class="wr-card">
        <div class="wr-card-h"><h2><i class="bi bi-chat-left-text"></i>Komentar ke Dept Head</h2><span class="wr-mute small">Terlihat Dept Head + Deputy</span></div>
        <div class="wr-feed wr-thread">
        <?php if (empty($deptComments)): ?>
            <div class="wr-feed-empty"><i class="bi bi-chat"></i>Belum ada komentar.</div>
        <?php else: foreach ($deptComments as $c): $mine = (int) $c['author_id'] === (int) $emp['id']; ?>
            <div class="wr-msg<?= $mine ? ' is-mine' : '' ?>">
                <div class="wr-msg-h"><b><?= esc($c['author_name'] ?? '—') ?></b><span><?= date('d M Y H:i', strtotime($c['created_at'])) ?></span></div>
                <div class="wr-msg-b"><?= nl2br(esc($c['body'])) ?></div>
            </div>
        <?php endforeach; endif; ?>
        </div>
        <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/comment') ?>" class="wr-reply">
            <?= csrf_field() ?>
            <div class="wr-comment-row">
                <input type="text" name="body" class="wr-input" placeholder="Tulis komentar…" required aria-label="Komentar ke Dept Head">
                <button type="submit" class="wr-btn wr-btn-primary"><i class="bi bi-send"></i>Kirim</button>
            </div>
        </form>
    </div>
</div>
</div>

</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title fw-semibold">Edit Program Kerja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/edit') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control form-control-sm" value="<?= esc($item['judul']) ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control form-control-sm" rows="2"><?= esc($item['deskripsi'] ?? '') ?></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Assign ke Departemen</label>
        <select name="assigned_to_dept_id" class="form-select form-select-sm">
            <option value="">— Tidak di-assign —</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int)($item['assigned_to_dept_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row g-2">
        <div class="col-12 col-sm-6">
            <label class="form-label small fw-semibold">Target Mulai</label>
            <input type="date" name="target_mulai" class="form-control form-control-sm" value="<?= $item['target_mulai'] ?? '' ?>">
        </div>
        <div class="col-12 col-sm-6">
            <label class="form-label small fw-semibold">Target Selesai</label>
            <input type="date" name="target_selesai" class="form-control form-control-sm" value="<?= $item['target_selesai'] ?? '' ?>">
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
    </div>
</form>
</div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/work-report.js') ?>?v=<?= @filemtime(FCPATH . 'js/work-report.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
