<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= session('user_theme') === 'light' ? 'light' : 'dark' ?>" data-theme="<?= session('user_theme') === 'light' ? 'light' : 'dark' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Mall Intelligence Center' ?></title>
<link rel="icon" type="image/png" href="<?= base_url('img/mic-logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= base_url('css/theme.css') ?>" rel="stylesheet">
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
        <img src="<?= base_url('img/mic-logo.png') ?>" alt="MIC Logo"
             style="width:36px;height:36px;object-fit:contain;flex-shrink:0;border-radius:4px;background:var(--c-logo-bg);padding:2px">
        <div>
            <div class="brand-name">Mall Intelligence Center</div>
            <div class="brand-sub">eWalk & Pentacity</div>
        </div>
    </div>

    <!-- Nav -->
    <div class="nav-section flex-grow-1">

        <div class="nav-label">Main</div>
        <a href="<?= base_url('/') ?>" class="nav-link <?= uri_string() === '' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <?php
        $_deptMenusE = session()->get('dept_menus');
        $_canSeeEvents = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $_deptMenusE === null || ($_deptMenusE['events']['can_view'] ?? false);
        if ($_canSeeEvents):
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
        $_deptMenusL = session()->get('dept_menus');
        $_canSeeLoyalty = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $_deptMenusL === null || ($_deptMenusL['loyalty_main']['can_view'] ?? false);
        if ($_canSeeLoyalty):
            $_loyaltyOpen = str_starts_with(uri_string(), 'loyalty');
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
        </div>
        <?php endif; ?>

        <?php
        $_deptMenusSp = session()->get('dept_menus');
        $_canSeeSponsor = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $_deptMenusSp === null || ($_deptMenusSp['sponsorship_main']['can_view'] ?? false);
        if ($_canSeeSponsor):
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
        $_creativeMainOpen = str_starts_with(uri_string(), 'creative') && !isset($event);
        $_deptMenusC = session()->get('dept_menus');
        $_canSeeCreativeMain = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $_deptMenusC === null || ($_deptMenusC['creative_main']['can_view'] ?? false);
        if ($_canSeeCreativeMain):
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
        </div>
        <?php endif; ?>

        <?php
        $deptMenusNav  = session()->get('dept_menus');
        $canSeeVM = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $deptMenusNav === null || ($deptMenusNav['vm_main']['can_view'] ?? false);
        $_vmOpen = str_starts_with(uri_string(), 'vm') && ! isset($event);
        if ($canSeeVM):
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
        $canSeeTraffic = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || $deptMenusNav === null || ($deptMenusNav['traffic']['can_view'] ?? false);
        if ($canSeeTraffic):
        ?>
        <div class="nav-label">Traffic</div>
        <a href="<?= base_url('traffic') ?>" class="nav-link <?= uri_string() === 'traffic' ? 'active' : '' ?>">
            <i class="bi bi-person-walking"></i> Daily Traffic
        </a>
        <a href="<?= base_url('traffic/summary') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/summary') ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line-fill"></i> Summary
        </a>
        <a href="<?= base_url('traffic/compare') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/compare') ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> Compare
        </a>
        <?php
        $canImportTraffic = session()->get('role_is_admin') || session()->get('user_role') === 'admin'
            || (session()->get('role_perms')['can_import_traffic'] ?? false);
        if ($canImportTraffic): ?>
        <a href="<?= base_url('traffic/import') ?>" class="nav-link <?= str_starts_with(uri_string(), 'traffic/import') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($event)):
            $isAdmin    = ($currentRole === 'admin');
            $deptMenus  = session()->get('dept_menus');
            $canSeeMenu = function(string $key) use ($isAdmin, $deptMenus): bool {
                if ($isAdmin) return true;
                if ($deptMenus === null) return true;
                return isset($deptMenus[$key]) && $deptMenus[$key]['can_view'];
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
        $_isAdmin = session()->get('role_is_admin') || session()->get('user_role') === 'admin';
        $_deptMenusPd = session()->get('dept_menus');
        $_canViewPd   = $_isAdmin || $_deptMenusPd === null || ($_deptMenusPd['people_dev']['can_view'] ?? false);
        if ($_canViewPd):
        ?>
        <div class="nav-label">People Development</div>
        <a href="<?= base_url('people/dashboard') ?>" class="nav-link <?= uri_string() === 'people/dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= base_url('people/employees') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/employees') ? 'active' : '' ?>">
            <i class="bi bi-person-vcard-fill"></i> Data Karyawan
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
        <a href="<?= base_url('people/eei') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/eei') ? 'active' : '' ?>">
            <i class="bi bi-heart-pulse-fill"></i> EEI Survey
        </a>
        <a href="<?= base_url('people/orgchart') ?>" class="nav-link <?= str_starts_with(uri_string(), 'people/orgchart') ? 'active' : '' ?>">
            <i class="bi bi-diagram-3-fill"></i> Struktur Organisasi
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
        v1.4 &nbsp;·&nbsp; © 2026 IT Dept WBL
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('events') ?>">Events</a></li>
                    <li class="breadcrumb-item active"><?= esc($event['name']) ?></li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?= ucfirst($currentRole) ?></span>
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
        const banner  = document.getElementById('themeBanner');
        const bannerT = document.getElementById('themeBannerText');
        const colorMap = { confetti:'#8b5cf6', balloons:'#ec4899', snow:'#38bdf8', fireworks:'#f97316', stars:'#fbbf24', none:'#64748b' };
        const c = colorMap[p.animation] || '#8b5cf6';
        banner.style.background = `linear-gradient(90deg,${c}22,${c}0a)`;
        banner.style.borderBottomColor = c + '44';
        bannerT.innerHTML = `<span style="margin-right:.5rem;font-size:1rem">${p.emoji || '🎉'}</span>${p.pesan}<small class="ms-2" style="opacity:.45">${p.nama}</small>`;
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
</body>
</html>
