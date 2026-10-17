<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/sponsorship-index.css') ?>?v=<?= @filemtime(FCPATH . 'css/sponsorship-index.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$dealStatusLabel = [
    'prospek'       => 'Prospek',
    'negosiasi'     => 'Negosiasi',
    'terkonfirmasi' => 'Terkonfirmasi',
    'lunas'         => 'Lunas',
    'batal'         => 'Batal',
];
$kategoriOptions = ['Platinum', 'Gold', 'Silver', 'Bronze', 'Media Partner', 'In-kind', 'Lainnya'];
$mallLabel = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk + Pentacity'];

if (! function_exists('spFmt')) {
    function spFmt(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
}
if (! function_exists('spPct')) {
    function spPct(int $actual, int $target): float {
        return $target > 0 ? min(100, round($actual / $target * 100, 1)) : 0;
    }
}
// Ringkas: 97.628.000 -> "97,6 jt"
if (! function_exists('spShort')) {
    function spShort(int $n): string {
        if ($n >= 1000000000) return 'Rp ' . number_format($n / 1e9, 1, ',', '.') . ' M';
        if ($n >= 1000000)    return 'Rp ' . number_format($n / 1e6, 1, ',', '.') . ' jt';
        return spFmt($n);
    }
}

$activePrograms   = array_filter($programs, fn($p) => $p['status'] === 'active');
$inactivePrograms = array_filter($programs, fn($p) => $p['status'] === 'inactive');

// Sisa belum terkumpul (program aktif; program yang sudah lewat target tidak menutupi program lain)
$totalSisa = 0;
foreach ($activePrograms as $p) {
    $totalSisa += max(0, (int)(($committedMap[$p['id']]['total_nilai'] ?? 0)) - (int)($realisasiMap[$p['id']] ?? 0));
}
$collRateAll = spPct($totalNilaiTerkumpul, $totalNilaiCommitted);

// Pilihan tahun dari tanggal mulai
$yearOpts = [];
foreach ($programs as $p) {
    if (! empty($p['tanggal_mulai'])) $yearOpts[date('Y', strtotime($p['tanggal_mulai']))] = true;
}
krsort($yearOpts);
?>

<div class="page-spon-idx" id="sponIdx">

<!-- Header -->
<div class="si-head">
    <div class="si-head-ico"><i class="bi bi-trophy-fill"></i></div>
    <div class="min-w-0">
        <h1 class="si-title">Program Sponsorship</h1>
        <div class="si-subtitle">Kelola deal sponsor, realisasi penerimaan, dan tracking target</div>
    </div>
    <div class="si-head-act">
        <a href="<?= base_url('sponsorship/summary') ?>" class="si-btn"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline">Summary Bulanan</span></a>
        <?php if ($canEdit): ?>
        <button class="si-btn si-btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="bi bi-plus-lg"></i>Tambah Program
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Strip KPI (program aktif) -->
<div class="si-kpis">
    <div class="si-kpi">
        <div class="si-kpi-l">Program Aktif</div>
        <div class="si-kpi-v"><?= $activeCount ?></div>
        <div class="si-kpi-s">dari <?= count($programs) ?> program</div>
    </div>
    <div class="si-kpi">
        <div class="si-kpi-l">Sponsor Terkonfirmasi</div>
        <div class="si-kpi-v"><?= $totalSponsorCount ?></div>
        <div class="si-kpi-s"><?= $targetSponsor > 0 ? 'target ' . $targetSponsor . ' sponsor' : 'belum ada target' ?></div>
    </div>
    <div class="si-kpi">
        <div class="si-kpi-l">Nilai Deal</div>
        <div class="si-kpi-v" title="<?= spFmt($totalNilaiCommitted) ?>"><?= spFmt($totalNilaiCommitted) ?></div>
        <div class="si-kpi-s">Cash <?= spFmt($totalNilaiCash) ?><?php if ($totalNilaiBarang > 0): ?> · Barang <?= spFmt($totalNilaiBarang) ?><?php endif; ?><?php if ($targetNilai > 0): ?> · target <?= spFmt($targetNilai) ?><?php endif; ?></div>
    </div>
    <div class="si-kpi">
        <div class="si-kpi-l">Terkumpul</div>
        <div class="si-kpi-v si-c-cyan" title="<?= spFmt($totalNilaiTerkumpul) ?>"><?= spFmt($totalNilaiTerkumpul) ?></div>
        <div class="si-kpi-s"><?= $totalNilaiCommitted > 0 ? $collRateAll . '% dari nilai deal' : 'belum ada deal' ?></div>
        <?php if ($totalNilaiCommitted > 0): ?><div class="si-bar"><i style="width:<?= $collRateAll ?>%"></i></div><?php endif; ?>
    </div>
    <div class="si-kpi">
        <div class="si-kpi-l">Sisa Belum Terkumpul</div>
        <div class="si-kpi-v <?= $totalSisa > 0 ? 'si-c-warn' : 'si-c-ok' ?>" title="<?= spFmt($totalSisa) ?>"><?= spFmt($totalSisa) ?></div>
        <div class="si-kpi-s">deal dikurangi realisasi</div>
    </div>
</div>

<?php if (! empty($bolehDobel) && ! empty($dobel)):
    $dblBelum = (int)($dobel['n_belum'] ?? 0); $dblPutus = (int)($dobel['n_keputusan'] ?? 0); $dblGrup = count(array_filter($dobel['grup'], fn($g) => $g['n_belum'] > 0));
    if ($dblBelum > 0 || $dblPutus > 0): ?>
<a href="<?= base_url('sponsorship/summary#dobel') ?>" class="si-dbl-bar<?= $dblBelum > 0 ? ' is-todo' : '' ?>" id="siDblBar">
    <i class="bi bi-files"></i>
    <span class="si-dbl-tx">
        <?php if ($dblBelum > 0): ?>
        <b><?= $dblBelum ?> kemungkinan sponsor dobel</b> antara program mandiri dan event (<?= $dblGrup ?> pasangan program × event) menunggu keputusan tim.
        <?php else: ?>
        <b>Tidak ada kemungkinan dobel yang menunggu.</b>
        <?php endif; ?>
        <?php if ($dblPutus > 0): ?><small><?= $dblPutus ?> keputusan tercatat</small><?php endif; ?>
    </span>
    <span class="si-dbl-go">Tinjau di Summary <i class="bi bi-arrow-right"></i></span>
</a>
<?php endif; endif; ?>

<?php if (empty($programs)): ?>
<div class="si-empty">
    <i class="bi bi-trophy"></i>
    <h5>Belum ada program sponsorship</h5>
    <p>Buat program pertama, lalu catat sponsor dan realisasi penerimaannya di sini.</p>
    <?php if ($canEdit): ?>
    <button class="si-btn si-btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
        <i class="bi bi-plus-lg"></i>Buat Program Pertama
    </button>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Pencarian & filter -->
<div class="si-tools" id="siTools">
    <div class="si-search">
        <i class="bi bi-search"></i>
        <input type="search" id="siQ" placeholder="Cari program atau nama sponsor…" autocomplete="off" aria-label="Cari program atau sponsor">
    </div>
    <select class="si-select" id="siFStatus" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="active">Aktif</option>
        <option value="inactive">Tidak aktif</option>
        <option value="locked">Terkunci</option>
    </select>
    <select class="si-select" id="siFYear" aria-label="Filter tahun">
        <option value="">Semua tahun</option>
        <?php foreach (array_keys($yearOpts) as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
    </select>
    <select class="si-select" id="siFMall" aria-label="Filter mall">
        <option value="">Semua mall</option>
        <option value="ewalk">eWalk</option>
        <option value="pentacity">Pentacity</option>
        <option value="both">Keduanya</option>
    </select>
    <select class="si-select" id="siFJenis" aria-label="Filter jenis sponsor">
        <option value="">Semua jenis</option>
        <option value="cash">Ada sponsor Cash</option>
        <option value="barang">Ada sponsor Barang</option>
    </select>
    <select class="si-select" id="siSort" aria-label="Urutkan">
        <option value="">Urutan: terbaru</option>
        <option value="nama">Nama A–Z</option>
        <option value="deal">Nilai deal terbesar</option>
        <option value="sisa">Sisa terbesar</option>
        <option value="pct">Progres terendah</option>
    </select>
    <div class="si-tools-end">
        <span class="si-count" id="siCount" aria-live="polite"></span>
        <button type="button" class="si-btn si-btn-sm" id="siExpand" title="Buka semua program"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="si-btn si-btn-sm" id="siCollapse" title="Tutup semua program"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<?php foreach ([['active', 'Aktif', $activePrograms], ['inactive', 'Tidak Aktif', $inactivePrograms]] as [$statusKey, $statusLabel, $progList]):
    if (empty($progList)) continue; ?>

<section class="si-group" data-group="<?= $statusKey ?>">
<div class="si-group-h">
    <span class="si-pill <?= $statusKey === 'active' ? 'si-pill-on' : 'si-pill-off' ?>"><?= $statusLabel ?></span>
    <span class="si-group-n"><?= count($progList) ?> program</span>
</div>
<div class="si-list">

<?php foreach ($progList as $prog):
    $pid          = $prog['id'];
    $spList       = $sponsors[$pid] ?? [];
    $committed    = $committedMap[$pid] ?? ['total_nilai' => 0, 'total_sponsor' => 0];
    $dealNilai    = (int)($committed['total_nilai'] ?? 0);
    $terkumpul    = (int)($realisasiMap[$pid] ?? 0);
    $collPct      = spPct($terkumpul, $dealNilai);
    $sisa         = $dealNilai - $terkumpul;
    $isLocked     = (bool)($prog['locked'] ?? false);
    $canEditProg  = $canEdit && ! $isLocked;
    $isActive     = $prog['status'] === 'active';
    $targetNilaiP = (int)($prog['target_nilai'] ?? 0);
    $year         = ! empty($prog['tanggal_mulai']) ? date('Y', strtotime($prog['tanggal_mulai'])) : '';
    $jenisSet     = array_values(array_unique(array_map(fn($s) => $s['jenis'] === 'cash' ? 'cash' : 'barang', $spList)));
    $searchText   = mb_strtolower($prog['nama_program'] . ' ' . implode(' ', array_column($spList, 'nama_sponsor')));
    $barCls       = $collPct >= 100 ? 'ok' : ($collPct >= 50 ? 'mid' : 'low');
    $evS          = $prog['eval_status'] ?? '';
?>
<article class="si-prog<?= $isActive ? '' : ' is-off' ?>" id="program-<?= $pid ?>"
         data-pid="<?= $pid ?>"
         data-name="<?= esc($prog['nama_program'], 'attr') ?>"
         data-search="<?= esc($searchText, 'attr') ?>"
         data-status="<?= $prog['status'] ?>" data-locked="<?= $isLocked ? 1 : 0 ?>"
         data-year="<?= $year ?>" data-mall="<?= esc($prog['mall'] ?? '', 'attr') ?>"
         data-jenis="<?= implode(',', $jenisSet) ?>"
         data-deal="<?= $dealNilai ?>" data-sisa="<?= $sisa ?>" data-pct="<?= $collPct ?>"
         data-order="<?= $pid ?>">
    <div class="si-prog-head" data-si-toggle>
        <button type="button" class="si-chev" aria-expanded="false" aria-controls="body-<?= $pid ?>" aria-label="Buka atau tutup rincian program"><i class="bi bi-chevron-right"></i></button>
        <div class="si-prog-main">
            <div class="si-prog-name">
                <span class="si-prog-t"><?= esc($prog['nama_program']) ?></span>
                <?php if ($isLocked): ?><span class="si-chip si-chip-bad"><i class="bi bi-lock-fill"></i>Terkunci</span><?php endif; ?>
                <?php if ($isLocked && $evS):
                    $evColor = ['berhasil'=>'ok','sebagian'=>'warn','gagal'=>'bad'][$evS] ?? 'mute';
                    $evIcon  = ['berhasil'=>'check-circle-fill','sebagian'=>'dash-circle-fill','gagal'=>'x-circle-fill'][$evS] ?? 'lock-fill';
                    $evLabel = ['berhasil'=>'Berhasil','sebagian'=>'Sebagian','gagal'=>'Perlu Perbaikan'][$evS] ?? $evS; ?>
                <span class="si-chip si-chip-<?= $evColor ?>"><i class="bi bi-<?= $evIcon ?>"></i><?= esc($evLabel) ?></span>
                <?php endif; ?>
                <?php if (! $isActive): ?><span class="si-chip si-chip-mute">Tidak aktif</span><?php endif; ?>
                <?php if (! empty($prog['mall'])): ?><span class="si-chip si-chip-blue"><i class="bi bi-buildings"></i><?= esc($mallLabel[$prog['mall']] ?? $prog['mall']) ?></span><?php endif; ?>
            </div>
            <div class="si-prog-meta">
                <?php if ($prog['tanggal_mulai']): ?>
                <span><i class="bi bi-calendar3"></i><?= date('d M Y', strtotime($prog['tanggal_mulai'])) ?><?php if ($prog['tanggal_selesai']): ?> – <?= date('d M Y', strtotime($prog['tanggal_selesai'])) ?><?php endif; ?></span>
                <?php endif; ?>
                <span><i class="bi bi-people"></i><?= (int)$committed['total_sponsor'] ?> terkonfirmasi<?= $prog['target_sponsor'] ? ' / target ' . (int)$prog['target_sponsor'] : '' ?> · <?= count($spList) ?> sponsor total</span>
                <?php if ($targetNilaiP > 0): ?>
                <span><i class="bi bi-bullseye"></i>Target <?= spFmt($targetNilaiP) ?><?php if ($dealNilai > 0): ?> · deal <?= round($dealNilai / $targetNilaiP * 100) ?>%<?php endif; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="si-prog-fig">
            <div class="si-fig-row">
                <div><span class="si-fig-l">Nilai deal</span><b><?= spFmt($dealNilai) ?></b></div>
                <div><span class="si-fig-l">Terkumpul</span><b class="si-c-cyan"><?= spFmt($terkumpul) ?></b></div>
                <div><span class="si-fig-l"><?= $sisa < 0 ? 'Lebih' : 'Sisa' ?></span><b class="<?= $sisa > 0 ? 'si-c-warn' : 'si-c-ok' ?>"><?= spFmt(abs($sisa)) ?></b></div>
            </div>
            <div class="si-prog-bar">
                <div class="si-bar si-bar-<?= $barCls ?>"><i style="width:<?= $collPct ?>%"></i></div>
                <span class="si-pct"><?= $dealNilai > 0 ? $collPct . '%' : '—' ?></span>
            </div>
        </div>
        <div class="si-prog-act" data-si-notoggle>
            <?php if ($canEditProg): ?>
            <button class="si-btn si-btn-sm si-btn-soft" data-bs-toggle="modal" data-bs-target="#addSponsorModal"
                    data-pid="<?= $pid ?>" data-nama="<?= esc($prog['nama_program'], 'attr') ?>" title="Tambah sponsor ke program ini">
                <i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Sponsor</span>
            </button>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <div class="dropdown">
                <button class="si-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi program"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end si-menu">
                <?php if ($isLocked): ?>
                    <?php if ($user['role'] === 'admin'): ?>
                    <li>
                        <form method="post" action="<?= base_url('sponsorship/' . $pid . '/unlock') ?>">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-unlock"></i>Buka kunci</button>
                        </form>
                    </li>
                    <?php else: ?>
                    <li><span class="dropdown-item-text small text-muted">Program terkunci. Hanya admin yang dapat membuka kunci.</span></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#editProgramModal"
                                data-id="<?= $pid ?>"
                                data-nama="<?= esc($prog['nama_program'], 'attr') ?>"
                                data-mall="<?= esc($prog['mall'] ?? '', 'attr') ?>"
                                data-mulai="<?= $prog['tanggal_mulai'] ?? '' ?>"
                                data-selesai="<?= $prog['tanggal_selesai'] ?? '' ?>"
                                data-deskripsi="<?= esc($prog['deskripsi'] ?? '', 'attr') ?>"
                                data-target-sponsor="<?= $prog['target_sponsor'] ?? '' ?>"
                                data-target-nilai="<?= $prog['target_nilai'] ?? '' ?>"
                                data-catatan="<?= esc($prog['catatan'] ?? '', 'attr') ?>"><i class="bi bi-pencil"></i>Edit program</button>
                    </li>
                    <li>
                        <form method="post" action="<?= base_url('sponsorship/' . $pid . '/toggle') ?>">
                            <?= csrf_field() ?>
                            <button class="dropdown-item"><i class="bi bi-<?= $isActive ? 'pause-circle' : 'play-circle' ?>"></i><?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#lockProgramModal"
                                data-id="<?= $pid ?>" data-nama="<?= esc($prog['nama_program'], 'attr') ?>"><i class="bi bi-lock"></i>Kunci program</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= base_url('sponsorship/' . $pid . '/delete') ?>"
                              onsubmit="return confirm('Hapus program ini beserta semua data sponsor dan realisasinya?')">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus program</button>
                        </form>
                    </li>
                <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="si-prog-body" id="body-<?= $pid ?>" hidden>
        <?php if ($prog['deskripsi']): ?>
        <p class="si-desc"><?= esc($prog['deskripsi']) ?></p>
        <?php endif; ?>

        <?php if (! empty($spList)): ?>
        <div class="si-sp-head" aria-hidden="true">
            <span>Sponsor</span><span>Kategori · Jenis</span><span class="si-r">Nilai deal</span><span>Terkumpul</span><span>Status</span><span></span>
        </div>
        <?php foreach ($spList as $sp):
            $spReal    = $realisasi[$sp['id']] ?? ['total_nilai' => 0, 'entries' => []];
            $items     = $itemsBySponsors[$sp['id']] ?? [];
            $nilaiDeal = (int)$sp['nilai'];
            $nilaiReal = (int)$spReal['total_nilai'];
            $lunasPenuh = $nilaiDeal > 0 && $nilaiReal >= $nilaiDeal;
            $spPctVal   = $nilaiDeal > 0 ? min(100, round($nilaiReal / $nilaiDeal * 100)) : 0;
            $entries    = $spReal['entries'] ?? [];
            // Tanggal input sponsor -> durasi sampai realisasi terpenuhi
            $inputDate   = ! empty($sp['created_at']) ? substr($sp['created_at'], 0, 10) : null;
            $fulfillDate = null;
            if ($nilaiDeal > 0 && ! empty($entries)) {
                $ents = $entries;
                usort($ents, fn($a, $b) => strcmp($a['tanggal'] ?? '', $b['tanggal'] ?? ''));
                $cum = 0;
                foreach ($ents as $en) {
                    $cum += (int)$en['nilai'];
                    if ($cum >= $nilaiDeal) { $fulfillDate = $en['tanggal']; break; }
                }
            }
        ?>
        <div class="si-sp" id="sponsor-<?= $sp['id'] ?>">
            <div class="si-sp-row">
                <div class="si-sp-name" data-l="Sponsor">
                    <div class="si-sp-n"><?= esc($sp['nama_sponsor']) ?>
                        <?php if (! empty($bolehDobel) && in_array((int)$sp['id'], $exProgSp ?? [], true)): ?>
                        <span class="si-chip si-chip-warn" title="Dikecualikan dari Summary gabungan karena dikonfirmasi dobel"><i class="bi bi-files"></i>dobel · dicatat di <?= esc($labelKecProg[(int)$sp['id']] ?? 'sisi lain') ?></span>
                        <?php endif; ?></div>
                    <?php if ($sp['detail']): ?><div class="si-sp-d"><?= esc($sp['detail']) ?></div><?php endif; ?>
                    <div class="si-sp-d"><i class="bi bi-person"></i>PIC: <?= esc($sp['pic_nama'] ?? '—') ?></div>
                    <?php if (! empty($items)): ?>
                    <div class="si-items">
                        <?php foreach ($items as $item):
                            $lineTotal = (int)$item['nilai'] * max(1, (int)($item['qty'] ?? 1)); ?>
                        <span class="si-item"><?= esc($item['deskripsi_barang']) ?>
                            <?php if ($item['qty']): ?>×<?= $item['qty'] ?> @ <?= spFmt((int)$item['nilai']) ?> = <?= spFmt($lineTotal) ?><?php else: ?>— <?= spFmt((int)$item['nilai']) ?><?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="si-sp-kat" data-l="Kategori · Jenis">
                    <?php if ($sp['kategori']): ?><span class="si-chip si-chip-mute"><?= esc($sp['kategori']) ?></span><?php endif; ?>
                    <span class="si-chip si-chip-jenis"><?= esc(ucfirst($sp['jenis'])) ?></span>
                </div>
                <div class="si-sp-val si-r" data-l="Nilai deal"><b><?= spFmt($nilaiDeal) ?></b></div>
                <div class="si-sp-col" data-l="Terkumpul">
                    <div class="si-sp-colh">
                        <b class="<?= $lunasPenuh ? 'si-c-ok' : 'si-c-cyan' ?>"><?= spFmt($nilaiReal) ?></b>
                        <?php if ($lunasPenuh): ?><i class="bi bi-check-circle-fill si-c-ok" title="Realisasi sudah mencapai nilai deal"></i>
                        <?php elseif ($nilaiDeal > 0 && $nilaiReal > 0): ?><span class="si-sp-pct"><?= round($nilaiReal / $nilaiDeal * 100) ?>%</span><?php endif; ?>
                    </div>
                    <?php if ($nilaiDeal > 0): ?><div class="si-bar si-bar-<?= $lunasPenuh ? 'ok' : ($nilaiReal > 0 ? 'mid' : 'low') ?>"><i style="width:<?= $spPctVal ?>%"></i></div><?php endif; ?>
                    <?php if ($inputDate): ?>
                    <div class="si-sp-d"><i class="bi bi-calendar-plus"></i><?= date('d/m/y', strtotime($inputDate)) ?>
                        <?php if ($fulfillDate): $days = max(0, (int)floor((strtotime($fulfillDate) - strtotime($inputDate)) / 86400)); ?>
                        → <span class="si-c-ok"><?= date('d/m/y', strtotime($fulfillDate)) ?> (<?= $days ?> hari)</span>
                        <?php else: ?>· <span class="si-c-warn">belum terpenuhi</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="si-sp-st" data-l="Status"><span class="si-deal si-deal-<?= esc($sp['status_deal']) ?>"><?= $dealStatusLabel[$sp['status_deal']] ?? esc($sp['status_deal']) ?></span></div>
                <div class="si-sp-act">
                    <?php if (! empty($entries)): ?>
                    <button type="button" class="si-btn si-btn-xs" data-si-real="<?= $sp['id'] ?>" aria-expanded="false" title="Lihat riwayat realisasi">
                        <i class="bi bi-receipt"></i><?= count($entries) ?><i class="bi bi-chevron-down si-caret"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($canEditProg): ?>
                    <button class="si-btn si-btn-xs si-btn-soft-ok" title="Input realisasi"
                            data-bs-toggle="modal" data-bs-target="#addRealisasiModal"
                            data-pid="<?= $pid ?>" data-spid="<?= $sp['id'] ?>" data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>">
                        <i class="bi bi-plus-circle"></i>Realisasi
                    </button>
                    <button class="si-iconbtn si-iconbtn-sm" title="Edit sponsor" aria-label="Edit sponsor"
                            data-bs-toggle="modal" data-bs-target="#editSponsorModal"
                            data-pid="<?= $pid ?>"
                            data-id="<?= $sp['id'] ?>"
                            data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>"
                            data-kategori="<?= esc($sp['kategori'] ?? '', 'attr') ?>"
                            data-jenis="<?= esc($sp['jenis'], 'attr') ?>"
                            data-nilai="<?= $sp['nilai'] ?>"
                            data-status="<?= esc($sp['status_deal'], 'attr') ?>"
                            data-detail="<?= esc($sp['detail'] ?? '', 'attr') ?>"
                            data-catatan="<?= esc($sp['catatan'] ?? '', 'attr') ?>"
                            data-items="<?= esc(json_encode($itemsBySponsors[$sp['id']] ?? []), 'attr') ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" action="<?= base_url('sponsorship/' . $pid . '/sponsor/' . $sp['id'] . '/delete') ?>"
                          class="d-inline" onsubmit="return confirm('Hapus sponsor ini beserta realisasinya?')">
                        <?= csrf_field() ?>
                        <button class="si-iconbtn si-iconbtn-sm si-iconbtn-bad" title="Hapus sponsor" aria-label="Hapus sponsor"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (! empty($entries)): ?>
            <div class="si-reals" id="reals-<?= $sp['id'] ?>" hidden>
                <?php foreach ($entries as $r): ?>
                <div class="si-real">
                    <span class="si-real-d"><i class="bi bi-calendar-check"></i><?= date('d/m/Y', strtotime($r['tanggal'])) ?></span>
                    <b class="si-c-cyan"><?= spFmt((int)$r['nilai']) ?></b>
                    <?php if ($r['file_bukti']): ?>
                    <a href="<?= base_url('sponsorship/' . $pid . '/file/' . $r['file_bukti']) ?>" target="_blank" class="si-real-f"><i class="bi bi-paperclip"></i>Bukti</a>
                    <?php endif; ?>
                    <span class="si-real-p" title="PIC pengisi realisasi"><i class="bi bi-person"></i><?= esc($r['pengisi'] ?? '—') ?></span>
                    <?php if ($canEditProg): ?>
                    <form method="post" action="<?= base_url('sponsorship/' . $pid . '/sponsor/' . $sp['id'] . '/realisasi/' . $r['id'] . '/delete') ?>"
                          class="ms-auto" onsubmit="return confirm('Hapus entri realisasi ini?')">
                        <?= csrf_field() ?>
                        <button class="si-iconbtn si-iconbtn-sm si-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri realisasi"><i class="bi bi-x-lg"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php
        $footCash   = array_sum(array_map(fn($s) => $s['jenis'] === 'cash' ? (int)$s['nilai'] : 0, $spList));
        $footBarang = array_sum(array_map(fn($s) => $s['jenis'] !== 'cash' ? (int)$s['nilai'] : 0, $spList));
        ?>
        <div class="si-foot">
            <span class="si-foot-l">Total semua sponsor</span>
            <span class="si-foot-v"><?= spFmt(array_sum(array_column($spList, 'nilai'))) ?>
                <?php if ($footCash > 0 && $footBarang > 0): ?><small>Cash <?= spFmt($footCash) ?> · Barang <?= spFmt($footBarang) ?></small><?php endif; ?></span>
            <span class="si-foot-v si-c-cyan"><?= spFmt($terkumpul) ?><small>terkumpul</small></span>
        </div>
        <?php else: ?>
        <div class="si-nosp">
            <i class="bi bi-people"></i> Belum ada sponsor terdaftar.
            <?php if ($canEditProg): ?>
            <button class="si-btn si-btn-sm si-btn-soft" data-bs-toggle="modal" data-bs-target="#addSponsorModal"
                    data-pid="<?= $pid ?>" data-nama="<?= esc($prog['nama_program'], 'attr') ?>"><i class="bi bi-plus-lg"></i>Tambah sponsor pertama</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>

</div>
</section>
<?php endforeach; ?>

<div class="si-empty si-empty-filter" id="siNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada program yang cocok</h5>
    <p>Ubah kata kunci atau longgarkan filter.</p>
    <button type="button" class="si-btn si-btn-sm" id="siReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<?php endif; ?>

<?php if (! empty($eventAggs)):
    $evTotN = array_sum(array_map(fn($e) => (int)$e['jumlah_sponsor'], $eventAggs));
    $evTotDeal = array_sum(array_map(fn($e) => (int)$e['deal'], $eventAggs));
    $evTotKum = array_sum(array_map(fn($e) => (int)$e['kumulatif'], $eventAggs)); ?>
<!-- Sponsor dari Event (baca-saja) -->
<section class="si-ev" id="sponsorEvent" aria-labelledby="siEvT">
<div class="si-group-h">
    <span class="si-pill si-pill-ev" id="siEvT"><i class="bi bi-calendar-event"></i> Sponsor dari Event</span>
    <span class="si-group-n"><?= count($eventAggs) ?> event · <?= $evTotN ?> sponsor · deal <?= spShort($evTotDeal) ?> · terkumpul <?= spShort($evTotKum) ?></span>
    <span class="si-group-n si-ev-ro"><i class="bi bi-eye"></i> hanya baca — kelola di tab Sponsorship masing-masing event</span>
</div>
<div class="si-list">
<?php foreach ($eventAggs as $e):
    $eid = (int)$e['event_id']; $rows = $rincianE[$eid] ?? [];
    if (empty($bolehDobel)) $rows = array_values(array_filter($rows, fn($x) => $x['dobel'] === null));
    $dealE = (int)$e['deal']; $kumE = (int)$e['kumulatif']; $pctE = spPct($kumE, $dealE);
    $malE = $mallLabel[$e['event_mall'] ?? ''] ?? ucfirst((string)($e['event_mall'] ?? ''));
?>
<article class="si-ev-card" id="event-<?= $eid ?>">
    <div class="si-ev-head" data-si-evtoggle>
        <button type="button" class="si-chev" aria-expanded="false" aria-controls="evbody-<?= $eid ?>" aria-label="Buka atau tutup sponsor event"><i class="bi bi-chevron-right"></i></button>
        <div class="si-ev-main">
            <div class="si-ev-name"><span class="si-chip si-chip-warn">Event</span> <span class="si-prog-t"><?= esc($e['event_name']) ?></span></div>
            <div class="si-ev-meta">
                <?php if ($malE !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($malE) ?></span><?php endif; ?>
                <?php if (! empty($e['event_start_date'])): ?><span><i class="bi bi-calendar3"></i><?= date('j M Y', strtotime($e['event_start_date'])) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="si-ev-stats">
            <div><span>Sponsor</span><b><?= (int)$e['jumlah_sponsor'] ?></b></div>
            <div><span>Nilai deal</span><b title="<?= spFmt($dealE) ?>"><?= spShort($dealE) ?></b></div>
            <div><span>Terkumpul</span><b class="si-c-cyan" title="<?= spFmt($kumE) ?>"><?= spShort($kumE) ?></b></div>
            <div class="si-ev-pct"><span><?= $pctE ?>%</span><div class="si-bar"><i style="width:<?= $pctE ?>%"></i></div></div>
        </div>
        <a href="<?= base_url('events/' . $eid . '/sponsors') ?>" class="si-btn si-btn-sm" title="Buka tab Sponsorship event ini"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-md-inline">Tab Sponsorship</span></a>
    </div>
    <div class="si-ev-body" id="evbody-<?= $eid ?>" hidden>
        <?php if (empty($rows)): ?>
        <div class="si-nosp"><i class="bi bi-people"></i> Belum ada sponsor.</div>
        <?php else: ?>
        <div class="si-ev-scroll"><table class="si-ev-table">
            <thead><tr><th>Sponsor</th><th>Jenis</th><th>Qty / Deskripsi</th><th class="si-r">Nilai</th><th class="si-r">Terkumpul</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $x): ?>
            <tr<?= $x['dobel'] !== null ? ' class="is-dobel"' : '' ?>>
                <td><b><?= esc($x['nama']) ?></b><?php if ($x['dobel'] !== null): ?> <span class="si-chip si-chip-warn"><i class="bi bi-files"></i>dobel · dicatat di <?= esc($x['dobel']) ?></span><?php endif; ?></td>
                <td><?= $x['jenis'] === 'cash' ? 'Cash' : 'Barang' ?></td>
                <td class="si-ev-d"><?= $x['rincian'] !== '' ? esc($x['rincian']) : '—' ?></td>
                <td class="si-r"><?= spFmt($x['nilai']) ?></td>
                <td class="si-r"><?= $x['real'] > 0 ? spFmt($x['real']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</div><!-- /.page-spon-idx -->

<!-- ── Modals ── -->

<!-- Add Program -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= base_url('sponsorship/add') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Program Sponsorship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
                            <input type="text" name="nama_program" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Mall</label>
                            <select name="mall" class="form-select form-select-sm">
                                <option value="">— Pilih Mall —</option>
                                <option value="ewalk">eWalk</option>
                                <option value="pentacity">Pentacity</option>
                                <option value="both">Keduanya</option>
                            </select>
                            <div class="form-text" style="font-size:.68rem">Dipakai untuk rekap program per mall di Laporan Bulanan</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Jumlah Sponsor</label>
                            <input type="number" name="target_sponsor" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Nilai (Rp)</label>
                            <input type="text" name="target_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="mis: 100.000.000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi / Mekanisme</label>
                            <textarea name="deskripsi" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning text-white">Simpan Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Program -->
<div class="modal fade" id="editProgramModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="editProgramForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program Sponsorship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
                            <input type="text" name="nama_program" id="ep_nama" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Mall</label>
                            <select name="mall" id="ep_mall" class="form-select form-select-sm">
                                <option value="">— Pilih Mall —</option>
                                <option value="ewalk">eWalk</option>
                                <option value="pentacity">Pentacity</option>
                                <option value="both">Keduanya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="ep_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="ep_selesai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Jumlah Sponsor</label>
                            <input type="number" name="target_sponsor" id="ep_target_sponsor" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Nilai (Rp)</label>
                            <input type="text" name="target_nilai" id="ep_target_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="ep_deskripsi" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" id="ep_catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Sponsor -->
<div class="modal fade" id="addSponsorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="addSponsorForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Sponsor — <span id="as_prog_nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Sponsor / Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sponsor" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select form-select-sm">
                                <option value="">— Pilih —</option>
                                <?php foreach ($kategoriOptions as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Sponsorship</label>
                            <select name="jenis" class="form-select form-select-sm" id="as_jenis" onchange="toggleAsItems(this.value)">
                                <option value="cash">Cash</option>
                                <option value="barang">Barang / In-kind</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="as_nilai_wrap">
                            <label class="form-label small fw-semibold">Nilai (Rp)</label>
                            <input type="text" name="nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="mis: 50.000.000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Deal</label>
                            <select name="status_deal" class="form-select form-select-sm">
                                <option value="prospek">Prospek</option>
                                <option value="negosiasi">Negosiasi</option>
                                <option value="terkonfirmasi">Terkonfirmasi</option>
                                <option value="lunas">Lunas</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detail / Keterangan Deal</label>
                            <textarea name="detail" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <!-- Barang items -->
                        <div class="col-12" id="as_items_wrap" style="display:none">
                            <label class="form-label small fw-semibold">Rincian Barang / In-kind</label>
                            <div id="as_items_list">
                                <div class="row g-2 mb-2 as-item-row">
                                    <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
                                    <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty"></div>
                                    <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAsItem(this)"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="addAsItem()" style="font-size:.75rem">
                                <i class="bi bi-plus me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Sponsor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sponsor -->
<div class="modal fade" id="editSponsorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="editSponsorForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sponsor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Sponsor <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sponsor" id="es_nama" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" id="es_kategori" class="form-select form-select-sm">
                                <option value="">— Pilih —</option>
                                <?php foreach ($kategoriOptions as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jenis</label>
                            <select name="jenis" id="es_jenis" class="form-select form-select-sm" onchange="toggleEsItems(this.value)">
                                <option value="cash">Cash</option>
                                <option value="barang">Barang / In-kind</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="es_nilai_wrap">
                            <label class="form-label small fw-semibold">Nilai (Rp)</label>
                            <input type="text" name="nilai" id="es_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric">
                        </div>
                        <div class="col-12" id="es_items_wrap" style="display:none">
                            <label class="form-label small fw-semibold">Rincian Barang</label>
                            <div id="es_items_list">
                                <div class="row g-2 mb-2 es-item-row">
                                    <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
                                    <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1"></div>
                                    <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEsItem(this)"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary mt-1" style="font-size:.75rem;padding:2px 8px" onclick="addEsItem()">
                                <i class="bi bi-plus me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Deal</label>
                            <select name="status_deal" id="es_status" class="form-select form-select-sm">
                                <option value="prospek">Prospek</option>
                                <option value="negosiasi">Negosiasi</option>
                                <option value="terkonfirmasi">Terkonfirmasi</option>
                                <option value="lunas">Lunas</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detail</label>
                            <textarea name="detail" id="es_detail" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" id="es_catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Realisasi -->
<div class="modal fade" id="addRealisasiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="addRealisasiForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Input Realisasi — <span id="ar_nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nilai Diterima (Rp) <span class="text-danger">*</span></label>
                            <input type="text" name="nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" required placeholder="mis: 25.000.000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Upload Bukti (opsional)</label>
                            <input type="file" name="file_bukti" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Realisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lock Program Modal -->
<div class="modal fade" id="lockProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="lockProgramForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-lock me-1"></i>Kunci Program — <span id="lp_nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Program yang dikunci tidak bisa diedit lagi. Isi evaluasi (ACT) sebelum mengunci.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Evaluasi</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eval_status" id="lp_ev_berhasil" value="berhasil">
                                <label class="form-check-label text-success" for="lp_ev_berhasil"><i class="bi bi-check-circle-fill me-1"></i>Berhasil</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eval_status" id="lp_ev_sebagian" value="sebagian">
                                <label class="form-check-label text-warning" for="lp_ev_sebagian"><i class="bi bi-dash-circle-fill me-1"></i>Sebagian</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="eval_status" id="lp_ev_gagal" value="gagal">
                                <label class="form-check-label text-danger" for="lp_ev_gagal"><i class="bi bi-x-circle-fill me-1"></i>Perlu Perbaikan</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="lp_eval_kendala">Kendala / Catatan Evaluasi</label>
                        <textarea name="eval_kendala" id="lp_eval_kendala" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold" for="lp_eval_rekomendasi">Rekomendasi / Tindak Lanjut</label>
                        <textarea name="eval_rekomendasi" id="lp_eval_rekomendasi" class="form-control form-control-sm" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning text-white">Kunci Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/sponsorship-index.js') ?>?v=<?= @filemtime(FCPATH . 'js/sponsorship-index.js') ?: 1 ?>"></script>
<script>
function numFmt(val) {
    const digits = String(val).replace(/\D/g, '');
    return digits === '' ? '' : parseInt(digits, 10).toLocaleString('id-ID');
}

// Live formatter — static + dynamic rows via delegation
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('num-fmt')) return;
    const el  = e.target;
    const pos = el.selectionStart;
    const old = el.value;
    const formatted = numFmt(old);
    el.value = formatted;
    // adjust cursor: account for added/removed dots
    const diff = formatted.length - old.length;
    el.setSelectionRange(pos + diff, pos + diff);
});

// Edit Program modal
document.getElementById('editProgramModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    this.querySelector('#ep_nama').value          = b.dataset.nama || '';
    this.querySelector('#ep_mall').value          = b.dataset.mall || '';
    this.querySelector('#ep_mulai').value         = b.dataset.mulai || '';
    this.querySelector('#ep_selesai').value       = b.dataset.selesai || '';
    this.querySelector('#ep_deskripsi').value     = b.dataset.deskripsi || '';
    this.querySelector('#ep_target_sponsor').value= b.dataset.targetSponsor || '';
    this.querySelector('#ep_target_nilai').value  = numFmt(b.dataset.targetNilai || '');
    this.querySelector('#ep_catatan').value       = b.dataset.catatan || '';
    document.getElementById('editProgramForm').action = '<?= base_url('sponsorship/') ?>' + b.dataset.id + '/edit';
});

// Add Sponsor modal
document.getElementById('addSponsorModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('as_prog_nama').textContent = b.dataset.nama || '';
    document.getElementById('addSponsorForm').action = '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/add';
    document.getElementById('as_jenis').value = 'cash';
    toggleAsItems('cash');
});

// Edit Sponsor modal
document.getElementById('editSponsorModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    const jenis = b.dataset.jenis || 'cash';
    this.querySelector('#es_nama').value    = b.dataset.nama || '';
    this.querySelector('#es_kategori').value= b.dataset.kategori || '';
    this.querySelector('#es_jenis').value   = jenis;
    this.querySelector('#es_nilai').value   = numFmt(b.dataset.nilai || '');
    this.querySelector('#es_status').value  = b.dataset.status || 'prospek';
    this.querySelector('#es_detail').value  = b.dataset.detail || '';
    this.querySelector('#es_catatan').value = b.dataset.catatan || '';
    document.getElementById('editSponsorForm').action =
        '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/' + b.dataset.id + '/edit';

    // Populate items
    const list = document.getElementById('es_items_list');
    list.innerHTML = '';
    let items = [];
    try { items = JSON.parse(b.dataset.items || '[]'); } catch(e) {}
    if (items.length === 0) items = [{}];
    items.forEach(item => {
        const row = esItemRow();
        row.querySelector('input[name="deskripsi_barang[]"]').value = item.deskripsi_barang || '';
        row.querySelector('input[name="qty[]"]').value              = item.qty || '';
        row.querySelector('input[name="nilai_item[]"]').value       = numFmt(item.nilai || '');
        list.appendChild(row);
    });
    toggleEsItems(jenis);
});

function esItemRow() {
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 es-item-row';
    div.innerHTML = `
        <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
        <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1"></div>
        <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEsItem(this)"><i class="bi bi-x"></i></button></div>`;
    return div;
}

function toggleEsItems(val) {
    document.getElementById('es_items_wrap').style.display = val === 'barang' ? '' : 'none';
    document.getElementById('es_nilai_wrap').style.display = val === 'cash'   ? '' : 'none';
}
function addEsItem() {
    document.getElementById('es_items_list').appendChild(esItemRow());
}
function removeEsItem(btn) {
    const rows = document.querySelectorAll('.es-item-row');
    if (rows.length > 1) btn.closest('.es-item-row').remove();
}

// Add Realisasi modal
document.getElementById('addRealisasiModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('ar_nama').textContent = b.dataset.nama || '';
    document.getElementById('addRealisasiForm').action =
        '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/' + b.dataset.spid + '/realisasi/add';
});

function toggleAsItems(val) {
    document.getElementById('as_items_wrap').style.display = val === 'barang' ? '' : 'none';
    document.getElementById('as_nilai_wrap').style.display = val === 'cash'   ? '' : 'none';
}

function addAsItem() {
    const row = document.querySelector('.as-item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('as_items_list').appendChild(row);
}

function removeAsItem(btn) {
    const rows = document.querySelectorAll('.as-item-row');
    if (rows.length > 1) btn.closest('.as-item-row').remove();
}

// Lock Program modal
document.getElementById('lockProgramModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    this.querySelector('#lp_nama').textContent = b.dataset.nama || '';
    const form = document.getElementById('lockProgramForm');
    form.action = '<?= base_url('sponsorship/') ?>' + b.dataset.id + '/lock';
    form.querySelectorAll('input[name=eval_status]').forEach(r => r.checked = false);
    form.querySelector('#lp_eval_kendala').value = '';
    form.querySelector('#lp_eval_rekomendasi').value = '';
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
