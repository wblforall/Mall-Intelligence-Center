<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= session('user_theme') === 'light' ? 'light' : 'dark' ?>" data-theme="<?= session('user_theme') === 'light' ? 'light' : 'dark' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Mall Intelligence Center' ?></title>
<link rel="icon" type="image/png" href="<?= base_url('img/mic-logo-sm.png') ?>">
<!-- ── PWA ── -->
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#091528">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MIC">
<link rel="apple-touch-icon" href="<?= base_url('img/apple-touch-icon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= base_url('css/theme.css') ?>?v=2.20.1" rel="stylesheet">
<style>
:root { --sidebar-width: 248px; }
body { min-height: 100vh; }

/* ── Sidebar — layout only, colors live in theme.css ── */
.sidebar {
    width: var(--sidebar-width); height: 100vh;
    position: fixed; top: 0; left: 0; z-index: 1000;
    overflow: hidden; transition: transform .25s ease;
    display: flex; flex-direction: column;
}
.sidebar .brand       { padding: 1.1rem 1.25rem .9rem; }
.sidebar .brand-name  { font-weight: 700; font-size: .85rem; letter-spacing: .01em; white-space: nowrap; }
.sidebar .brand-sub   { font-size: .68rem; margin-top: 2px; }
.sidebar .nav-section { padding: 0 .75rem; margin-top: .5rem; flex: 1 1 0; overflow-y: auto; }
.sidebar .nav-label   { font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; padding: .8rem .5rem .3rem; }
.sidebar .nav-link    { padding: .45rem .75rem; border-radius: .5rem; display: flex; align-items: center; gap: .65rem; font-size: .83rem; transition: background .15s, color .15s; margin-bottom: 1px; }
.sidebar .nav-link i  { width: 16px; text-align: center; font-size: .9rem; flex-shrink: 0; }
.sidebar .subnav      { margin: 2px 0 4px 0; }
.sidebar .subnav .nav-link { padding-left: 1rem; font-size: .8rem; border-left: 2px solid transparent; border-radius: 0 .5rem .5rem 0; margin-left: .25rem; }
.sidebar .sidebar-footer   { flex-shrink: 0; padding: .75rem 1rem; }
.sidebar .sidebar-footer .user-name { font-size: .8rem; font-weight: 500; }
.sidebar .sidebar-footer .user-role { font-size: .7rem; }
.sidebar .sidebar-footer .btn-logout { background: none; border: none; padding: 0; font-size: .8rem; transition: color .15s; }


/* ── Main ── */
.main-content { margin-left: var(--sidebar-width); min-height: 100vh; position: relative; }
.topbar { padding: .6rem 1.5rem; position: sticky; top: 0; z-index: 999; }
.page-content { padding: 1.5rem; }

/* ── KPI cards (always colored) ── */
.kpi-card { border-radius: .75rem; padding: 1.25rem; color: #fff; }
.kpi-card .kpi-label  { font-size: .75rem; opacity: .9; font-weight: 500; }
.kpi-card .kpi-value  { font-size: 1.75rem; font-weight: 700; line-height: 1; }
.kpi-card .kpi-target { font-size: .7rem; opacity: .8; }

/* ── Table sizing ── */
.table th { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.table td { font-size: .875rem; vertical-align: middle; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.show { transform: translateX(0); }
    .main-content { margin-left: 0; }
}
[aria-expanded="true"] #eventsChevron  { transform: rotate(-180deg); }
[aria-expanded="true"] #loyaltyChevron { transform: rotate(-180deg); }
[aria-expanded="true"] #creativeChevron{ transform: rotate(-180deg); }
[aria-expanded="true"] #vmChevron      { transform: rotate(-180deg); }
[aria-expanded="true"] #sponsorChevron { transform: rotate(-180deg); }
#eventsChevron, #loyaltyChevron, #creativeChevron, #vmChevron, #sponsorChevron { transition: transform .2s; }

/* ── Global entrance animation ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { opacity: 0; animation: fadeUp .45s cubic-bezier(.22,.68,0,1.1) forwards; }
</style>
<?= $this->renderSection('styles') ?>
</head>
<body>

<?php $currentUser = session()->get('user_name') ?? ''; $currentRole = session()->get('user_role') ?? ''; ?>

<div class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="brand d-flex align-items-center gap-2">
        <img src="<?= base_url('img/mic-logo-sm.png') ?>" alt="MIC Logo"
             width="36" height="36"
             style="object-fit:contain;flex-shrink:0;border-radius:4px;background:var(--c-logo-bg);padding:2px">
        <div>
            <div class="brand-name">Mall Intelligence Center</div>
            <div class="brand-sub">eWalk & Pentacity</div>
        </div>
    </div>

    <!-- Nav -->
    <div class="nav-section flex-grow-1">

        <?php
        // Mirror persis canViewMenu()/canEditMenu() di BaseController:
        // admin → grant per-user (user_menus, additive) → dept_menus.
        $_navIsAdmin   = session()->get('role_is_admin') || session()->get('user_role') === 'admin';
        $_navUserMenus = session()->get('user_menus');
        $_navDeptMenus = session()->get('dept_menus');
        $navCanView = function (string $key) use ($_navIsAdmin, $_navUserMenus, $_navDeptMenus): bool {
            if ($_navIsAdmin) return true;
            if (isset($_navUserMenus[$key]) && $_navUserMenus[$key]['can_view']) return true;
            if ($_navDeptMenus === null) return false;
            return isset($_navDeptMenus[$key]) && $_navDeptMenus[$key]['can_view'];
        };
        $navCanEdit = function (string $key) use ($_navIsAdmin, $_navUserMenus, $_navDeptMenus): bool {
            if ($_navIsAdmin) return true;
            if (isset($_navUserMenus[$key]) && $_navUserMenus[$key]['can_edit']) return true;
            if ($_navDeptMenus === null) return false;
            return isset($_navDeptMenus[$key]) && $_navDeptMenus[$key]['can_edit'];
        };
        // Inputter traffic-only (mis. Security: can_edit tanpa can_view) tidak punya akses dashboard.
        $_inputOnly = ! $_navIsAdmin && ! $navCanView('traffic') && $navCanEdit('traffic');
        if (! $_inputOnly):
        ?>
        <div class="nav-label">Main</div>
        <a href="<?= base_url('/') ?>" class="nav-link <?= uri_string() === '' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <?php endif; ?>
        <?php
        $_rp = session()->get('role_perms') ?? [];
        if (($_rp['is_admin'] ?? false) || ($_rp['can_view_gantt'] ?? false)):
        ?>
        <a href="<?= base_url('gantt') ?>" class="nav-link <?= str_starts_with(uri_string(), 'gantt') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-steps"></i> Gantt Timeline
        </a>
        <?php endif; ?>
        <?php
        if ($navCanView('events')):
            $_eventsOpen = str_starts_with(uri_string(), 'events') && ! isset($event);
        ?>
        <a href="<?= base_url('events') ?>" class="nav-link <?= str_starts_with(uri_string(), 'events') && !isset($event) && ! str_starts_with(uri_string(), 'events/monthly-summary') ? 'active' : '' ?>"
           data-bs-toggle="collapse" data-bs-target="#eventsSubmenu" aria-expanded="<?= $_eventsOpen ? 'true' : 'false' ?>">
            <i class="bi bi-calendar-event-fill"></i> Events
            <?php if (!empty($_waitingDataCount)): ?>
            <span class="badge bg-warning text-dark ms-1 rounded-pill" style="font-size:.65rem"><?= $_waitingDataCount ?></span>
            <?php endif; ?>
            <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;transition:transform .2s" id="eventsChevron"></i>
        </a>
        <div class="collapse <?= $_eventsOpen ? 'show' : '' ?>" id="eventsSubmenu">
            <a href="<?= base_url('events') ?>" class="nav-link <?= uri_string() === 'events' ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-list-ul"></i> Semua Event
            </a>
            <a href="<?= base_url('events/monthly-summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'events/monthly-summary') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-calendar-month"></i> Summary Bulanan
            </a>
        </div>
        <?php endif; ?>
        <?php
        if ($navCanView('loyalty_main')):
            $_loyaltyOpen = str_starts_with(uri_string(), 'loyalty') || str_starts_with(uri_string(), 'stock/');
        ?>
        <a href="<?= base_url('loyalty') ?>" class="nav-link"
           data-bs-toggle="collapse" data-bs-target="#loyaltySubmenu" aria-expanded="<?= $_loyaltyOpen ? 'true' : 'false' ?>">
            <i class="bi bi-star-fill"></i> Program Loyalty
            <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;transition:transform .2s" id="loyaltyChevron"></i>
        </a>
        <div class="collapse <?= $_loyaltyOpen ? 'show' : '' ?>" id="loyaltySubmenu">
            <a href="<?= base_url('loyalty') ?>" class="nav-link <?= uri_string() === 'loyalty' ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-list-ul"></i> Semua Program
            </a>
            <a href="<?= base_url('loyalty/summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'loyalty/summary') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-bar-chart-line"></i> Summary Bulanan
            </a>
            <a href="<?= base_url('loyalty/tenants') ?>" class="nav-link <?= str_starts_with(uri_string(), 'loyalty/tenants') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-shop"></i> Master Tenant
            </a>
            <a href="<?= base_url('stock/summary') ?>" class="nav-link <?= (uri_string() === 'stock/summary' || str_contains(uri_string(), '/kartu')) ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-clipboard-data"></i> Summary Stok Fisik
            </a>
            <a href="<?= base_url('stock/barang') ?>" class="nav-link <?= (str_starts_with(uri_string(), 'stock/barang') && ! str_contains(uri_string(), '/kartu')) ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-box-seam"></i> Stock Hadiah Fisik
            </a>
            <a href="<?= base_url('stock/voucher') ?>" class="nav-link <?= str_starts_with(uri_string(), 'stock/voucher') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-ticket-perforated"></i> Stock Voucher Fisik
            </a>
        </div>
        <?php endif; ?>

        <?php
        if ($navCanView('sponsorship_main')):
            $_sponsorOpen = str_starts_with(uri_string(), 'sponsorship');
        ?>
        <a href="<?= base_url('sponsorship') ?>" class="nav-link"
           data-bs-toggle="collapse" data-bs-target="#sponsorSubmenu" aria-expanded="<?= $_sponsorOpen ? 'true' : 'false' ?>">
            <i class="bi bi-trophy-fill"></i> Sponsorship
            <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;transition:transform .2s" id="sponsorChevron"></i>
        </a>
        <div class="collapse <?= $_sponsorOpen ? 'show' : '' ?>" id="sponsorSubmenu">
            <a href="<?= base_url('sponsorship') ?>" class="nav-link <?= uri_string() === 'sponsorship' ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-list-ul"></i> Semua Program
            </a>
            <a href="<?= base_url('sponsorship/summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'sponsorship/summary') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-bar-chart-line"></i> Summary Bulanan
            </a>
        </div>
        <?php endif; ?>

        <?php
        $_creativeMainOpen = (str_starts_with(uri_string(), 'creative') || str_starts_with(uri_string(), 'creative/media-promo')) && !isset($event);
        if ($navCanView('creative_main')):
        ?>
        <a href="<?= base_url('creative') ?>" class="nav-link"
           data-bs-toggle="collapse" data-bs-target="#creativeSubmenu" aria-expanded="<?= $_creativeMainOpen ? 'true' : 'false' ?>">
            <i class="bi bi-vector-pen"></i> Creative &amp; Design
            <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;transition:transform .2s" id="creativeChevron"></i>
        </a>
        <div class="collapse <?= $_creativeMainOpen ? 'show' : '' ?>" id="creativeSubmenu">
            <a href="<?= base_url('creative') ?>" class="nav-link <?= uri_string() === 'creative' ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-list-ul"></i> Semua Item
            </a>
            <a href="<?= base_url('creative/monthly-summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'creative/monthly-summary') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-bar-chart-line"></i> Summary Bulanan
            </a>
            <a href="<?= base_url('creative/media-promo') ?>" class="nav-link <?= uri_string() === 'creative/media-promo' || (str_starts_with(uri_string(), 'creative/media-promo/') && !str_starts_with(uri_string(), 'creative/media-promo/master') && !str_starts_with(uri_string(), 'creative/media-promo/summary') && !str_starts_with(uri_string(), 'creative/media-promo/gantt')) ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-megaphone"></i> Media Promo
            </a>
            <a href="<?= base_url('creative/media-promo/summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'creative/media-promo/summary') ? 'active' : '' ?>" style="padding-left:2.8rem;font-size:.75rem">
                <i class="bi bi-graph-up-arrow"></i> Summary
            </a>
            <a href="<?= base_url('creative/media-promo/gantt') ?>" class="nav-link <?= str_starts_with(uri_string(), 'creative/media-promo/gantt') ? 'active' : '' ?>" style="padding-left:2.8rem;font-size:.75rem">
                <i class="bi bi-calendar3-range"></i> Gantt
            </a>
            <a href="<?= base_url('creative/media-promo/master') ?>" class="nav-link <?= str_starts_with(uri_string(), 'creative/media-promo/master') ? 'active' : '' ?>" style="padding-left:2.8rem;font-size:.75rem">
                <i class="bi bi-pin-map"></i> Master Titik
            </a>
        </div>
        <?php endif; ?>

        <?php
        $_vmOpen = str_starts_with(uri_string(), 'vm') && ! isset($event);
        if ($navCanView('vm_main')):
        ?>
        <a href="<?= base_url('vm') ?>" class="nav-link"
           data-bs-toggle="collapse" data-bs-target="#vmSubmenu" aria-expanded="<?= $_vmOpen ? 'true' : 'false' ?>">
            <i class="bi bi-palette-fill"></i> Dekorasi &amp; VM
            <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;transition:transform .2s" id="vmChevron"></i>
        </a>
        <div class="collapse <?= $_vmOpen ? 'show' : '' ?>" id="vmSubmenu">
            <a href="<?= base_url('vm') ?>" class="nav-link <?= uri_string() === 'vm' ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-list-ul"></i> Semua Item
            </a>
            <a href="<?= base_url('vm/monthly-summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'vm/monthly-summary') ? 'active' : '' ?>" style="padding-left:2rem;font-size:.78rem">
                <i class="bi bi-bar-chart-line"></i> Summary Bulanan
            </a>
        </div>
        <?php endif; ?>

        <?php
        $canViewTraffic = $navCanView('traffic');
        $canEditTraffic = $navCanEdit('traffic');
        $canSeeTraffic  = $canViewTraffic || $canEditTraffic; // inputter (edit-only, mis. Security) tetap lihat menu
        if ($canSeeTraffic):
        ?>
        <div class="nav-label">Traffic</div>
        <a href="<?= base_url('traffic') ?>" class="nav-link <?= uri_string() === 'traffic' ? 'active' : '' ?>">
            <i class="bi bi-person-walking"></i> Daily Traffic
        </a>
        <?php if ($canViewTraffic): ?>
        <a href="<?= base_url('traffic/summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/summary') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line-fill"></i> Summary
        </a>
        <a href="<?= base_url('traffic/compare') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/compare') ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> Compare
        </a>
        <?php endif; ?>
        <?php
        $canImportTraffic = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || (session()->get('role_perms')['can_import_traffic'] ?? false);
        if ($canImportTraffic): ?>
        <a href="<?= base_url('traffic/import') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/import') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php
        $canViewPkLive = $navCanView('parking_live');
        $canViewPkVeh  = $navCanView('parking_vehicles');
        $canViewPkRev  = $navCanView('parking_revenue');
        $canViewPkData = $canViewPkVeh || $canViewPkRev; // analisa butuh traffic/revenue
        if ($canViewPkLive || $canViewPkData):
        ?>
        <div class="nav-label">Parkir</div>
        <?php if ($canViewPkLive): ?>
        <a href="<?= base_url('parking/live') ?>" class="nav-link <?= uri_string() === 'parking/live' || uri_string() === 'parking' ? 'active' : '' ?>">
            <i class="bi bi-broadcast"></i> Live
        </a>
        <?php endif; ?>
        <?php if ($canViewPkVeh): ?>
        <a href="<?= base_url('parking/vehicles/summary') ?>" class="nav-link <?= uri_string() === 'parking/vehicles/summary' ? 'active' : '' ?>">
            <i class="bi bi-car-front-fill"></i> Kendaraan — Summary
        </a>
        <?php endif; ?>
        <?php if ($canViewPkRev): ?>
        <a href="<?= base_url('parking/revenue/summary') ?>" class="nav-link <?= uri_string() === 'parking/revenue/summary' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i> Revenue — Summary
        </a>
        <?php endif; ?>
        <?php if ($canViewPkData): ?>
        <a href="<?= base_url('parking/compare') ?>" class="nav-link <?= uri_string() === 'parking/compare' ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> Compare Periode
        </a>
        <a href="<?= base_url('parking/occupancy') ?>" class="nav-link <?= uri_string() === 'parking/occupancy' ? 'active' : '' ?>">
            <i class="bi bi-activity"></i> Okupansi Intraday
        </a>
        <a href="<?= base_url('parking/recon') ?>" class="nav-link <?= uri_string() === 'parking/recon' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-data"></i> Rekaman vs SPI
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($event)):
            $isAdmin    = ($currentRole === 'admin');
            $canSeeMenu = function(string $key) use ($isAdmin, $_navDeptMenus, $navCanView): bool {
                if ($isAdmin) return true;
                if ($_navDeptMenus === null) return true; // tanpa dept: submenu event tetap tampil (perilaku lama)
                return $navCanView($key);
            };
            $uri = uri_string();
        ?>
        <div class="nav-label">Event Ini</div>
        <div class="subnav">
            <?php if ($canSeeMenu('summary')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="nav-link <?= str_ends_with($uri, '/summary') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Summary
            </a>
            <a href="<?= base_url('events/'.$event['id'].'/gallery') ?>" class="nav-link <?= str_contains($uri, '/gallery') ? 'active' : '' ?>">
                <i class="bi bi-images"></i> Gallery Foto
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('content')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="nav-link <?= str_contains($uri, '/content') ? 'active' : '' ?>">
                <i class="bi bi-collection-play"></i> Content Event
            </a>
            <a href="<?= base_url('events/'.$event['id'].'/rundown') ?>" class="nav-link <?= str_contains($uri, '/rundown') ? 'active' : '' ?>">
                <i class="bi bi-list-ol"></i> Rundown
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('loyalty')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/loyalty') ?>" class="nav-link <?= str_contains($uri, '/loyalty') ? 'active' : '' ?>">
                <i class="bi bi-star-fill"></i> Program Loyalty
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('vm')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/vm') ?>" class="nav-link <?= str_contains($uri, '/vm') ? 'active' : '' ?>">
                <i class="bi bi-palette-fill"></i> Dekorasi & VM
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('creative')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/creative') ?>" class="nav-link <?= str_contains($uri, '/creative') ? 'active' : '' ?>">
                <i class="bi bi-vector-pen"></i> Creative & Design
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('exhibitors')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/exhibitors') ?>" class="nav-link <?= str_contains($uri, '/exhibitors') ? 'active' : '' ?>">
                <i class="bi bi-shop"></i> Exhibition
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('sponsors')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/sponsors') ?>" class="nav-link <?= str_contains($uri, '/sponsors') ? 'active' : '' ?>">
                <i class="bi bi-award-fill"></i> Sponsorship
            </a>
            <?php endif; ?>
            <?php if ($canSeeMenu('budget')): ?>
            <a href="<?= base_url('events/'.$event['id'].'/budget') ?>" class="nav-link <?= str_contains($uri, '/budget') ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i> Budget
            </a>
            <a href="<?= base_url('events/'.$event['id'].'/other-cost') ?>" class="nav-link <?= str_contains($uri, '/other-cost') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i> Other Cost
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        $_isAdmin   = $_navIsAdmin;
        $_canViewPd = $navCanView('people_dev');
        $_canHr     = $navCanView('hr_main');
        // Data Karyawan, Struktur Organisasi & Pengajuan Data: dipakai HR; bila user PD-only, ditampilkan di section PD agar tetap punya akses.
        ?>

        <?php if ($_canHr): ?>
        <div class="nav-label">Human Resources</div>
        <a href="<?= base_url('people/hr-dashboard') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/hr-dashboard') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= base_url('people/employees') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/employees') ? 'active' : '' ?>">
            <i class="bi bi-person-vcard-fill"></i> Data Karyawan
        </a>
        <a href="<?= base_url('people/orgchart') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/orgchart') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3-fill"></i> Struktur Organisasi
        </a>
        <a href="<?= base_url('appraisal') ?>" class="nav-link <?= str_starts_with(uri_string(), 'appraisal') && !str_starts_with(uri_string(), 'appraisal/saya') ? 'active' : '' ?>">
            <i class="bi bi-clipboard-data-fill"></i> Appraisal
        </a>
        <a href="<?= base_url('people/change-requests') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/change-requests') ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i> Pengajuan Data
            <?php if (($_changeReqCount ?? 0) > 0): ?><span class="badge bg-danger ms-auto"><?= $_changeReqCount ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if ($_canViewPd): ?>
        <div class="nav-label">People Development</div>
        <a href="<?= base_url('people/dashboard') ?>" class="nav-link <?= uri_string() === 'people/dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= base_url('people/competencies') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/competencies') ? 'active' : '' ?>">
            <i class="bi bi-diagram-2-fill"></i> Competency Framework
        </a>
        <a href="<?= base_url('people/training') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/training') && !str_starts_with(uri_string(), 'people/training/budget') ? 'active' : '' ?>">
            <i class="bi bi-mortarboard-fill"></i> Program Training
        </a>
        <a href="<?= base_url('people/training/budget') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/training/budget') ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i> Budget Training
        </a>
        <a href="<?= base_url('people/tna') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/tna') ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-pulse-fill"></i> TNA Assessment
        </a>
        <a href="<?= base_url('people/pip') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/pip') && ! str_starts_with(uri_string(), 'people/pip/aspek') ? 'active' : '' ?>">
            <i class="bi bi-arrow-up-circle-fill"></i> PIP
        </a>
        <a href="<?= base_url('people/pip/aspek') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/pip/aspek') ? 'active' : '' ?>">
            <i class="bi bi-list-columns-reverse"></i> Master Aspek PIP
        </a>
        <a href="<?= base_url('people/idp') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/idp') ? 'active' : '' ?>">
            <i class="bi bi-journal-richtext"></i> IDP
        </a>
        <a href="<?= base_url('people/eei') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/eei') ? 'active' : '' ?>">
            <i class="bi bi-heart-pulse-fill"></i> EEI Survey
        </a>
        <?php if (! $_canHr): ?>
        <a href="<?= base_url('people/employees') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/employees') ? 'active' : '' ?>">
            <i class="bi bi-person-vcard-fill"></i> Data Karyawan
        </a>
        <a href="<?= base_url('people/orgchart') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/orgchart') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3-fill"></i> Struktur Organisasi
        </a>
        <a href="<?= base_url('people/change-requests') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/change-requests') ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i> Pengajuan Data
            <?php if (($_changeReqCount ?? 0) > 0): ?><span class="badge bg-danger ms-auto"><?= $_changeReqCount ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php
        // ── Talent Portfolio: akses BUKAN via menu dept — viewer list (admin-managed),
        //    inbox penilai (rantai atasan), atau HR. Link harus tampil utk penilai
        //    walau dept-nya tak punya akses People Development.
        $_talUid    = (int) session()->get('user_id');
        $_talDb     = db_connect();
        $_talViewer = false; $_talInbox = 0;
        $_canHrEdit = $navCanEdit('hr_main');
        if ($_talDb->tableExists('talent_placements')) { // guard: sebelum migrate, jangan fatal-kan layout
            if (! $_isAdmin) {
                $_talViewer = $_talDb->table('talent_viewers')->where('user_id', $_talUid)->countAllResults() > 0;
            }
            $_talInbox = $_talDb->table('talent_placements tp')
                ->join('talent_periods pr', 'pr.id = tp.period_id')
                ->where('tp.current_actor_id', $_talUid)
                ->whereIn('tp.status', ['input', 'in_review'])
                ->where('pr.status', 'active')
                ->countAllResults();
        }
        $_talMap = $_isAdmin || $_talViewer;
        if ($_talMap || $_talInbox > 0 || $_canHrEdit):
        ?>
        <div class="nav-label">Talent</div>
        <?php if ($_talMap): ?>
        <a href="<?= base_url('people/talent') ?>" class="nav-link <?= uri_string() === 'people/talent' ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap-fill"></i> Talent Portfolio
        </a>
        <?php endif; ?>
        <?php if ($_talInbox > 0 || $_canHrEdit): ?>
        <a href="<?= base_url('people/talent/input') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/talent/input') ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check-fill"></i> Penilaian Talent
            <?php if ($_talInbox > 0): ?><span class="badge bg-danger ms-auto"><?= $_talInbox ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if ($_canHrEdit): ?>
        <a href="<?= base_url('people/talent/periods') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/talent/periods') || str_starts_with(uri_string(), 'people/talent/viewers') ? 'active' : '' ?>">
            <i class="bi bi-calendar2-range"></i> Periode Talent
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <?php
        $_canShared = $_canViewPd || $_canHr;
        // "Penilaian Saya" — untuk atasan/penilai (ditentukan org chart), terlepas dari menu key.
        $_apprShow   = $_isAdmin || ($_apprShowMenu ?? false);
        $_apprAuthor = ($_apprIsAuthor ?? false) && ! $_canHr; // dept head/deputy non-HR
        if (($_apprShow || $_apprAuthor) && ! $_canShared): ?><div class="nav-label">Penilaian</div><?php endif; ?>
        <?php if ($_apprShow): ?>
        <a href="<?= base_url('appraisal/saya') ?>" class="nav-link <?= str_starts_with(uri_string(), 'appraisal/saya') ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check-fill"></i> Penilaian Saya
            <?php if (($_apprInboxCount ?? 0) > 0): ?><span class="badge bg-danger ms-auto"><?= $_apprInboxCount ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if ($_apprAuthor): ?>
        <a href="<?= base_url('appraisal/templates') ?>" class="nav-link <?= str_starts_with(uri_string(), 'appraisal/templates') ? 'active' : '' ?>">
            <i class="bi bi-bullseye"></i> Template KPI
        </a>
        <?php endif; ?>

        <?php
        if ($navCanView('legal')):
        ?>
        <div class="nav-label">Legal</div>
        <a href="<?= base_url('legal') ?>" class="nav-link <?= uri_string() === 'legal' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Dashboard Legal
        </a>
        <a href="<?= base_url('legal/permits') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/permits') ? 'active' : '' ?>">
            <i class="bi bi-patch-check"></i> Perizinan & Lisensi
        </a>
        <a href="<?= base_url('legal/spk') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/spk') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Review SPK
        </a>
        <a href="<?= base_url('legal/pks') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/pks') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Perjanjian Kerja Sama
        </a>
        <a href="<?= base_url('legal/psm-mall') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/psm-mall') ? 'active' : '' ?>">
            <i class="bi bi-shop"></i> PSM Mall
        </a>
        <a href="<?= base_url('legal/psm-developer') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/psm-developer') ? 'active' : '' ?>">
            <i class="bi bi-building"></i> PSM Developer
        </a>
        <a href="<?= base_url('legal/psm-gudang') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/psm-gudang') ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> PSM Gudang
        </a>
        <a href="<?= base_url('legal/kontrak-pameran') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/kontrak-pameran') ? 'active' : '' ?>">
            <i class="bi bi-easel"></i> Kontrak Sewa Pameran
        </a>
        <a href="<?= base_url('legal/reviews') ?>" class="nav-link <?= str_starts_with(uri_string(), 'legal/reviews') && !str_starts_with(uri_string(), 'legal/review-file') ? 'active' : '' ?>">
            <i class="bi bi-chat-left-text"></i> Review Dokumen
        </a>
        <?php endif; ?>

        <?php
        if ($navCanView('work_report')):
        ?>
        <div class="nav-label">Progress Report</div>
        <a href="<?= base_url('work-report') ?>" class="nav-link <?= str_starts_with(uri_string(), 'work-report') ? 'active' : '' ?>">
            <i class="bi bi-kanban"></i> Progress Report
        </a>
        <?php endif; ?>

        <?php if (session()->get('role_perms')['can_view_logs'] ?? session()->get('role_is_admin') || session()->get('user_role') === 'admin'): ?>
        <div class="nav-label">System</div>
        <a href="<?= base_url('logs') ?>" class="nav-link <?= str_starts_with(uri_string(), 'logs') ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Activity Log
        </a>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
        <div class="nav-label">Admin</div>
        <a href="<?= base_url('departments') ?>" class="nav-link <?= str_starts_with(uri_string(), 'departments') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3-fill"></i> Departemen
        </a>
        <a href="<?= base_url('divisions') ?>" class="nav-link <?= str_starts_with(uri_string(), 'divisions') ? 'active' : '' ?>">
            <i class="bi bi-layers-fill"></i> Divisi
        </a>
        <a href="<?= base_url('admin/clusters') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/clusters') ? 'active' : '' ?>">
            <i class="bi bi-collection-fill"></i> Cluster Kompetensi
        </a>
        <a href="<?= base_url('jabatans') ?>" class="nav-link <?= str_starts_with(uri_string(), 'jabatans') ? 'active' : '' ?>">
            <i class="bi bi-person-badge-fill"></i> Master Jabatan
        </a>
        <a href="<?= base_url('users') ?>" class="nav-link <?= str_starts_with(uri_string(), 'users') ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Users
        </a>
        <a href="<?= base_url('roles') ?>" class="nav-link <?= str_starts_with(uri_string(), 'roles') ? 'active' : '' ?>">
            <i class="bi bi-shield-fill-check"></i> Roles
        </a>
        <a href="<?= base_url('traffic-doors') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic-doors') ? 'active' : '' ?>">
            <i class="bi bi-door-open-fill"></i> Master Pintu
        </a>
        <a href="<?= base_url('event-locations') ?>" class="nav-link <?= str_starts_with(uri_string(), 'event-locations') ? 'active' : '' ?>">
            <i class="bi bi-geo-alt-fill"></i> Master Lokasi Event
        </a>
        <a href="<?= base_url('theme-periods') ?>" class="nav-link <?= str_starts_with(uri_string(), 'theme-periods') ? 'active' : '' ?>">
            <i class="bi bi-stars"></i> Tema Periode
        </a>
        <a href="<?= base_url('admin/holidays') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/holidays') ? 'active' : '' ?>">
            <i class="bi bi-calendar-heart-fill"></i> Hari Libur
        </a>
        <a href="<?= base_url('admin/settings') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/settings') ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i> Pengaturan
        </a>
        <?php endif; ?>

    </div>

    <!-- User footer -->
    <div class="sidebar-footer d-flex align-items-center gap-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
             style="width:30px;height:30px;background:var(--c-avatar-bg);color:var(--c-avatar-fg);font-size:.75rem">
            <?= strtoupper(substr($currentUser, 0, 1)) ?>
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="user-name text-truncate"><?= esc($currentUser) ?></div>
            <div class="user-role"><?= ucfirst($currentRole) ?></div>
        </div>
        <a href="<?= base_url('logout') ?>" class="btn-logout" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
    <div style="padding:.4rem 1rem .6rem;font-size:.6rem;opacity:.35;line-height:1.5">
        v2.20.0 &nbsp;·&nbsp; © 2026 IT Dept WBL
    </div>

</div>

<div class="main-content">
    <canvas id="themeCanvas" style="position:absolute;top:0;left:0;width:100%;height:220px;pointer-events:none;z-index:9;opacity:0;transition:opacity .6s"></canvas>
    <nav class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <?php if (isset($event)): ?>
            <nav aria-label="breadcrumb" class="min-w-0 overflow-hidden flex-grow-1">
                <ol class="breadcrumb mb-0 small flex-nowrap">
                    <li class="breadcrumb-item flex-shrink-0"><a href="<?= base_url('events') ?>">Events</a></li>
                    <li class="breadcrumb-item active text-truncate" style="max-width:26vw"><?= esc($event['name']) ?></li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary d-none d-sm-inline-block"><?= ucfirst($currentRole) ?></span>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i><?= esc($currentUser) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li>
                        <?php $uiTheme = session('user_theme') ?? 'dark'; ?>
                        <form method="POST" action="<?= base_url('profile/theme') ?>" class="px-3 py-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="theme" value="<?= $uiTheme === 'dark' ? 'light' : 'dark' ?>">
                            <button type="submit" class="btn btn-sm w-100 text-start p-0 border-0 dropdown-item">
                                <i class="bi bi-<?= $uiTheme === 'dark' ? 'sun' : 'moon-stars' ?> me-2"></i>
                                <?= $uiTheme === 'dark' ? 'Light Mode' : 'Dark Mode' ?>
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Theme Period Alert Banner (populated by JS) -->
    <div id="themeBanner" style="display:none;padding:.5rem 1.25rem;font-size:.82rem;font-weight:500;border-bottom:1px solid rgba(255,255,255,.08);position:relative;z-index:8">
        <span id="themeBannerText"></span>
        <button onclick="this.parentElement.style.display='none'" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;opacity:.5;cursor:pointer;font-size:1rem;line-height:1;padding:0;color:inherit">&times;</button>
    </div>

    <div class="page-content">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Persist sidebar scroll position across page loads
(function() {
    const nav = document.querySelector('.sidebar .nav-section');
    if (!nav) return;
    const saved = sessionStorage.getItem('sidebarScroll');
    if (saved) nav.scrollTop = parseInt(saved);
    nav.addEventListener('scroll', function() {
        sessionStorage.setItem('sidebarScroll', this.scrollTop);
    }, { passive: true });
})();
</script>
<script>
if (typeof Chart !== 'undefined') {
    const _dark = document.documentElement.getAttribute('data-theme') === 'dark';
    Chart.defaults.color                        = _dark ? 'rgba(180,210,255,.6)'  : '#64748b';
    Chart.defaults.borderColor                  = _dark ? 'rgba(255,255,255,.06)' : 'rgba(99,102,241,.12)';
    Chart.defaults.plugins.legend.labels.color  = _dark ? 'rgba(180,210,255,.7)'  : '#475569';
    Chart.defaults.scale.grid.color             = _dark ? 'rgba(255,255,255,.05)' : 'rgba(99,102,241,.07)';
    Chart.defaults.scale.ticks.color            = _dark ? 'rgba(180,210,255,.45)' : '#64748b';
}
</script>

<style>
/* Tabel responsif: jangan potong scroll-x saat dicetak dari halaman biasa */
@media print { .table-responsive { overflow: visible !important; } }
</style>
<script>
/* Auto-bungkus setiap tabel Bootstrap yang belum responsive agar bisa digeser
   horizontal di layar sempit (mobile & iPad portrait). Berlaku global untuk
   semua halaman yang memakai layout ini. */
(function () {
    function wrapTables(root) {
        (root || document).querySelectorAll('table.table').forEach(function (t) {
            if (t.closest('.table-responsive')) return;
            var w = document.createElement('div');
            w.className = 'table-responsive';
            t.parentNode.insertBefore(w, t);
            w.appendChild(t);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { wrapTables(); });
    } else {
        wrapTables();
    }
    window.wrapResponsiveTables = wrapTables; // bisa dipanggil ulang setelah render tabel via JS
})();
</script>
<?= $this->renderSection('scripts') ?>

<style>
#themeBanner { animation: none; }
#themeBanner.show { display:block !important; animation: fadeUp .35s ease forwards; }
</style>
<!-- Theme Period Canvas JS below -->
<script>
(function () {
    const canvas = document.getElementById('themeCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let particles = [], fwBursts = [], fwTimer = 0, currentAnim = null, raf = null;

    function resize() {
        canvas.width  = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
    }

    window.addEventListener('scroll', function () {}, { passive: true });

    fetch('<?= base_url('theme-periods/today') ?>')
        .then(r => r.json())
        .then(periods => {
            if (!periods || !periods.length) return;
            const p = periods[0];
            showBanner(p);
            if (p.animation && p.animation !== 'none') {
                resize();
                window.addEventListener('resize', resize);
                canvas.style.opacity = '1';
                startAnimation(p.animation);
            }
        })
        .catch(() => {});

    function showBanner(p) {
        if (!p.pesan) return;
        const _esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const banner  = document.getElementById('themeBanner');
        const bannerT = document.getElementById('themeBannerText');
        const colorMap = { confetti:'#8b5cf6', balloons:'#ec4899', snow:'#38bdf8', fireworks:'#f97316', stars:'#fbbf24', none:'#64748b' };
        const c = colorMap[p.animation] || '#8b5cf6';
        banner.style.background = `linear-gradient(90deg,${c}22,${c}0a)`;
        banner.style.borderBottomColor = c + '44';
        bannerT.innerHTML = `<span style="margin-right:.5rem;font-size:1rem">${_esc(p.emoji || '🎉')}</span>${_esc(p.pesan)}<small class="ms-2" style="opacity:.45">${_esc(p.nama)}</small>`;
        banner.classList.add('show');
    }

    function startAnimation(type) {
        particles = []; fwBursts = []; fwTimer = 0;
        currentAnim = type;
        if (type === 'confetti')  initConfetti();
        if (type === 'balloons')  initBalloons();
        if (type === 'snow')      initSnow();
        if (type === 'fireworks') initFireworks();
        if (type === 'stars')     initStars();
        if (raf) cancelAnimationFrame(raf);
        animate();
    }

    /* ── CONFETTI ─────────────────────────────── */
    function initConfetti() {
        const cols = ['#f43f5e','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899'];
        for (let i = 0; i < 65; i++) particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            w: Math.random() * 8 + 4, h: Math.random() * 4 + 3,
            color: cols[i % cols.length],
            speed: Math.random() * 2 + 1.5,
            drift: Math.random() * 1.6 - 0.8,
            rot: Math.random() * Math.PI * 2,
            rotS: (Math.random() - 0.5) * 0.1,
        });
    }
    function drawConfetti() {
        particles.forEach(p => {
            p.y += p.speed; p.x += p.drift; p.rot += p.rotS;
            if (p.y > canvas.height) { p.y = -p.h; p.x = Math.random() * canvas.width; }
            ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
            ctx.fillStyle = p.color; ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
            ctx.restore();
        });
    }

    /* ── BALLOONS ─────────────────────────────── */
    function initBalloons() {
        const cols = ['#f43f5e','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899'];
        for (let i = 0; i < 10; i++) particles.push({
            x: Math.random() * canvas.width,
            y: canvas.height + Math.random() * canvas.height,
            r: Math.random() * 14 + 18,
            color: cols[i % cols.length],
            speed: Math.random() * 0.7 + 0.4,
            phase: Math.random() * Math.PI * 2,
        });
    }
    function drawBalloons() {
        particles.forEach(p => {
            p.y -= p.speed; p.phase += 0.018; p.x += Math.sin(p.phase) * 0.55;
            if (p.y < -p.r * 3) { p.y = canvas.height + p.r; p.x = Math.random() * canvas.width; }
            ctx.save();
            ctx.beginPath();
            ctx.ellipse(p.x, p.y, p.r, p.r * 1.2, 0, 0, Math.PI * 2);
            ctx.fillStyle = p.color + 'bb'; ctx.fill();
            ctx.strokeStyle = p.color; ctx.lineWidth = 1; ctx.stroke();
            ctx.beginPath();
            ctx.ellipse(p.x - p.r*.3, p.y - p.r*.38, p.r*.22, p.r*.15, -0.5, 0, Math.PI*2);
            ctx.fillStyle = 'rgba(255,255,255,.38)'; ctx.fill();
            ctx.beginPath();
            ctx.moveTo(p.x, p.y + p.r * 1.2);
            ctx.bezierCurveTo(p.x+4, p.y+p.r*1.8, p.x-4, p.y+p.r*2.4, p.x, p.y+p.r*3);
            ctx.strokeStyle = p.color + '80'; ctx.lineWidth = 1; ctx.stroke();
            ctx.restore();
        });
    }

    /* ── SNOW ─────────────────────────────────── */
    function initSnow() {
        for (let i = 0; i < 70; i++) particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * 3.5 + 1.5,
            speed: Math.random() * 1 + 0.4,
            drift: Math.random() * 0.7 - 0.35,
            op: Math.random() * 0.45 + 0.35,
        });
    }
    function drawSnow() {
        particles.forEach(p => {
            p.y += p.speed; p.x += p.drift;
            if (p.y > canvas.height) { p.y = -p.r; p.x = Math.random() * canvas.width; }
            if (p.x > canvas.width)  p.x = 0;
            if (p.x < 0)             p.x = canvas.width;
            ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
            ctx.fillStyle = `rgba(255,255,255,${p.op})`; ctx.fill();
        });
    }

    /* ── FIREWORKS ────────────────────────────── */
    function initFireworks() { fwBursts = []; fwTimer = 0; addBurst(); addBurst(); }
    function addBurst() {
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height * 0.75 + 15;
        const cols = ['#f43f5e','#f97316','#eab308','#22c55e','#3b82f6','#8b5cf6','#ec4899','#fff'];
        const color = cols[Math.floor(Math.random() * cols.length)];
        for (let i = 0; i < 30; i++) {
            const a = (i / 30) * Math.PI * 2;
            const s = Math.random() * 3 + 1.5;
            fwBursts.push({ x, y, vx: Math.cos(a)*s, vy: Math.sin(a)*s, color, life: 1, decay: Math.random()*.014+.011 });
        }
    }
    function drawFireworks() {
        fwTimer++;
        if (fwTimer % 95 === 0) addBurst();
        fwBursts = fwBursts.filter(p => p.life > 0);
        fwBursts.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.vy += 0.04; p.vx *= 0.98; p.life -= p.decay;
            const alpha = Math.floor(p.life * 255).toString(16).padStart(2,'0');
            ctx.beginPath(); ctx.arc(p.x, p.y, 2.5, 0, Math.PI*2);
            ctx.fillStyle = p.color + alpha; ctx.fill();
        });
    }

    /* ── STARS ────────────────────────────────── */
    function initStars() {
        const cols = ['#fbbf24','#f9a8d4','#a5f3fc','#d9f99d','#ffffff'];
        for (let i = 0; i < 55; i++) particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * 3 + 1.5,
            phase: Math.random() * Math.PI * 2,
            speed: Math.random() * 0.025 + 0.01,
            color: cols[i % cols.length],
        });
    }
    function drawStar(x, y, r, op, color) {
        const pts = 5, outer = r, inner = r * 0.4;
        ctx.beginPath();
        for (let i = 0; i < pts * 2; i++) {
            const a = (i * Math.PI / pts) - Math.PI / 2;
            const d = i % 2 === 0 ? outer : inner;
            i === 0 ? ctx.moveTo(x + d*Math.cos(a), y + d*Math.sin(a))
                    : ctx.lineTo(x + d*Math.cos(a), y + d*Math.sin(a));
        }
        ctx.closePath();
        ctx.fillStyle = color + Math.floor(op*255).toString(16).padStart(2,'0');
        ctx.fill();
    }
    function drawStars() {
        particles.forEach(p => {
            p.phase += p.speed;
            const op = (Math.sin(p.phase) + 1) * 0.45 + 0.1;
            drawStar(p.x, p.y, p.r * 3, op, p.color);
        });
    }

    /* ── LOOP ─────────────────────────────────── */
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (currentAnim === 'confetti')  drawConfetti();
        if (currentAnim === 'balloons')  drawBalloons();
        if (currentAnim === 'snow')      drawSnow();
        if (currentAnim === 'fireworks') drawFireworks();
        if (currentAnim === 'stars')     drawStars();
        raf = requestAnimationFrame(animate);
    }
})();
</script>

<?php if (session()->get('logged_in')): ?>
<!-- Idle auto-logout (60 menit tanpa aktivitas user; abaikan polling/background) -->
<div id="idle-modal" style="position:fixed;inset:0;z-index:2000;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.6);backdrop-filter:blur(3px)">
    <div style="background:#1e293b;color:#e2e8f0;border:1px solid rgba(148,163,184,.25);border-radius:1rem;padding:1.6rem 1.8rem;width:min(94%,400px);text-align:center;box-shadow:0 12px 48px rgba(0,0,0,.45)">
        <div style="font-size:2.4rem;line-height:1;margin-bottom:.5rem"><i class="bi bi-clock-history text-warning"></i></div>
        <div class="fw-semibold mb-1">Sesi akan berakhir</div>
        <div class="small text-secondary mb-3">Anda tidak aktif. Otomatis keluar dalam <strong id="idle-count">60</strong> detik.</div>
        <div class="d-flex justify-content-center gap-2">
            <button id="idle-stay" type="button" class="btn btn-sm btn-success"><i class="bi bi-shield-check"></i> Tetap masuk</button>
            <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-secondary">Keluar sekarang</a>
        </div>
    </div>
</div>
<script>
(function () {
    const IDLE_MS = 60 * 60 * 1000;  // 60 menit
    const WARN_MS = 60 * 1000;       // peringatan 1 menit sebelum
    const LOGOUT = '<?= base_url('logout') ?>';
    const modal = document.getElementById('idle-modal');
    const countEl = document.getElementById('idle-count');
    let last = Date.now(), warning = false, cdTimer = null;

    function bump() { if (!warning) last = Date.now(); }
    ['mousemove','mousedown','keydown','scroll','touchstart','click','wheel'].forEach(ev =>
        window.addEventListener(ev, bump, { passive: true }));
    // Aktivitas di tab lain (login sama) ikut reset + batalkan peringatan via localStorage
    window.addEventListener('storage', e => {
        if (e.key !== 'mic_activity') return;
        last = Date.now();
        if (warning) { warning = false; clearInterval(cdTimer); modal.style.display = 'none'; }
    });
    setInterval(() => { if (!warning) try { localStorage.setItem('mic_activity', String(last)); } catch (e) {} }, 15000);

    function showWarning() {
        warning = true; let left = Math.ceil(WARN_MS / 1000);
        countEl.textContent = left; modal.style.display = 'flex';
        cdTimer = setInterval(() => {
            left--; countEl.textContent = left;
            if (left <= 0) { clearInterval(cdTimer); window.location.href = LOGOUT; }
        }, 1000);
    }
    document.getElementById('idle-stay').addEventListener('click', () => {
        warning = false; clearInterval(cdTimer); modal.style.display = 'none'; last = Date.now();
        fetch(location.href, { method: 'HEAD', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {}); // refresh session
    });
    setInterval(() => {
        if (warning) return;
        const idle = Date.now() - last;
        if (idle >= IDLE_MS) { window.location.href = LOGOUT; }
        else if (idle >= IDLE_MS - WARN_MS) { showWarning(); }
    }, 5000);
})();
</script>
<?php endif; ?>
<!-- ── PWA service worker ── -->
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
        .catch(err => console.warn('SW register gagal:', err));
    });
  }
</script>
</body>
</html>
