<?php
/**
 * Papan program kerja (admin & divisi): KPI, Perlu Perhatian, ringkasan PIC, filter, kartu per grup.
 * Param: $mode ('admin'|'division'), $groups [['title','icon','items'=>[...]]], $statusLabel,
 *        $gmUnread, $deptReplyUnread (id => jumlah), $canFlag (bool), $detailBase (url).
 */
echo view('work_report/_wr_fn');
$gmUnread        = $gmUnread ?? [];
$deptReplyUnread = $deptReplyUnread ?? [];
$isDiv   = $mode === 'division';
$canFlag = $canFlag ?? false;

// ── Hitung ringkasan (hanya penilaian tampilan dari data yang sama) ─────
$all = []; $kpi = ['total' => 0, 'att' => 0, 'over' => 0, 'stale' => 0, 'flag' => 0, 'msg' => 0];
$pics = []; $depts = [];
foreach ($groups as $gi => $g) foreach ($g['items'] as $it) {
    $m = wrMeta($it, (int) ($gmUnread[$it['id']] ?? 0), (int) ($deptReplyUnread[$it['id']] ?? 0));
    $it['_m'] = $m; $it['_g'] = $g['title'];
    $all[] = $it;
    $kpi['total']++;
    if ($m['att']) $kpi['att']++;
    if ($m['overdue']) $kpi['over']++;
    if ($m['noUpdate'] || $m['stale']) $kpi['stale']++;
    if ($m['flagged']) $kpi['flag']++;
    if ($m['gm'] || $m['dept']) $kpi['msg']++;
    $pn = trim((string) ($it['pic_name'] ?? '')) ?: 'Tanpa PIC';
    $pics[$pn] ??= ['n' => 0, 'sum' => 0, 'cnt' => 0, 'att' => 0];
    $pics[$pn]['n']++; $pics[$pn]['att'] += $m['att'] ? 1 : 0;
    if ($it['latest_progress'] !== null) { $pics[$pn]['sum'] += (int) $it['latest_progress']; $pics[$pn]['cnt']++; }
    $depts[$g['title']] = true;
}
uasort($pics, fn($a, $b) => [$b['att'], $b['n']] <=> [$a['att'], $a['n']]);
$attList = array_filter($all, fn($i) => $i['_m']['att']);
usort($attList, fn($a, $b) => $b['_m']['score'] <=> $a['_m']['score']);
$attTop = array_slice($attList, 0, 6);
$detUrl = fn($id) => $detailBase . '/' . $id . '/detail';
$kpiDef = [
    ['', 'Program Aktif', $kpi['total'], '', 'bi-kanban', 'dari ' . ($isDiv ? 'divisi ini' : 'semua cakupan')],
    ['att', 'Perlu Perhatian', $kpi['att'], $kpi['att'] ? 'bad' : 'ok', 'bi-bell-fill', $kpi['att'] ? 'ada yang butuh tindakan' : 'semua terkendali'],
    ['over', 'Terlambat', $kpi['over'], $kpi['over'] ? 'bad' : 'ok', 'bi-alarm-fill', 'lewat target selesai'],
    ['stale', 'Belum Update', $kpi['stale'], $kpi['stale'] ? 'warn' : 'ok', 'bi-hourglass-split', 'belum ada / &ge; 14 hari'],
    ['flag', 'Di-flag ke GM', $kpi['flag'], $kpi['flag'] ? 'gold' : '', 'bi-flag-fill', 'tampil di halaman GM'],
];
if ($isDiv) $kpiDef[] = ['msg', 'Pesan Baru', $kpi['msg'], $kpi['msg'] ? 'blue' : '', 'bi-chat-left-dots-fill', 'dari GM / Dept belum dibaca'];
?>
<?php if ($kpi['total'] === 0): ?>
<div class="wr-empty">
    <i class="bi bi-inbox"></i>
    <h5>Belum ada program kerja</h5>
    <p><?= $isDiv ? 'Tambahkan program kerja pertama untuk divisi ini.' : 'Tidak ada program kerja pada cakupan ini. Ubah filter divisi/departemen di atas.' ?></p>
    <?php if ($isDiv): ?><button class="wr-btn wr-btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-plus-lg"></i>Tambah Program Kerja</button><?php endif; ?>
</div>
<?php return; endif; ?>

<!-- KPI (klik = saring daftar) -->
<div class="wr-kpis wr-k<?= count($kpiDef) ?>">
<?php foreach ($kpiDef as [$key, $lbl, $val, $tone, $ico, $sub]): ?>
    <button type="button" class="wr-kpi<?= $tone ? ' t-' . $tone : '' ?>" data-wr-kpi="<?= $key ?>" aria-pressed="false">
        <span class="wr-kpi-l"><i class="bi <?= $ico ?>"></i><?= $lbl ?></span>
        <span class="wr-kpi-v"><?= $val ?></span>
        <span class="wr-kpi-s"><?= $sub ?></span>
    </button>
<?php endforeach; ?>
</div>

<!-- Perlu perhatian -->
<section class="wr-attn <?= $attTop ? '' : 'is-clear' ?>" id="wrAttn">
<?php if ($attTop): ?>
    <div class="wr-attn-h">
        <h2><i class="bi bi-bell-fill"></i>Perlu Perhatian <span class="wr-attn-n"><?= count($attList) ?></span></h2>
        <?php if (count($attList) > count($attTop)): ?>
        <button type="button" class="wr-btn wr-btn-sm" data-wr-kpi="att">Lihat semua <?= count($attList) ?> <i class="bi bi-arrow-down-short"></i></button>
        <?php endif; ?>
    </div>
    <div class="wr-attn-list">
    <?php foreach ($attTop as $it): $m = $it['_m']; ?>
        <div class="wr-attn-row">
            <div class="wr-attn-main min-w-0">
                <a class="wr-attn-t" href="<?= $detUrl($it['id']) ?>"><?= esc($it['judul']) ?></a>
                <div class="wr-attn-sub"><?= esc($it['_g']) ?><?php if (! empty($it['pic_name'])): ?> &middot; PIC <?= esc($it['pic_name']) ?><?php endif; ?></div>
            </div>
            <div class="wr-chips">
            <?php foreach (array_slice($m['reasons'], 0, 3) as $r): ?>
                <span class="wr-chip wr-chip-<?= $r[0] ?>"><i class="bi <?= $r[1] ?>"></i><?= esc($r[2]) ?></span>
            <?php endforeach; ?>
            </div>
            <a class="wr-btn wr-btn-xs" href="#initiative-<?= $it['id'] ?>" data-wr-jump="<?= $it['id'] ?>">Lihat kartu</a>
            <a class="wr-btn wr-btn-xs wr-btn-soft" href="<?= $detUrl($it['id']) ?>"><i class="bi bi-eye"></i>Buka</a>
        </div>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <i class="bi bi-check-circle-fill"></i><div><b>Semua program terkendali.</b> Tidak ada yang terlambat, belum update, atau menunggu balasan.</div>
<?php endif; ?>
</section>

<!-- Ringkasan per PIC -->
<?php if (count($pics) > 1): ?>
<section class="wr-pics" id="wrPics">
    <div class="wr-pics-h"><i class="bi bi-people-fill"></i>Per Anggota (PIC)
        <span class="wr-pics-hint">klik untuk menyaring</span></div>
    <div class="wr-pics-list">
    <?php $pi = 0; foreach ($pics as $name => $p): $pi++;
        $avg = $p['cnt'] ? (int) round($p['sum'] / $p['cnt']) : null; ?>
        <button type="button" class="wr-pic<?= $pi > 8 ? ' is-extra' : '' ?>" data-wr-pic="<?= esc($name, 'attr') ?>" <?= $pi > 8 ? 'hidden' : '' ?>>
            <span class="wr-pic-n"><?= esc($name) ?></span>
            <span class="wr-pic-m"><?= $p['n'] ?> program<?= $avg !== null ? ' &middot; rata-rata ' . $avg . '%' : '' ?></span>
            <?php if ($avg !== null): ?><span class="wr-bar wr-bar-<?= wrBarCls($avg) ?>"><i style="width:<?= $avg ?>%"></i></span><?php endif; ?>
            <?php if ($p['att']): ?><span class="wr-pic-att" title="Perlu perhatian"><?= $p['att'] ?></span><?php endif; ?>
        </button>
    <?php endforeach; ?>
    <?php if ($pi > 8): ?><button type="button" class="wr-btn wr-btn-sm" id="wrPicMore">+<?= $pi - 8 ?> lainnya</button><?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Pencarian & filter -->
<div class="wr-tools" id="wrTools">
    <div class="wr-search">
        <i class="bi bi-search"></i>
        <input type="search" id="wrQ" placeholder="Cari program, PIC, catatan…" autocomplete="off" aria-label="Cari program kerja">
    </div>
    <select class="wr-select" id="wrFStatus" aria-label="Saring status">
        <option value="">Semua status</option>
        <option value="att">Perlu perhatian</option>
        <option value="over">Terlambat</option>
        <option value="stale">Belum update</option>
        <option value="flag">Di-flag ke GM</option>
        <?php if ($isDiv): ?><option value="msg">Pesan baru</option><?php endif; ?>
        <option value="on_track">On Track</option>
        <option value="at_risk">At Risk</option>
        <option value="delayed">Delayed</option>
        <option value="done">Selesai</option>
        <option value="cancelled">Dibatalkan</option>
        <option value="none">Belum ada status</option>
    </select>
    <select class="wr-select" id="wrFPic" aria-label="Saring PIC">
        <option value="">Semua PIC</option>
        <?php foreach (array_keys($pics) as $pn): ?><option value="<?= esc($pn, 'attr') ?>"><?= esc($pn) ?></option><?php endforeach; ?>
    </select>
    <?php if ($isDiv && count($depts) > 1): ?>
    <select class="wr-select" id="wrFDept" aria-label="Saring departemen">
        <option value="">Semua departemen</option>
        <?php foreach (array_keys($depts) as $dn): ?><option value="<?= esc($dn, 'attr') ?>"><?= esc($dn) ?></option><?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select class="wr-select" id="wrSort" aria-label="Urutkan">
        <option value="">Urutan bawaan</option>
        <option value="att">Perlu perhatian dulu</option>
        <option value="pct">Progres terendah</option>
        <option value="upd">Update terlama</option>
        <option value="tgt">Target terdekat</option>
        <option value="nama">Nama A–Z</option>
    </select>
    <div class="wr-tools-end">
        <span class="wr-count" id="wrCount" aria-live="polite"></span>
        <button type="button" class="wr-btn wr-btn-sm" id="wrReset" hidden><i class="bi bi-x-lg"></i>Reset</button>
    </div>
</div>

<div class="wr-empty wr-empty-filter" id="wrNoMatch" hidden>
    <i class="bi bi-funnel"></i><h5>Tidak ada program yang cocok</h5><p>Ubah kata kunci atau filter, atau tekan Reset.</p>
</div>

<?php $order = 0; foreach ($groups as $g): ?>
<section class="wr-group" data-group>
    <div class="wr-group-h">
        <i class="bi <?= $g['icon'] ?>"></i>
        <h2><?= esc($g['title']) ?></h2>
        <span class="wr-group-n" data-group-n><?= count($g['items']) ?> program</span>
    </div>
    <div class="wr-list">
    <?php foreach ($g['items'] as $item):
        $m  = wrMeta($item, (int) ($gmUnread[$item['id']] ?? 0), (int) ($deptReplyUnread[$item['id']] ?? 0));
        $st = $m['st']; $info = $st ? ($statusLabel[$st] ?? null) : null;
        $pct = $item['latest_progress'] !== null ? (int) $item['latest_progress'] : null;
        $picName = trim((string) ($item['pic_name'] ?? '')) ?: 'Tanpa PIC';
        $stale = $m['stale'] || $m['noUpdate'];
        $search = mb_strtolower($item['judul'] . ' ' . $picName . ' ' . $g['title'] . ' ' . ($item['latest_catatan'] ?? '') . ' ' . ($item['latest_hambatan'] ?? ''));
        $tone = $m['overdue'] || $st === 'delayed' || $m['gm'] ? 'bad' : ($m['att'] && ! $m['flagged'] || $stale || $st === 'at_risk' ? 'warn' : ($m['flagged'] ? 'gold' : ($st === 'done' ? 'blue' : 'ok')));
    ?>
    <article class="wr-item t-<?= $tone ?>" id="initiative-<?= $item['id'] ?>" data-id="<?= $item['id'] ?>"
        data-name="<?= esc($item['judul'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
        data-st="<?= $st ?: 'none' ?>" data-att="<?= $m['att'] ? 1 : 0 ?>" data-over="<?= $m['overdue'] ? 1 : 0 ?>"
        data-stale="<?= $stale ? 1 : 0 ?>" data-flag="<?= $m['flagged'] ? 1 : 0 ?>" data-msg="<?= ($m['gm'] || $m['dept']) ? 1 : 0 ?>"
        data-pic="<?= esc($picName, 'attr') ?>" data-dept="<?= esc($g['title'], 'attr') ?>"
        data-pct="<?= $pct ?? -1 ?>" data-upd="<?= $m['updDays'] ?? 99999 ?>"
        data-tgt="<?= ! empty($item['target_selesai']) ? strtotime($item['target_selesai']) : 99999999999 ?>"
        data-score="<?= $m['score'] ?>" data-order="<?= $order++ ?>">
        <div class="wr-item-main">
            <div class="wr-item-top">
                <a class="wr-item-t" href="<?= $detUrl($item['id']) ?>"><?= esc($item['judul']) ?></a>
                <div class="wr-chips">
                    <?php if ($info): ?><span class="wr-chip wr-st-<?= $st ?>"><?= $info['label'] ?></span>
                    <?php else: ?><span class="wr-chip wr-chip-mute">Belum ada status</span><?php endif; ?>
                    <?php if ($m['overdue']): ?><span class="wr-chip wr-chip-bad"><i class="bi bi-alarm-fill"></i>Terlambat <?= $m['overDays'] ?> hari</span><?php endif; ?>
                    <?php if ($m['flagged']): ?><span class="wr-chip wr-chip-gold"><i class="bi bi-flag-fill"></i>Tampil di GM</span><?php endif; ?>
                    <?php if ($m['gm']): ?><span class="wr-chip wr-chip-bad"><i class="bi bi-chat-left-text-fill"></i><?= $m['gm'] ?> pesan GM</span><?php endif; ?>
                    <?php if ($m['dept']): ?><span class="wr-chip wr-chip-blue"><i class="bi bi-reply-fill"></i><?= $m['dept'] ?> balasan Dept</span><?php endif; ?>
                    <?php if (! empty($item['assigned_to_dept_id'])): ?><span class="wr-chip wr-chip-mute"><i class="bi bi-person-badge"></i>Dari Deputy</span><?php endif; ?>
                </div>
            </div>
            <div class="wr-prog">
                <?php if ($pct !== null): ?>
                <span class="wr-bar wr-bar-<?= wrBarCls($pct) ?>"><i style="width:<?= min(100, $pct) ?>%"></i></span><b><?= $pct ?>%</b>
                <?php else: ?><span class="wr-bar"><i style="width:0"></i></span><b class="wr-mute">&mdash;</b><?php endif; ?>
            </div>
            <?php if (! empty($item['latest_catatan']) || ! empty($item['latest_hambatan'])): ?>
            <div class="wr-notes">
                <?php if (! empty($item['latest_catatan'])): ?><div class="wr-note"><?= nl2br(esc(mb_substr($item['latest_catatan'], 0, 150))) ?><?= mb_strlen($item['latest_catatan']) > 150 ? '…' : '' ?></div><?php endif; ?>
                <?php if (! empty($item['latest_hambatan'])): ?><div class="wr-note wr-note-warn"><i class="bi bi-cone-striped"></i><?= nl2br(esc(mb_substr($item['latest_hambatan'], 0, 120))) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="wr-meta">
                <span><i class="bi bi-person-check"></i><?= ! empty($item['pic_name']) ? 'PIC ' . esc($item['pic_name']) : 'Belum ada PIC' ?></span>
                <?php if (! empty($item['target_selesai'])): ?>
                <span class="<?= $m['overdue'] ? 'is-bad' : '' ?>"><i class="bi bi-calendar-check"></i>Target <?= date('d M Y', strtotime($item['target_selesai'])) ?><?php
                    if ($m['overdue']) echo ' (lewat ' . $m['overDays'] . ' hari)'; elseif ($m['leftDays'] !== null && $m['leftDays'] <= 30 && ! $m['closed']) echo ' (' . $m['leftDays'] . ' hari lagi)'; ?></span>
                <?php endif; ?>
                <span class="<?= $stale ? 'is-warn' : '' ?>" title="<?= ! empty($item['latest_updated_at']) ? date('d M Y H:i', strtotime($item['latest_updated_at'])) : '' ?>"><i class="bi bi-arrow-repeat"></i>Update <?= wrAgo($m['updDays']) ?></span>
                <?php if (! empty($item['created_by_name'])): ?><span><i class="bi bi-pencil"></i><?= esc($item['created_by_name']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="wr-item-act">
            <a href="<?= $detUrl($item['id']) ?>" class="wr-btn wr-btn-soft"><i class="bi bi-eye"></i>Detail</a>
            <?php if ($isDiv): ?>
                <?php if ($canFlag): ?>
                <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/flag') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="wr-btn <?= $m['flagged'] ? 'wr-btn-gold' : '' ?>" title="<?= $m['flagged'] ? 'Batalkan tampil di GM' : 'Tampilkan ke GM' ?>">
                        <i class="bi bi-flag<?= $m['flagged'] ? '-fill' : '' ?>"></i><span class="d-none d-xl-inline"><?= $m['flagged'] ? 'Batalkan Flag' : 'Flag ke GM' ?></span><span class="d-xl-none"><?= $m['flagged'] ? 'Batal Flag' : 'Flag' ?></span>
                    </button>
                </form>
                <?php endif; ?>
                <button type="button" class="wr-btn" data-bs-toggle="collapse" data-bs-target="#commentForm<?= $item['id'] ?>" aria-expanded="false"><i class="bi bi-chat-left-dots"></i>Komentar</button>
            <?php endif; ?>
            <div class="dropdown">
                <button type="button" class="wr-iconbtn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Menu lainnya"><i class="bi bi-three-dots"></i></button>
                <ul class="dropdown-menu dropdown-menu-end wr-menu">
                    <li><a class="dropdown-item" href="<?= $detUrl($item['id']) ?>"><i class="bi bi-clock-history"></i>Riwayat &amp; detail</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/archive') ?>" onsubmit="return confirm('Arsipkan program kerja ini? Program pindah ke tab Arsip.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item"><i class="bi bi-archive"></i>Arsipkan</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        <?php if ($isDiv): ?>
        <div class="collapse wr-comment" id="commentForm<?= $item['id'] ?>">
            <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/comment') ?>">
                <?= csrf_field() ?>
                <div class="wr-comment-row">
                    <input type="text" name="body" class="wr-input" placeholder="Komentar ke Dept Head…" required aria-label="Komentar ke Dept Head">
                    <button type="submit" class="wr-btn wr-btn-primary"><i class="bi bi-send"></i>Kirim</button>
                </div>
                <div class="wr-hint">Hanya terlihat oleh Dept Head dan Deputy.</div>
            </form>
        </div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
