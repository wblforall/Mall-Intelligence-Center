<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
/* ── Entrance ── */
.fade-up {
    opacity: 0;
    transform: translateY(16px);
    animation: fadeUp .5s cubic-bezier(.22,.68,0,1.1) forwards;
}
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

/* ── Active event pulse dot ── */
@keyframes pulseGreen {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,.55); }
    60%       { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}
.pulse-active { animation: pulseGreen 2s ease-in-out infinite; }

/* ── Event row hover ── */
.event-row { transition: background .15s; }
.event-row:hover { background: rgba(99,102,241,.07); }

/* ── Traffic card bar ── */
.traffic-bar {
    height: 4px;
    border-radius: 2px;
    transform-origin: left;
    transform: scaleX(0);
    transition: transform .8s cubic-bezier(.22,.68,0,1.1);
}
.traffic-bar.animate { transform: scaleX(1); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];

$activeEvents    = array_filter($events, fn($e) => $e['status'] === 'active');
$upcomingEvents  = array_filter($events, fn($e) => $e['status'] === 'draft' && !empty($e['start_date']) && $e['start_date'] > $today);
$draftEvents     = array_filter($events, fn($e) => $e['status'] === 'draft' && (empty($e['start_date']) || $e['start_date'] <= $today));
$completedEvents = array_filter($events, fn($e) => $e['status'] === 'completed');

function daysDiff(string $date, string $today): int {
    return (int) round((strtotime($date) - strtotime($today)) / 86400);
}
function daysLabel(int $d): string {
    if ($d === 0) return 'hari ini';
    if ($d === 1) return 'besok';
    if ($d > 0)  return $d . ' hari lagi';
    return abs($d) . ' hari lalu';
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <small class="text-muted">Selamat datang, <?= esc($user['name']) ?></small>
        <div class="mt-1 d-flex align-items-center gap-2">
            <span id="localDate" class="small fw-semibold text-body"></span>
            <span class="text-muted small">·</span>
            <span id="localTime" class="small text-muted font-monospace"></span>
            <span id="localTz" class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem"></span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

<!-- KPI Events -->
<div class="col-12 col-lg-7">
<div class="row g-3 h-100">
    <div class="col-6 col-sm-3 fade-up" style="animation-delay:.1s">
        <div class="card text-center h-100 py-3">
            <div class="fs-2 fw-bold" data-count="<?= $counts['total'] ?>"><?= $counts['total'] ?></div>
            <div class="small text-muted">Total Events</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 fade-up" style="animation-delay:.17s">
        <div class="card text-center h-100 py-3" style="border-top:3px solid var(--c-ev-active)">
            <div class="fs-2 fw-bold text-success" data-count="<?= $counts['active'] ?>"><?= $counts['active'] ?></div>
            <div class="small text-muted">Aktif</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 fade-up" style="animation-delay:.24s">
        <div class="card text-center h-100 py-3" style="border-top:3px solid var(--c-ev-upcoming)">
            <div class="fs-2 fw-bold text-warning" data-count="<?= $counts['draft'] ?>"><?= $counts['draft'] ?></div>
            <div class="small text-muted">Draft</div>
        </div>
    </div>
    <div class="col-6 col-sm-3 fade-up" style="animation-delay:.31s">
        <div class="card text-center h-100 py-3" style="border-top:3px solid var(--c-ev-past)">
            <div class="fs-2 fw-bold text-secondary" data-count="<?= $counts['completed'] ?>"><?= $counts['completed'] ?></div>
            <div class="small text-muted">Selesai</div>
        </div>
    </div>
</div>
</div>

<!-- Quick Actions -->
<div class="col-12 col-lg-5 fade-up" style="animation-delay:.2s">
<div class="card h-100">
<div class="card-body py-2 px-3">
    <div class="small fw-semibold text-muted mb-2 text-uppercase" style="letter-spacing:.05em">Shortcuts</div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('traffic/input/ewalk/' . $today) ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Input Traffic eWalk
        </a>
        <a href="<?= base_url('traffic/input/pentacity/' . $today) ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-pencil me-1"></i>Input Traffic Pentacity
        </a>
        <a href="<?= base_url('traffic/import') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel
        </a>
        <a href="<?= base_url('traffic/summary') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart-line me-1"></i>Traffic Summary
        </a>
        <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
        <a href="<?= base_url('events') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-event me-1"></i>Semua Event
        </a>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

</div>

<!-- Traffic Snapshot -->
<div class="row g-3 mb-4">

<?php
$trafficMalls = [
    'ewalk'     => ['label'=>'eWalk',     'color'=>'primary', 'hex'=>'#2563eb', 'bg'=>'#eff6ff'],
    'pentacity' => ['label'=>'Pentacity', 'color'=>'success', 'hex'=>'#059669', 'bg'=>'#f0fdf4'],
];
$trafficDelays = ['ewalk' => '.38s', 'pentacity' => '.46s'];
foreach ($trafficMalls as $mall => $cfg):
    $t = $traffic[$mall]; $hasToday = $t['today'] > 0;
    $maxMonth = max($traffic['ewalk']['month'], $traffic['pentacity']['month'], 1);
    $barPct   = $t['month'] > 0 ? min(100, round($t['month'] / $maxMonth * 100)) : 0;
?>
<div class="col-12 col-md-6 fade-up" style="animation-delay:<?= $trafficDelays[$mall] ?>">
<div class="card h-100" style="border-left:4px solid <?= $cfg['hex'] ?>">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <span class="fw-semibold" style="color:<?= $cfg['hex'] ?>">
                <i class="bi bi-building me-1"></i><?= $cfg['label'] ?>
            </span>
            <div class="small text-muted mt-1">
                <?php if ($hasToday): ?>
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Data hari ini sudah diisi</span>
                <?php else: ?>
                <span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum ada data hari ini</span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?= base_url('traffic/input/'.$mall.'/'.$today) ?>" class="btn btn-sm" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['hex'] ?>;border:1px solid <?= $cfg['hex'] ?>">
            <i class="bi bi-pencil"></i>
        </a>
    </div>
    <div class="row g-0 text-center mb-3">
        <div class="col-6 border-end">
            <div class="small text-muted">Hari Ini</div>
            <div class="fw-bold fs-5" style="color:<?= $cfg['hex'] ?>"
                 <?= $hasToday ? 'data-count="'.$t['today'].'"' : '' ?>>
                <?= $hasToday ? number_format($t['today']) : '—' ?>
            </div>
            <div class="small text-muted">pengunjung</div>
        </div>
        <div class="col-6">
            <div class="small text-muted">Bulan Ini</div>
            <div class="fw-bold fs-5"
                 <?= $t['month'] > 0 ? 'data-count="'.$t['month'].'"' : '' ?>>
                <?= $t['month'] > 0 ? number_format($t['month']) : '—' ?>
            </div>
            <div class="small text-muted">
                <?= $t['last_date'] ? 'update ' . date('d M', strtotime($t['last_date'])) : 'belum ada data' ?>
            </div>
        </div>
    </div>
    <?php if ($t['month'] > 0): ?>
    <div class="traffic-bar bg-<?= $cfg['color'] ?>" data-pct="<?= $barPct ?>"></div>
    <?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>

</div>

<!-- Events -->
<div class="card fade-up" style="animation-delay:.52s">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-event me-2"></i>Event</h6>
    <a href="<?= base_url('events') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
</div>
<div class="card-body p-0">

<?php if (empty($events)): ?>
<div class="p-5 text-center text-muted">
    <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
    Belum ada event.
    <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
    <div class="mt-2"><a href="<?= base_url('events/create') ?>">Buat event pertama</a></div>
    <?php endif; ?>
</div>
<?php else: ?>

<?php
$sections = [
    ['events' => $activeEvents,   'label' => 'Berlangsung',  'color' => 'success', 'icon' => 'play-circle-fill'],
    ['events' => $upcomingEvents, 'label' => 'Akan Datang',  'color' => 'primary', 'icon' => 'clock'],
    ['events' => $draftEvents,    'label' => 'Draft',        'color' => 'warning', 'icon' => 'pencil-square'],
    ['events' => $completedEvents,'label' => 'Selesai',      'color' => 'secondary','icon' => 'check-circle'],
];
foreach ($sections as $sec):
    if (empty($sec['events'])) continue;
?>

<div class="px-3 py-2 border-bottom bg-light">
    <span class="small fw-semibold text-<?= $sec['color'] ?>">
        <i class="bi bi-<?= $sec['icon'] ?> me-1"></i><?= $sec['label'] ?>
        <span class="badge bg-<?= $sec['color'] ?>-subtle text-<?= $sec['color'] ?> ms-1"><?= count($sec['events']) ?></span>
    </span>
</div>

<?php foreach ($sec['events'] as $e):
    $startDays = !empty($e['start_date']) ? daysDiff($e['start_date'], $today) : null;
    $endDate   = !empty($e['start_date']) && !empty($e['event_days'])
        ? date('Y-m-d', strtotime($e['start_date'] . ' +' . ($e['event_days'] - 1) . ' days'))
        : null;
    $endDays   = $endDate ? daysDiff($endDate, $today) : null;
    $isActive  = $e['status'] === 'active';
?>
<div class="d-flex align-items-center px-3 py-2 border-bottom gap-3 event-row">

    <!-- Status dot -->
    <div class="flex-shrink-0">
        <span class="badge rounded-pill bg-<?= $sec['color'] ?>-subtle text-<?= $sec['color'] ?> <?= $isActive ? 'pulse-active' : '' ?>"
              style="width:8px;height:8px;padding:4px;border-radius:50%!important;display:inline-block"></span>
    </div>

    <!-- Name + mall -->
    <div class="flex-grow-1 min-w-0">
        <div class="fw-medium text-truncate"><?= esc($e['name']) ?></div>
        <div class="small text-muted">
            <span class="me-2"><?= $mallLabels[$e['mall']] ?? esc($e['mall']) ?></span>
            <?php if (!empty($e['start_date'])): ?>
            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($e['start_date'])) ?>
            <?php if ($e['event_days'] > 1): ?>· <?= $e['event_days'] ?> hari<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contextual time badge -->
    <div class="flex-shrink-0 text-end">
        <?php if ($e['status'] === 'active' && $endDays !== null): ?>
            <?php if ($endDays < 0): ?>
            <span class="badge bg-secondary-subtle text-secondary">Berakhir <?= daysLabel($endDays) ?></span>
            <?php elseif ($endDays === 0): ?>
            <span class="badge bg-danger-subtle text-danger">Berakhir hari ini</span>
            <?php else: ?>
            <span class="badge bg-success-subtle text-success">Berakhir <?= daysLabel($endDays) ?></span>
            <?php endif; ?>
        <?php elseif ($e['status'] === 'draft' && $startDays !== null && $startDays > 0): ?>
            <span class="badge bg-primary-subtle text-primary">Mulai <?= daysLabel($startDays) ?></span>
        <?php elseif ($e['status'] === 'completed' && $endDate): ?>
            <span class="badge bg-secondary-subtle text-secondary"><?= date('d M Y', strtotime($endDate)) ?></span>
        <?php endif; ?>
    </div>

    <!-- Action -->
    <div class="flex-shrink-0">
        <a href="<?= base_url('events/'.$e['id'].'/summary') ?>" class="btn btn-sm btn-outline-<?= $sec['color'] ?>">Buka</a>
    </div>

</div>
<?php endforeach; ?>
<?php endforeach; ?>

<?php endif; ?>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    /* ── Clock ── */
    const elDate = document.getElementById('localDate');
    const elTime = document.getElementById('localTime');
    const elTz   = document.getElementById('localTz');
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    elTz.textContent = tz;
    const fmtDate = new Intl.DateTimeFormat('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric', timeZone:tz });
    const fmtTime = new Intl.DateTimeFormat('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false, timeZone:tz });
    function tick() { const n = new Date(); elDate.textContent = fmtDate.format(n); elTime.textContent = fmtTime.format(n); }
    tick(); setInterval(tick, 1000);

    /* ── Count-up ── */
    function countUp(el, target, delay) {
        const duration = Math.min(1000, 300 + target * 0.4);
        setTimeout(() => {
            const start = performance.now();
            const original = el.textContent;
            const step = (now) => {
                const p = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - p, 3);
                const v = Math.round(ease * target);
                el.textContent = v.toLocaleString('id-ID');
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        }, delay);
    }

    document.querySelectorAll('[data-count]').forEach((el, i) => {
        const target = parseInt(el.dataset.count);
        if (target > 0) countUp(el, target, 200 + i * 60);
    });

    /* ── Traffic bar ── */
    setTimeout(() => {
        document.querySelectorAll('.traffic-bar').forEach(bar => {
            bar.style.width = bar.dataset.pct + '%';
            bar.classList.add('animate');
        });
    }, 600);

    /* ── Event row stagger ── */
    document.querySelectorAll('.event-row').forEach((row, i) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';
        row.style.transition = `opacity .3s ease ${.6 + i * .04}s, transform .3s ease ${.6 + i * .04}s`;
        requestAnimationFrame(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        });
    });
})();
</script>
<?= $this->endSection() ?>
