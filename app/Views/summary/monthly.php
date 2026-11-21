<?php helper('tanggal'); ?><?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<style>
/* ═══ Summary Bulanan Event — kalender bulan: pengunjung harian + pita event ═══ */
.ms-legend { display: flex; flex-wrap: wrap; gap: .4rem 1.1rem; font-size: .76rem; color: var(--txt-muted); }
.ms-legend i { display: inline-block; width: 12px; height: 10px; border-radius: 2px; margin-right: .35rem; vertical-align: -1px; }
.ms-scroll { overflow-x: auto; }
.ms-cal { --lab: 210px; position: relative; min-width: 820px; }
.ms-grid { display: grid; grid-template-columns: var(--lab) repeat(var(--n), minmax(0, 1fr)); align-items: end; }
.ms-bg { position: absolute; inset: 0; pointer-events: none; }
.ms-bg > div { height: 100%; }
.ms-bg .wk { background: rgba(148,163,184,.07); }
.ms-bg .now { background: rgba(239,68,68,.10); box-shadow: inset 2px 0 0 #ef4444; }
[data-theme="light"] .ms-bg .wk { background: rgba(100,116,139,.07); }
.ms-lab { font-size: .78rem; color: var(--txt); padding-right: .8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; align-self: center; }
.ms-lab small { display: block; font-size: .68rem; color: var(--txt-muted); }
.ms-traffic { height: 120px; border-bottom: 1px solid var(--card-border, rgba(255,255,255,.08)); }
.ms-day { height: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 1px; padding: 0 1px; }
.ms-day span { flex: 1 1 0; max-width: 9px; border-radius: 2px 2px 0 0; min-height: 0; }
.ms-day .ew { background: var(--c-ewalk, #3b82f6); }
.ms-day .pc { background: var(--c-penta, #10b981); }
.ms-dates .d { text-align: center; font-size: .66rem; line-height: 1.15; color: var(--txt-muted); padding: .3rem 0 .45rem; font-variant-numeric: tabular-nums; }
.ms-dates .d b { display: block; font-size: .74rem; color: var(--txt); font-weight: 600; }
.ms-dates .d.red, .ms-dates .d.red b { color: #f87171; }
.ms-ev { height: 30px; align-items: center; border-top: 1px solid rgba(255,255,255,.04); }
[data-theme="light"] .ms-ev { border-top-color: rgba(15,23,42,.05); }
.ms-ev .ms-lab a { color: inherit; text-decoration: none; }
.ms-ev .ms-lab a:hover { text-decoration: underline; }
.ms-bar { height: 20px; border-radius: 5px; display: flex; align-items: center; padding: 0 .5rem; overflow: hidden; white-space: nowrap;
          font-size: .7rem; font-weight: 600; color: #fff; text-decoration: none; position: relative; z-index: 1; }
.ms-bar:hover { color: #fff; filter: brightness(1.12); }
.ms-bar.m-ewalk { background: var(--c-ewalk, #3b82f6); }
.ms-bar.m-pentacity { background: var(--c-penta, #10b981); }
.ms-bar.m-keduanya { background: linear-gradient(90deg, var(--c-ewalk, #3b82f6), var(--c-penta, #10b981)); }
.ms-bar.cut-l { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.ms-bar.cut-r { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.ms-bar.tbc { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.22) 0 5px, transparent 5px 10px); }
.ms-up { display: inline-block; padding: .1rem .45rem; border-radius: 999px; font-size: .74rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.ms-up.pos { background: rgba(34,197,94,.15); color: #4ade80; }
.ms-up.neg { background: rgba(239,68,68,.14); color: #f87171; }
.ms-up.flat { background: rgba(148,163,184,.14); color: var(--txt-muted); }
[data-theme="light"] .ms-up.pos { color: #15803d; } [data-theme="light"] .ms-up.neg { color: #b91c1c; }
.ms-roi { display: block; height: 4px; border-radius: 2px; background: rgba(148,163,184,.18); margin-top: .3rem; overflow: hidden; }
.ms-roi i { display: block; height: 100%; }
.ms-note { font-size: .74rem; color: var(--txt-muted); margin-top: .6rem; }
.ms-kpi-split { font-size: .74rem; color: var(--txt-muted); margin-top: .15rem; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels   = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'keduanya' => 'eWalk & Pentacity'];
$statusLabels = ['draft' => 'Draft', 'active' => 'Berjalan', 'waiting_data' => 'Menunggu Data', 'completed' => 'Selesai'];
$stChip       = ['draft' => 'mute', 'active' => 'ok', 'waiting_data' => 'warn', 'completed' => 'blue'];
$mlChip       = ['ewalk' => 'blue', 'pentacity' => 'ok', 'keduanya' => 'warn'];
$idBulan      = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli',
                 'August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanLabel   = strtr(\DateTime::createFromFormat('Y-m', $bulan)->format('F Y'), $idBulan);
$rp     = static fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
$rpKecil = static fn($n) => abs($n) >= 1e9 ? 'Rp ' . number_format($n / 1e9, 2, ',', '.') . ' M' : (abs($n) >= 1e6 ? 'Rp ' . number_format($n / 1e6, 1, ',', '.') . ' jt' : $rp($n));
$totalProfit = $totalRevenue - $totalBudget;
$roiTotal    = $totalBudget > 0 ? round($totalRevenue / $totalBudget * 100) : null;
$perMall     = array_count_values(array_map(fn($r) => $r['event']['mall'] ?? 'keduanya', $rows));
$nHari       = count($hariBulan);
$hariIni     = date('Y-m-d');
$HARI1       = ['M','S','S','R','K','J','S'];
// Skala batang: hari tertinggi salah satu mal
$maks = 1;
foreach ($harian as $v) foreach ($v as $n) $maks = max($maks, (int) $n);
$colOf = static fn(string $tgl) => (int) date('j', strtotime($tgl));   // kolom 1..n
?>

<div class="page-evs">

<!-- Kepala & navigasi bulan -->
<div class="ev-head">
    <div class="ev-head-ico"><i class="bi bi-calendar-month"></i></div>
    <div class="min-w-0">
        <h1 class="ev-title">Summary Bulanan</h1>
        <div class="ev-subtitle"><?= $bulanLabel ?> · <?= count($rows) ?> event</div>
    </div>
    <div class="ev-head-act">
        <a href="?bulan=<?= $prevBulan ?>" class="ev-iconbtn" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <form method="GET" class="m-0">
            <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control form-control-sm" style="width:155px" aria-label="Pilih bulan" onchange="this.form.submit()">
        </form>
        <a href="?bulan=<?= $nextBulan ?>" class="ev-iconbtn" title="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>

<!-- Angka utama -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Event</div>
        <div class="ev-kpi-v"><?= count($rows) ?></div>
        <div class="ms-kpi-split">
            <?php $bag = []; foreach (['ewalk','pentacity','keduanya'] as $m) if (!empty($perMall[$m])) $bag[] = $perMall[$m] . ' ' . $mallLabels[$m]; ?>
            <?= $bag ? esc(implode(' · ', $bag)) : 'belum ada event' ?>
        </div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Biaya event</div>
        <div class="ev-kpi-v"><?= $rpKecil($totalBudget) ?></div>
        <div class="ev-kpi-s">dari budget tiap event</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Pendapatan event</div>
        <div class="ev-kpi-v ev-ok"><?= $rpKecil($totalRevenue) ?></div>
        <div class="ev-kpi-s">exhibitor, sponsor, dll.</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Selisih</div>
        <div class="ev-kpi-v <?= $totalProfit >= 0 ? 'ev-cyan' : 'ev-bad' ?>"><?= $totalProfit < 0 ? '−' : '' ?><?= $rpKecil(abs($totalProfit)) ?></div>
        <div class="ev-kpi-s"><?= $roiTotal !== null ? 'pendapatan ' . $roiTotal . '% dari biaya' : 'belum ada biaya tercatat' ?></div>
    </div>
</div>

<?php if (empty($rows) && empty($harian)): ?>
<div class="ev-empty">
    <i class="bi bi-calendar-x"></i>
    <h5>Tidak ada event di bulan <?= $bulanLabel ?></h5>
    <p>Pilih bulan lain dengan panah di atas, atau lihat semua event.</p>
    <a href="<?= base_url('events') ?>" class="ev-btn"><i class="bi bi-calendar-event"></i>Daftar Event</a>
</div>
<?php else: ?>

<!-- Kalender bulan: pengunjung harian + pita event -->
<div class="ev-panel">
    <div class="ev-panel-h">
        <h2 class="ev-panel-t"><i class="bi bi-calendar3-range"></i>Pengunjung harian &amp; event</h2>
        <div class="ev-panel-end ms-legend">
            <span><i style="background:var(--c-ewalk,#3b82f6)"></i>eWalk <?= $monthTrafficEwalk > 0 ? '· ' . number_format($monthTrafficEwalk) : '' ?></span>
            <span><i style="background:var(--c-penta,#10b981)"></i>Pentacity <?= $monthTrafficPenta > 0 ? '· ' . number_format($monthTrafficPenta) : '' ?></span>
            <span><i style="background:repeating-linear-gradient(135deg,#94a3b8 0 3px,transparent 3px 6px)"></i>tbc</span>
        </div>
    </div>
    <div class="ev-panel-b ms-scroll">
        <div class="ms-cal" style="--n:<?= $nHari ?>">
            <div class="ms-bg ms-grid" aria-hidden="true">
                <div></div>
                <?php foreach ($hariBulan as $tgl): $dw = (int) date('w', strtotime($tgl)); ?>
                <div class="<?= $tgl === $hariIni ? 'now' : (($dw === 0 || $dw === 6) ? 'wk' : '') ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="ms-grid ms-traffic" role="img" aria-label="Pengunjung harian eWalk dan Pentacity bulan <?= esc($bulanLabel, 'attr') ?>">
                <div class="ms-lab">Pengunjung/hari<small>tertinggi <?= number_format($maks) ?></small></div>
                <?php foreach ($hariBulan as $tgl):
                    $ew = (int) ($harian[$tgl]['ewalk'] ?? 0); $pc = (int) ($harian[$tgl]['pentacity'] ?? 0); ?>
                <div class="ms-day" title="<?= esc(tgl_indo_dm($tgl), 'attr') ?> · eWalk <?= number_format($ew) ?> · Pentacity <?= number_format($pc) ?>">
                    <span class="ew" style="height:<?= round($ew / $maks * 100, 1) ?>%"></span>
                    <span class="pc" style="height:<?= round($pc / $maks * 100, 1) ?>%"></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ms-grid ms-dates" aria-hidden="true">
                <div></div>
                <?php foreach ($hariBulan as $tgl): $dw = (int) date('w', strtotime($tgl)); ?>
                <div class="d <?= ($dw === 0 || $dw === 6) ? 'red' : '' ?>"><b><?= (int) date('j', strtotime($tgl)) ?></b><?= $HARI1[$dw] ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($rows as $r):
                $ev   = $r['event'];
                $mall = $ev['mall'] ?? 'keduanya';
                $mulaiCol = $ev['start_date'] < $hariBulan[0] ? 1 : $colOf($ev['start_date']);
                $akhirCol = $r['endDate'] > end($hariBulan) ? $nHari : $colOf($r['endDate']);
                $cls = 'ms-bar m-' . esc($mall, 'attr')
                     . ($ev['start_date'] < $hariBulan[0] ? ' cut-l' : '')
                     . ($r['endDate'] > end($hariBulan) ? ' cut-r' : '')
                     . (stripos($ev['name'], '(tbc)') !== false ? ' tbc' : '');
                $url = base_url('events/' . $ev['id'] . '/summary');
            ?>
            <div class="ms-grid ms-ev">
                <div class="ms-lab"><a href="<?= $url ?>" title="<?= esc($ev['name'], 'attr') ?>"><?= esc($ev['name']) ?></a></div>
                <a href="<?= $url ?>" class="<?= $cls ?>" style="grid-column:<?= $mulaiCol + 1 ?> / <?= $akhirCol + 2 ?>"
                   title="<?= esc($ev['name'] . ' · ' . ($mallLabels[$mall] ?? $mall) . ' · ' . tgl_indo_dm($ev['start_date']) . ' – ' . tgl_indo_dm($r['endDate']), 'attr') ?>">
                    <?= ($akhirCol - $mulaiCol) >= 3 ? esc($mallLabels[$mall] ?? $mall) : '' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($rows)): ?>
<!-- Rincian per event -->
<div class="ev-panel">
    <div class="ev-panel-h">
        <h2 class="ev-panel-t"><i class="bi bi-table"></i>Rincian per event</h2>
    </div>
    <div class="ev-tablewrap">
        <table class="ev-table">
            <thead>
                <tr>
                    <th>Event</th><th>Tanggal</th>
                    <th class="r">Biaya</th><th class="r">Pendapatan</th><th class="r">Selisih</th>
                    <th class="r" title="Rata-rata pengunjung per hari selama event, di mal tempat event">Pengunjung/hari</th>
                    <th class="r" title="Dibanding rata-rata hari lain di bulan yang sama, mal yang sama">vs hari lain</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $ev = $r['event']; $mall = $ev['mall'] ?? 'keduanya';
                $profit = $r['profit'];
                $roi = $r['budget'] > 0 ? round($r['revenue'] / $r['budget'] * 100) : null;
                $up  = $r['uplift'];
                $upCls = $up === null ? 'flat' : ($up >= 5 ? 'pos' : ($up <= -5 ? 'neg' : 'flat'));
                $url = base_url('events/' . $ev['id'] . '/summary');
            ?>
            <tr class="is-click" data-href="<?= $url ?>">
                <td class="ev-wrap" style="min-width:220px">
                    <a href="<?= $url ?>" class="fw-semibold" style="color:inherit"><?= esc($ev['name']) ?></a>
                    <span class="ev-sub">
                        <span class="ev-chip ev-chip-<?= $mlChip[$mall] ?? 'mute' ?>"><?= esc($mallLabels[$mall] ?? $mall) ?></span>
                        <span class="ev-chip ev-chip-<?= $stChip[$ev['status']] ?? 'mute' ?>"><?= esc($statusLabels[$ev['status']] ?? $ev['status']) ?></span>
                    </span>
                </td>
                <td class="nw"><?= tgl_indo_dm($ev['start_date']) ?> – <?= tgl_indo_dm($r['endDate']) ?><span class="ev-sub"><?= (int) $ev['event_days'] ?> hari</span></td>
                <td class="r nw"><?= $r['budget'] > 0 ? $rp($r['budget']) : '<span class="ev-mute">—</span>' ?></td>
                <td class="r nw"><?= $r['revenue'] > 0 ? '<span class="ev-ok">' . $rp($r['revenue']) . '</span>' : '<span class="ev-mute">—</span>' ?></td>
                <td class="r nw">
                    <?php if ($r['budget'] > 0 || $r['revenue'] > 0): ?>
                    <b class="<?= $profit >= 0 ? 'ev-cyan' : 'ev-bad' ?>"><?= $profit < 0 ? '−' : '' ?><?= $rp(abs($profit)) ?></b>
                    <?php if ($roi !== null): ?>
                    <span class="ms-roi" title="Pendapatan <?= $roi ?>% dari biaya"><i style="width:<?= min(100, $roi) ?>%;background:<?= $roi >= 100 ? '#22c55e' : ($roi >= 60 ? '#f2c14e' : '#ef4444') ?>"></i></span>
                    <span class="ev-sub"><?= $roi ?>% dari biaya</span>
                    <?php endif; ?>
                    <?php else: ?><span class="ev-mute">—</span><?php endif; ?>
                </td>
                <td class="r nw">
                    <?= $r['avgSelama'] !== null ? number_format($r['avgSelama']) : '<span class="ev-mute">—</span>' ?>
                    <?php if ($r['hariData'] > 0 && $r['hariData'] < min((int) $ev['event_days'], $nHari)): ?><span class="ev-sub"><?= $r['hariData'] ?> hari ada data</span><?php endif; ?>
                </td>
                <td class="r nw">
                    <?php if ($up !== null): ?>
                    <span class="ms-up <?= $upCls ?>" title="Rata-rata hari lain: <?= number_format((int) $r['avgLain']) ?>/hari"><?= $up > 0 ? '+' : '' ?><?= str_replace('.', ',', (string) $up) ?>%</span>
                    <?php else: ?>
                    <span class="ev-mute" title="<?= $r['avgSelama'] !== null ? 'Event berjalan sepanjang bulan: tidak ada hari lain untuk pembanding' : 'Belum ada data pengunjung' ?>">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="r nw"><?= $rp($totalBudget) ?></td>
                    <td class="r nw ev-ok"><?= $rp($totalRevenue) ?></td>
                    <td class="r nw <?= $totalProfit >= 0 ? 'ev-cyan' : 'ev-bad' ?>"><?= $totalProfit < 0 ? '−' : '' ?><?= $rp(abs($totalProfit)) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<p class="ms-note">Pengunjung dihitung di mal tempat event (event "eWalk &amp; Pentacity" memakai jumlah kedua mal). "vs hari lain" membandingkan rata-rata per hari selama event dengan hari lain di bulan yang sama; event di akhir pekan cenderung lebih tinggi.</p>
<?php endif; ?>
<?php endif; ?>

</div><!-- /page-evs -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-summary.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
