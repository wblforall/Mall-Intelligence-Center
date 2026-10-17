<?php
/**
 * Bar tab navigasi per-event (menggantikan grup sidebar "Event Ini").
 * Variabel: $event (wajib; bila kosong tidak render).
 * Aturan hak tampil & aktif sama persis dengan blok sidebar lama.
 */
if (empty($event['id'])) return;

$_etRole      = session()->get('user_role') ?? '';
$_etIsAdmin   = session()->get('role_is_admin') || $_etRole === 'admin';
$_etUserMenus = session()->get('user_menus');
$_etDeptMenus = session()->get('dept_menus');
$_etCanView   = function (string $key) use ($_etIsAdmin, $_etUserMenus, $_etDeptMenus): bool {
    if ($_etIsAdmin) return true;
    if (isset($_etUserMenus[$key]) && $_etUserMenus[$key]['can_view']) return true;
    if ($_etDeptMenus === null) return false;
    return isset($_etDeptMenus[$key]) && $_etDeptMenus[$key]['can_view'];
};
$_etCanSee = function (string $key) use ($_etRole, $_etDeptMenus, $_etCanView): bool {
    if ($_etRole === 'admin') return true;
    if ($_etDeptMenus === null) return true; // tanpa dept: tab tetap tampil (perilaku lama sidebar)
    return $_etCanView($key);
};

$_etUri = uri_string();
$_etId  = (int) $event['id'];
// [hak, label, ikon, path, aturan-aktif, modul-completion]
$_etDefs = [
    ['summary',    'Summary',          'bi-speedometer2',    'summary',    fn($u) => str_ends_with($u, '/summary'),     null],
    ['summary',    'Gallery Foto',     'bi-images',          'gallery',    fn($u) => str_contains($u, '/gallery'),      null],
    ['content',    'Content Event',    'bi-collection-play', 'content',    fn($u) => str_contains($u, '/content'),      'content'],
    ['content',    'Rundown',          'bi-list-ol',         'rundown',    fn($u) => str_contains($u, '/rundown'),      'content'],
    ['loyalty',    'Program Loyalty',  'bi-star-fill',       'loyalty',    fn($u) => str_contains($u, '/loyalty'),      'loyalty'],
    ['vm',         'Dekorasi & VM',    'bi-palette-fill',    'vm',         fn($u) => str_contains($u, '/vm'),           'vm'],
    ['creative',   'Creative & Design','bi-vector-pen',      'creative',   fn($u) => str_contains($u, '/creative'),     'creative'],
    ['exhibitors', 'Exhibition',       'bi-shop',            'exhibitors', fn($u) => str_contains($u, '/exhibitors'),   'exhibitors'],
    ['sponsors',   'Sponsorship',      'bi-award-fill',      'sponsors',   fn($u) => str_contains($u, '/sponsors'),     'sponsors'],
    ['budget',     'Budget',           'bi-cash-stack',      'budget',     fn($u) => str_contains($u, '/budget'),       null],
    ['budget',     'Other Cost',       'bi-receipt',         'other-cost', fn($u) => str_contains($u, '/other-cost'),   null],
];

if (! array_filter($_etDefs, fn($d) => $_etCanSee($d[0]))) return; // tidak ada hak sama sekali: jangan render bar kosong

// Lencana titik: modul wajib belum ditandai selesai, setelah event berakhir (sama dengan banner "Data belum lengkap")
$_etPending = [];
try {
    $_etStart = ! empty($event['start_date']) ? strtotime($event['start_date']) : null;
    if ($_etStart) {
        $_etEnd = date('Y-m-d', strtotime('+' . (max(1, (int) ($event['event_days'] ?? 1)) - 1) . ' days', $_etStart));
        if (date('Y-m-d') > $_etEnd) {
            $_etDone = array_column(db_connect()->table('event_completions')->select('module')->where('event_id', $_etId)->get()->getResultArray(), 'module');
            $_etPending = array_diff(array_keys(\App\Models\EventCompletionModel::REQUIRED_MODULES), $_etDone);
        }
    }
} catch (\Throwable $e) { $_etPending = []; }
?>
<link rel="stylesheet" href="<?= base_url('css/event-tabs.css') ?>?v=<?= @filemtime(FCPATH . 'css/event-tabs.css') ?: 1 ?>">
<nav class="ev-tabs" id="evTabs" aria-label="Navigasi event ini">
    <div class="ev-tabs-in" role="list">
    <?php foreach ($_etDefs as [$perm, $label, $ico, $path, $isActive, $mod]):
        if (! $_etCanSee($perm)) continue;
        $on  = $isActive($_etUri);
        $dot = $mod !== null && in_array($mod, $_etPending, true);
    ?>
        <a role="listitem" href="<?= base_url('events/' . $_etId . '/' . $path) ?>" class="ev-tab<?= $on ? ' is-active' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= $ico ?>" aria-hidden="true"></i><span><?= esc($label) ?></span><?php if ($dot): ?><b class="ev-tab-dot" title="Data modul belum ditandai selesai"></b><?php endif; ?>
        </a>
    <?php endforeach; ?>
    </div>
</nav>
<script src="<?= base_url('js/event-tabs.js') ?>?v=<?= @filemtime(FCPATH . 'js/event-tabs.js') ?: 1 ?>" defer></script>
