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

/* ── Eco news hover ── */
.eco-news-item:hover .text-body { color: var(--bs-primary) !important; }

/* ── Traffic card bar ── */
.traffic-bar {
    height: 4px;
    border-radius: 2px;
    transform-origin: left;
    transform: scaleX(0);
    transition: transform .8s cubic-bezier(.22,.68,0,1.1);
}
.traffic-bar.animate { transform: scaleX(1); }

/* ── News skeleton shimmer ── */
@keyframes shimmer {
    0%   { background-position: -400px 0; }
    100% { background-position:  400px 0; }
}
.news-skel {
    background: linear-gradient(90deg, var(--bs-secondary-bg) 25%, var(--bs-tertiary-bg) 50%, var(--bs-secondary-bg) 75%);
    background-size: 800px 100%;
    animation: shimmer 1.4s ease-in-out infinite;
    border-radius: 4px;
}
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

<!-- ══ Economic Snapshot ═══════════════════════════════════════════════════ -->
<?php
$eco = $economicData;
function fmtRp(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
?>
<div class="card mb-4 fade-up" style="animation-delay:.52s">
<div class="card-header d-flex align-items-center justify-content-between py-2">
    <div class="fw-semibold small">
        <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Kondisi Ekonomi
    </div>
    <span class="text-muted small" id="ecoRefreshedAt"></span>
</div>
<div class="card-body">
<div class="row g-4">

<!-- Col 1: Kurs Valuta (live) + Indikator Makro -->
<div class="col-12 col-md-4">

    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-currency-exchange me-1"></i>Kurs Valuta
        <span class="badge bg-success-subtle text-success border ms-1" style="font-size:.58rem;vertical-align:middle">LIVE</span>
    </div>

    <div id="kursLoading" class="text-muted small mb-3 d-flex align-items-center gap-2">
        <span class="spinner-border spinner-border-sm"></span>Memuat kurs...
    </div>
    <table id="kursTable" class="table table-sm mb-3" style="display:none;font-size:.82rem">
    <tbody>
        <tr><td class="text-muted pe-2">🇺🇸 USD/IDR</td><td id="k-usd" class="fw-bold text-end"></td><td id="k-usd-chg" class="text-end" style="font-size:.72rem;min-width:52px"></td></tr>
        <tr><td class="text-muted pe-2">🇪🇺 EUR/IDR</td><td id="k-eur" class="fw-bold text-end"></td><td id="k-eur-chg" class="text-end" style="font-size:.72rem"></td></tr>
        <tr><td class="text-muted pe-2">🇸🇬 SGD/IDR</td><td id="k-sgd" class="fw-bold text-end"></td><td id="k-sgd-chg" class="text-end" style="font-size:.72rem"></td></tr>
        <tr><td class="text-muted pe-2">🇯🇵 JPY/IDR</td><td id="k-jpy" class="fw-bold text-end"></td><td id="k-jpy-chg" class="text-end" style="font-size:.72rem"></td></tr>
    </tbody>
    </table>
    <div id="kursError" class="text-danger small mb-3" style="display:none">
        <i class="bi bi-exclamation-circle me-1"></i>Gagal memuat kurs.
    </div>

    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-bank me-1"></i>Indikator Makro
    </div>
    <div class="d-flex flex-column gap-2">
        <?php
        $indicators = [
            ['label' => 'BI Rate (7-Day RRR)',      'key' => 'bi_rate',   'color' => 'text-primary'],
            ['label' => 'Inflasi YoY',              'key' => 'inflation', 'color' => 'text-info'],
            ['label' => 'Pertumbuhan Ekonomi 🇮🇩',  'key' => 'gdp',       'color' => 'text-success'],
            ['label' => 'PDRB Balikpapan 🏙️',       'key' => 'gdp_bpn',  'color' => 'text-success'],
        ];
        foreach ($indicators as $ind):
            $d = $eco[$ind['key']];
        ?>
        <div class="d-flex align-items-center justify-content-between rounded border px-3 py-2" style="background:var(--bs-tertiary-bg)">
            <div>
                <div style="font-size:.7rem" class="text-muted d-flex align-items-center gap-1">
                    <?= $ind['label'] ?>
                    <?php if (! empty($d['live'])): ?>
                    <span class="badge bg-success-subtle text-success border" style="font-size:.55rem">LIVE</span>
                    <?php endif; ?>
                </div>
                <div class="fw-bold <?= $ind['color'] ?>" style="font-size:1.1rem"><?= $d['pct'] ?>%</div>
            </div>
            <div class="text-end text-muted" style="font-size:.68rem"><?= esc($d['per']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Col 2: Harga BBM -->
<div class="col-12 col-md-3">
    <div class="small fw-semibold text-muted text-uppercase mb-2 d-flex align-items-center justify-content-between" style="letter-spacing:.05em">
        <span><i class="bi bi-fuel-pump me-1"></i>Harga BBM Pertamina</span>
        <?php if ($user['role'] === 'admin'): ?>
        <button class="btn btn-outline-secondary border-0 p-0 px-1" style="font-size:.7rem"
                data-bs-toggle="modal" data-bs-target="#bbmModal" title="Update harga BBM">
            <i class="bi bi-pencil-square"></i>
        </button>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-column gap-1 mb-2">
    <?php foreach ($eco['bbm'] as $b): ?>
    <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1" style="font-size:.8rem;background:var(--bs-tertiary-bg)">
        <div>
            <span><?= esc($b['nama']) ?></span>
            <?php if ($b['subsidi']): ?>
            <span class="badge bg-warning-subtle text-warning border ms-1" style="font-size:.6rem">PSO</span>
            <?php endif; ?>
        </div>
        <span class="fw-bold ms-2 text-nowrap"><?= fmtRp($b['harga']) ?></span>
    </div>
    <?php endforeach; ?>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3" style="font-size:.68rem">
        <span class="text-muted"><i class="bi bi-info-circle me-1"></i>per <?= esc($eco['bbm_per']) ?> · harga per liter</span>
        <a href="https://pertaminapatraniaga.com/page/harga-terbaru-bbm" target="_blank" rel="noopener"
           class="text-primary text-decoration-none" title="Cek harga terbaru di Pertamina Patra Niaga">
            <i class="bi bi-box-arrow-up-right me-1"></i>Cek terbaru
        </a>
    </div>

    <div id="bbmNewsSection">
    <?php if (! empty($bbmNews)): ?>
    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-newspaper me-1"></i>Berita BBM Terkini
    </div>
    <div class="d-flex flex-column gap-1">
    <?php foreach ($bbmNews as $bn): ?>
    <a href="<?= esc($bn['link']) ?>" target="_blank" rel="noopener"
       class="text-decoration-none border rounded px-2 py-1 eco-news-item" style="background:var(--bs-tertiary-bg)">
        <div class="text-body" style="font-size:.75rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= esc($bn['title']) ?>
        </div>
        <?php if ($bn['date_fmt']): ?>
        <div class="text-muted mt-1" style="font-size:.65rem">
            <i class="bi bi-clock me-1"></i>
            <?php if ($bn['age_min'] !== null && $bn['age_min'] < 60): ?>
                <?= $bn['age_min'] ?> mnt lalu
            <?php elseif ($bn['age_min'] !== null && $bn['age_min'] < 1440): ?>
                <?= floor($bn['age_min'] / 60) ?> jam lalu
            <?php else: ?>
                <?= $bn['date_fmt'] ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-lightning-charge me-1"></i>Dampak BBM ke Mall
    </div>
    <div class="d-flex flex-column gap-1" style="font-size:.78rem">
        <div class="d-flex gap-2 align-items-start">
            <span class="text-warning">⚡</span>
            <span class="text-muted">Logistik tenant naik → tekanan harga jual</span>
        </div>
        <div class="d-flex gap-2 align-items-start">
            <span class="text-primary">🚗</span>
            <span class="text-muted">Mobilitas pengunjung naik → potensi turun traffic</span>
        </div>
        <div class="d-flex gap-2 align-items-start">
            <span class="text-success">🏪</span>
            <span class="text-muted">Operasional mall sensitif terhadap harga solar</span>
        </div>
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- Col 3: Berita (tabbed) -->
<div class="col-12 col-md-5">
    <ul class="nav nav-tabs nav-tabs-sm border-bottom mb-2" style="font-size:.78rem" role="tablist">
        <li class="nav-item">
            <button class="nav-link active px-2 py-1" data-bs-toggle="tab" data-bs-target="#tabEkoNas">
                <i class="bi bi-globe me-1"></i>Ekonomi Nasional
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-2 py-1" data-bs-toggle="tab" data-bs-target="#tabBpn">
                🏙️ Balikpapan
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabEkoNas">
            <div class="text-muted mb-2" style="font-size:.65rem">
                <i class="bi bi-arrow-clockwise me-1"></i>diperbarui tiap pagi
                · CNBC · CNN · Detik Finance · ANTARA
            </div>
            <div id="newsEkoContent" class="d-flex flex-column gap-2">
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
            </div>
        </div>
        <div class="tab-pane fade" id="tabBpn">
            <div class="text-muted mb-2" style="font-size:.65rem">
                <i class="bi bi-arrow-clockwise me-1"></i>diperbarui tiap pagi
                · IniBalikpapan · Tribun Kaltim
            </div>
            <div id="newsBpnContent" class="d-flex flex-column gap-2">
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
                <div class="news-skel" style="height:36px"></div>
            </div>
        </div>
    </div>
</div>

</div><!-- /row -->

<!-- ── Insight Ekonomi ──────────────────────────────────────────────────── -->
<?php
$bi   = $eco['bi_rate'];
$infl = $eco['inflation'];
$gdp  = $eco['gdp'];
$gpbn = $eco['gdp_bpn'];

$biNum   = (float)str_replace(',', '.', $bi['pct']);
$inflNum = (float)str_replace(',', '.', $infl['pct']);
$gdpNum  = (float)str_replace(',', '.', $gdp['pct']);
$gpbnNum = (float)str_replace(',', '.', $gpbn['pct']);

// Ekstrak harga BBM dari database
$bbmMap = [];
foreach ($eco['bbm'] as $b) {
    $key = mb_strtolower(preg_replace('/\s+/', '_', $b['nama']));
    $bbmMap[$key] = $b['harga'];
}
$hrgDexlite   = $bbmMap['dexlite']          ?? 0;   // non-subsidi solar, patokan logistik
$hrgDex       = $bbmMap['pertamina_dex']    ?? 0;
$hrgPertalite = $bbmMap['pertalite_ron_90'] ?? 0;   // subsidi, patokan mobilitas rakyat
$hrgPertamax  = $bbmMap['pertamax_ron_92']  ?? 0;

// Sinyal gabungan
$logistikBerat  = $hrgDexlite > 20000;  // solar non-subsidi sangat mahal → tekanan supply chain
$mobilityOk     = $hrgPertalite <= 10500; // Pertalite stabil → daya beli transportasi terjaga
$cicilanMurah   = $biNum < 5.5;
$dayaBeliOk     = $inflNum < 3 && $mobilityOk;
$ekonomiTumbuh  = $gdpNum > 5 && $gpbnNum > 5;

// Scoring per sektor — kombinasi BBM + makro
$sectors = [
    [
        'nama'  => 'F&B / Kuliner',
        'trend' => $logistikBerat ? 'down' : ($dayaBeliOk ? 'up' : 'flat'),
        'reason'=> $logistikBerat
            ? 'Dexlite Rp ' . number_format($hrgDexlite,0,',','.') . '/L → biaya distribusi bahan baku naik signifikan'
            : ($dayaBeliOk ? 'Inflasi rendah & Pertalite stabil jaga daya beli konsumen' : 'Tekanan inflasi kurangi frekuensi makan di luar'),
    ],
    [
        'nama'  => 'Fashion & Lifestyle',
        'trend' => $dayaBeliOk && $gdpNum > 5 ? 'up' : ($logistikBerat ? 'flat' : 'flat'),
        'reason'=> $dayaBeliOk && $gdpNum > 5
            ? 'Ekonomi tumbuh ' . $gdp['pct'] . '%, daya beli terjaga → belanja non-primer naik'
            : 'Biaya logistik tinggi tekan margin tenant fashion',
    ],
    [
        'nama'  => 'Elektronik & Gadget',
        'trend' => $cicilanMurah && ! $logistikBerat ? 'up' : ($cicilanMurah ? 'flat' : 'down'),
        'reason'=> $cicilanMurah
            ? 'BI Rate ' . $bi['pct'] . '% permudah cicilan; ' . ($logistikBerat ? 'namun logistik impor tertekan BBM' : 'logistik relatif aman')
            : 'Suku bunga tinggi rem pembelian barang mahal',
    ],
    [
        'nama'  => 'Properti & Dekorasi',
        'trend' => $cicilanMurah ? 'up' : 'down',
        'reason'=> $cicilanMurah
            ? 'BI Rate ' . $bi['pct'] . '% dorong KPR & renovasi; efek IKN perkuat demand di Balikpapan'
            : 'Suku bunga tinggi hambat kredit properti & renovasi',
    ],
    [
        'nama'  => 'Jasa & Hiburan',
        'trend' => $gpbnNum > 6 && $mobilityOk ? 'up' : 'flat',
        'reason'=> $gpbnNum > 6 && $mobilityOk
            ? 'PDRB Balikpapan ' . $gpbn['pct'] . '% + Pertalite stabil → mobilitas pengunjung terjaga'
            : 'Pertumbuhan lokal moderat atau biaya mobilitas mulai terasa',
    ],
    [
        'nama'  => 'Logistik & Distribusi',
        'trend' => $logistikBerat ? 'down' : 'flat',
        'reason'=> $logistikBerat
            ? 'Dexlite Rp ' . number_format($hrgDexlite,0,',','.') . ' & Pertamina Dex Rp ' . number_format($hrgDex,0,',','.') . '/L — tekanan besar pada armada truk & kapal'
            : 'Harga solar non-subsidi dalam batas wajar',
    ],
    [
        'nama'  => 'Otomotif & Aksesori',
        'trend' => $cicilanMurah && $ekonomiTumbuh ? 'up' : ($logistikBerat ? 'flat' : 'flat'),
        'reason'=> $cicilanMurah && $ekonomiTumbuh
            ? 'Cicilan murah + IKN dorong demand kendaraan; meski BBM non-subsidi mahal'
            : 'Harga BBM non-subsidi tinggi kurangi minat kendaraan diesel',
    ],
];

$upSectors   = array_filter($sectors, fn($s) => $s['trend'] === 'up');
$downSectors = array_filter($sectors, fn($s) => $s['trend'] === 'down');
$flatSectors = array_filter($sectors, fn($s) => $s['trend'] === 'flat');
?>
<div class="border-top pt-3 mt-1">
    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-lightbulb me-1 text-warning"></i>Insight Ekonomi
        <span class="fw-normal text-lowercase ms-1" style="font-size:.65rem">berdasarkan indikator saat ini</span>
    </div>
    <div class="row g-2">
        <?php if (! empty($upSectors)): ?>
        <div class="col-12 col-sm-4">
            <div class="rounded border px-2 py-2" style="background:rgba(16,185,129,.07);border-color:rgba(16,185,129,.3)!important">
                <div class="small fw-semibold text-success mb-1"><i class="bi bi-arrow-up-circle-fill me-1"></i>Menguat</div>
                <?php foreach ($upSectors as $s): ?>
                <div class="mb-1">
                    <div style="font-size:.78rem" class="fw-semibold"><?= $s['nama'] ?></div>
                    <div style="font-size:.68rem" class="text-muted"><?= $s['reason'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (! empty($downSectors)): ?>
        <div class="col-12 col-sm-4">
            <div class="rounded border px-2 py-2" style="background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.3)!important">
                <div class="small fw-semibold text-danger mb-1"><i class="bi bi-arrow-down-circle-fill me-1"></i>Melemah</div>
                <?php foreach ($downSectors as $s): ?>
                <div class="mb-1">
                    <div style="font-size:.78rem" class="fw-semibold"><?= $s['nama'] ?></div>
                    <div style="font-size:.68rem" class="text-muted"><?= $s['reason'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (! empty($flatSectors)): ?>
        <div class="col-12 col-sm-4">
            <div class="rounded border px-2 py-2" style="background:var(--bs-tertiary-bg)">
                <div class="small fw-semibold text-muted mb-1"><i class="bi bi-dash-circle me-1"></i>Stabil</div>
                <?php foreach ($flatSectors as $s): ?>
                <div class="mb-1">
                    <div style="font-size:.78rem" class="fw-semibold"><?= $s['nama'] ?></div>
                    <div style="font-size:.68rem" class="text-muted"><?= $s['reason'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Daya Beli & Segmen ───────────────────────────────────────────────── -->
<?php
// Tentukan status tiap segmen berdasarkan indikator
// Segmen Bawah: pakai Pertalite, sensitif harga sembako
$segBawahStatus = ($mobilityOk && $inflNum < 3) ? 'terjaga'
    : (($inflNum > 4 || $hrgPertalite > 10500) ? 'tertekan' : 'waspada');

// Segmen Menengah: pakai Pertamax, punya cicilan, belanja di mall
$segMenengahStatus = ($cicilanMurah && $inflNum < 3 && $hrgPertamax < 14000) ? 'membaik'
    : ($logistikBerat && $inflNum > 3 ? 'tertekan' : 'terjaga');

// Segmen Atas: pakai Pertamax Turbo, investasi properti, less price-sensitive
$segAtasStatus = $cicilanMurah ? 'membaik' : 'terjaga';

// UMKM & Logistik: pakai Dexlite/Solar, sangat terdampak kenaikan BBM non-subsidi
$segUmkmStatus = $logistikBerat ? 'tertekan' : 'terjaga';

$statusCfg = [
    'membaik'  => ['color' => 'success', 'icon' => 'bi-arrow-up-circle-fill',   'label' => 'Membaik'],
    'terjaga'  => ['color' => 'primary', 'icon' => 'bi-shield-check-fill',      'label' => 'Terjaga'],
    'waspada'  => ['color' => 'warning', 'icon' => 'bi-exclamation-triangle-fill','label'=> 'Waspada'],
    'tertekan' => ['color' => 'danger',  'icon' => 'bi-arrow-down-circle-fill', 'label' => 'Tertekan'],
];

$segments = [
    [
        'nama'   => 'Kelas Bawah',
        'desc'   => 'Pekerja harian, buruh, pengguna angkutan umum',
        'bbm'    => 'Pertalite Rp ' . number_format($hrgPertalite,0,',','.'),
        'status' => $segBawahStatus,
        'poin'   => [
            $mobilityOk ? '✔ Transportasi: Pertalite stabil, biaya mobilitas ke mall tidak naik' : '✘ Pertalite naik, mobilitas terbebani',
            $inflNum < 3 ? '✔ Inflasi ' . $infl['pct'] . '% — harga kebutuhan pokok masih terkendali' : '✘ Inflasi tinggi gerus daya beli',
            $logistikBerat ? '⚠ Harga barang di warung/pasar berpotensi naik imbas Dexlite mahal' : '✔ Rantai pasok relatif stabil',
        ],
        'spend'  => 'F&B murah, fashion entry-level, kebutuhan sehari-hari',
    ],
    [
        'nama'   => 'Kelas Menengah',
        'desc'   => 'Karyawan swasta/PNS, pengguna Pertamax, punya cicilan',
        'bbm'    => 'Pertamax Rp ' . number_format($hrgPertamax,0,',','.'),
        'status' => $segMenengahStatus,
        'poin'   => [
            $hrgPertamax < 14000 ? '✔ Pertamax Rp ' . number_format($hrgPertamax,0,',','.') . ' — beban BBM relatif ringan' : '⚠ Pertamax cukup mahal',
            $cicilanMurah ? '✔ BI Rate ' . $bi['pct'] . '% — cicilan KPR & kredit lebih ringan' : '⚠ Suku bunga tinggi bebani cicilan',
            $logistikBerat ? '⚠ Harga barang kebutuhan naik imbas biaya distribusi' : '✔ Rantai pasok terjaga',
            $gdpNum > 5 ? '✔ Ekonomi tumbuh ' . $gdp['pct'] . '% — income relatif terjaga' : '',
        ],
        'spend'  => 'F&B mid-range, fashion branded, elektronik cicilan, bioskop',
    ],
    [
        'nama'   => 'Kelas Atas',
        'desc'   => 'Eksekutif, pengusaha, pekerja migas/IKN, investor',
        'bbm'    => 'Pertamax Turbo Rp ' . number_format($hrgPertamax > 0 ? ($bbmMap['pertamax_turbo'] ?? 19900) : 19900, 0, ',', '.'),
        'status' => $segAtasStatus,
        'poin'   => [
            '⚠ Pertamax Turbo Rp ' . number_format($bbmMap['pertamax_turbo'] ?? 19900,0,',','.') . ' — naik, tapi proporsi ke pengeluaran kecil',
            $cicilanMurah ? '✔ BI Rate rendah — investasi properti & bisnis makin menarik' : '⚠ Suku bunga tinggi rem investasi',
            $gpbnNum > 6 ? '✔ PDRB Balikpapan ' . $gpbn['pct'] . '% — aktivitas bisnis lokal bergairah' : '',
            '✔ Efek IKN Nusantara perkuat aktivitas ekonomi di Balikpapan',
        ],
        'spend'  => 'Fine dining, lifestyle, elektronik premium, properti & dekorasi',
    ],
    [
        'nama'   => 'UMKM & Pelaku Usaha',
        'desc'   => 'Tenant mall, pedagang, pengusaha distribusi & logistik',
        'bbm'    => 'Dexlite Rp ' . number_format($hrgDexlite,0,',','.'),
        'status' => $segUmkmStatus,
        'poin'   => [
            $logistikBerat ? '✘ Dexlite Rp ' . number_format($hrgDexlite,0,',','.') . ' & Pertamina Dex Rp ' . number_format($hrgDex,0,',','.') . ' — biaya armada naik drastis' : '✔ Harga solar non-subsidi dalam batas wajar',
            $logistikBerat ? '⚠ Margin tenant tergerus; risiko kenaikan harga jual produk' : '',
            $inflNum < 3 ? '✔ Inflasi rendah bantu jaga volume permintaan konsumen' : '⚠ Inflasi tinggi turunkan volume penjualan',
            $cicilanMurah ? '✔ Kredit usaha lebih murah — peluang ekspansi & restocking' : '',
        ],
        'spend'  => 'Biaya operasional, restocking, sewa & utilitas',
    ],
];
?>
<div class="border-top pt-3 mt-2">
    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
        <i class="bi bi-people-fill me-1 text-primary"></i>Daya Beli & Dampak ke Segmen Pengunjung
    </div>
    <div class="row g-2">
    <?php foreach ($segments as $seg):
        $cfg = $statusCfg[$seg['status']];
    ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="rounded border h-100 px-2 py-2" style="font-size:.78rem">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-bold"><?= $seg['nama'] ?></span>
                <span class="badge bg-<?= $cfg['color'] ?>-subtle text-<?= $cfg['color'] ?> border" style="font-size:.62rem">
                    <i class="bi <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                </span>
            </div>
            <div class="text-muted mb-1" style="font-size:.68rem"><?= $seg['desc'] ?></div>
            <div class="badge bg-secondary-subtle text-secondary border mb-2" style="font-size:.62rem">
                <i class="bi bi-fuel-pump me-1"></i><?= $seg['bbm'] ?>
            </div>
            <div class="d-flex flex-column gap-1 mb-2">
            <?php foreach (array_filter($seg['poin']) as $p): ?>
                <div class="text-muted" style="font-size:.7rem;line-height:1.3"><?= $p ?></div>
            <?php endforeach; ?>
            </div>
            <div class="border-top pt-1 mt-1" style="font-size:.65rem">
                <span class="text-muted"><i class="bi bi-bag me-1"></i><?= $seg['spend'] ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

</div><!-- /card-body -->
</div>
<!-- ══ /Economic Snapshot ══════════════════════════════════════════════════ -->

<!-- Events -->
<div class="card fade-up" style="animation-delay:.60s">
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

<?php if ($user['role'] === 'admin'): ?>
<!-- ══ BBM Update Modal ════════════════════════════════════════════════════ -->
<div class="modal fade" id="bbmModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<form method="POST" action="<?= base_url('dashboard/update-bbm') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h6 class="modal-title fw-semibold"><i class="bi bi-fuel-pump me-2"></i>Update Harga BBM</h6>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Berlaku Per</label>
        <input type="text" name="bbm_per" class="form-control form-control-sm"
               value="<?= esc($eco['bbm_per']) ?>" placeholder="cth: Mei 2025" required>
    </div>
    <div class="small fw-semibold text-muted mb-2">Harga per Liter (Rupiah)</div>
    <div class="d-flex flex-column gap-2" id="bbmRows">
    <?php foreach ($eco['bbm'] as $idx => $b): ?>
    <div class="border rounded px-3 py-2 bbm-row" style="background:var(--bs-tertiary-bg)">
        <div class="row g-2 align-items-center">
            <div class="col-5">
                <input type="text" name="nama[]" class="form-control form-control-sm"
                       value="<?= esc($b['nama']) ?>" placeholder="Nama BBM" required>
            </div>
            <div class="col-4">
                <input type="number" name="harga[]" class="form-control form-control-sm"
                       value="<?= $b['harga'] ?>" min="100" step="50" placeholder="Harga" required>
            </div>
            <div class="col-2 text-center">
                <div class="form-check form-check-inline m-0" title="PSO / Subsidi">
                    <input class="form-check-input" type="checkbox" name="subsidi[<?= $idx ?>]"
                           value="1" <?= $b['subsidi'] ? 'checked' : '' ?>>
                    <label class="form-check-label small">PSO</label>
                </div>
            </div>
            <div class="col-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0 px-1 remove-row"
                        title="Hapus baris"><i class="bi bi-x"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addBbmRow">
        <i class="bi bi-plus me-1"></i>Tambah Jenis BBM
    </button>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="bi bi-check-lg me-1"></i>Simpan
    </button>
</div>
</form>
</div>
</div>
</div>
<?php endif; ?>

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

    /* ── Exchange rates (live, no API key) ─────────────────────────────── */
    (async function loadKurs() {
        const API_BASE = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@';
        const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        const chg = (cur, prev) => {
            if (! prev) return '';
            const d = ((cur - prev) / prev) * 100;
            const sign = d > 0 ? '▲' : d < 0 ? '▼' : '─';
            const cls  = d > 0 ? 'text-danger' : d < 0 ? 'text-success' : 'text-muted';
            return `<span class="${cls}">${sign} ${Math.abs(d).toFixed(2)}%</span>`;
        };

        try {
            const yd = new Date(Date.now() - 864e5).toISOString().slice(0, 10);
            const [todayRes, yestRes] = await Promise.all([
                fetch(API_BASE + 'latest/v1/currencies/usd.json').then(r => r.json()),
                fetch(API_BASE + yd + '/v1/currencies/usd.json').then(r => r.json()).catch(() => null),
            ]);

            const t = todayRes.usd;
            const y = yestRes?.usd ?? null;

            // Derive IDR per foreign unit: idr / foreign_usd_rate
            const pairs = [
                { id: 'usd', label: 'usd', idr_t: t.idr,             idr_y: y?.idr             },
                { id: 'eur', label: 'eur', idr_t: t.idr / t.eur,     idr_y: y ? y.idr / y.eur : null },
                { id: 'sgd', label: 'sgd', idr_t: t.idr / t.sgd,     idr_y: y ? y.idr / y.sgd : null },
                { id: 'jpy', label: 'jpy', idr_t: t.idr / t.jpy * 100, idr_y: y ? y.idr / y.jpy * 100 : null, note: '/100' },
            ];

            pairs.forEach(p => {
                const elV = document.getElementById('k-' + p.id);
                const elC = document.getElementById('k-' + p.id + '-chg');
                if (elV) elV.textContent = fmt(p.idr_t) + (p.note ?? '');
                if (elC) elC.innerHTML  = chg(p.idr_t, p.idr_y);
            });

            document.getElementById('kursLoading').style.display = 'none';
            document.getElementById('kursTable').style.display   = '';
            const now = new Date();
            const elR = document.getElementById('ecoRefreshedAt');
            if (elR) elR.textContent = 'Kurs: ' + now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
        } catch (e) {
            document.getElementById('kursLoading').style.display = 'none';
            document.getElementById('kursError').style.display   = '';
        }
    })();

    /* ── News lazy-load ─────────────────────────────────────────────────── */
    function escH(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function renderNewsItems(containerId, items, max) {
        const el = document.getElementById(containerId);
        if (! el) return;
        if (! items || ! items.length) {
            el.innerHTML = '<div class="text-muted small fst-italic"><i class="bi bi-wifi-off me-1"></i>Tidak dapat memuat berita.</div>';
            return;
        }
        const rows = items.slice(0, max).map((n, i, arr) => {
            const border = i < arr.length - 1 ? 'border-bottom' : '';
            const age    = n.age_min;
            const lbl    = age === null ? escH(n.date_fmt)
                         : age < 60    ? age + ' mnt lalu'
                         : age < 1440  ? Math.floor(age / 60) + ' jam lalu'
                         : escH(n.date_fmt);
            return `<a href="${escH(n.link)}" target="_blank" rel="noopener"
                       class="text-decoration-none py-2 ${border} eco-news-item">
                <div class="text-body fw-medium" style="font-size:.82rem;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${escH(n.title)}</div>
                ${lbl ? `<div class="text-muted mt-1" style="font-size:.68rem"><i class="bi bi-clock me-1"></i>${lbl}</div>` : ''}
            </a>`;
        });
        el.innerHTML = '<div class="d-flex flex-column">' + rows.join('') + '</div>';
    }
    function renderBbmNews(items) {
        const el = document.getElementById('bbmNewsSection');
        if (! el || ! items || ! items.length) return;
        const rows = items.map(n => {
            const age = n.age_min;
            const lbl = age === null ? escH(n.date_fmt)
                      : age < 60    ? age + ' mnt lalu'
                      : age < 1440  ? Math.floor(age / 60) + ' jam lalu'
                      : escH(n.date_fmt);
            return `<a href="${escH(n.link)}" target="_blank" rel="noopener"
                       class="text-decoration-none border rounded px-2 py-1 eco-news-item" style="background:var(--bs-tertiary-bg)">
                <div class="text-body" style="font-size:.75rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${escH(n.title)}</div>
                ${lbl ? `<div class="text-muted mt-1" style="font-size:.65rem"><i class="bi bi-clock me-1"></i>${lbl}</div>` : ''}
            </a>`;
        });
        el.innerHTML = `<div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.05em">
            <i class="bi bi-newspaper me-1"></i>Berita BBM Terkini</div>
            <div class="d-flex flex-column gap-1">${rows.join('')}</div>`;
    }

    (async function loadNews() {
        try {
            const res  = await fetch('<?= base_url('dashboard/news-feed') ?>');
            const data = await res.json();
            renderNewsItems('newsEkoContent', data.eco, 7);
            renderNewsItems('newsBpnContent', data.bpn, 7);
            if (data.bbm && data.bbm.length) renderBbmNews(data.bbm);
        } catch (e) {
            renderNewsItems('newsEkoContent', [], 7);
            renderNewsItems('newsBpnContent', [], 7);
        }
    })();

    /* ── BBM modal: add/remove rows ── */
    document.getElementById('addBbmRow')?.addEventListener('click', function () {
        const tpl = document.querySelector('.bbm-row');
        if (! tpl) return;
        const idx  = document.querySelectorAll('.bbm-row').length;
        const clone = tpl.cloneNode(true);
        clone.querySelectorAll('input[type=text], input[type=number]').forEach(i => i.value = '');
        clone.querySelector('input[type=checkbox]').checked = false;
        clone.querySelector('input[type=checkbox]').name = 'subsidi[' + idx + ']';
        document.getElementById('bbmRows').appendChild(clone);
    });
    document.getElementById('bbmRows')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (btn && document.querySelectorAll('.bbm-row').length > 1) btn.closest('.bbm-row').remove();
    });

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
