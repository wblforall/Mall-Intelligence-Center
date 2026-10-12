<?php
/**
 * Kepala bersama halaman Budget Departemen: judul, pilihan dept (admin),
 * tahun, dan tab. Butuh: $dept, $tahun, $tab, $depts (kosong = hanya dept sendiri), $deptSendiri, $canEdit, $years, $url.
 */
if (! function_exists('rpb')) {
    /** Rupiah ringkas tanpa "Rp" untuk tabel padat; nol → tanda pisah. */
    function rpb(float $v, bool $nolStrip = true): string
    {
        if ($nolStrip && abs($v) < 0.5) return '<span class="text-body-tertiary">–</span>';
        return number_format($v, 0, ',', '.');
    }
}
$_tabs = [
    'rekap'     => ['', 'bi-table', 'Rekap'],
    'rencana'   => ['rencana', 'bi-pencil-square', 'Isi Budget'],
    'realisasi' => ['realisasi', 'bi-receipt', 'Realisasi'],
    'pos'       => ['pos', 'bi-list-ol', 'Pos Budget'],
];
$_bawaQ = array_filter(['tahun' => $tahun]);
?>
<style>
/* Tombol pilihan yang aktif (bulan, Ringkas/Per Bulan, mal). Tema memaksa warna
   teks .btn-outline-secondary dengan !important, sehingga saat aktif teksnya
   tenggelam di latar abu-abu — terutama di tema terang. */
html[data-theme] .btn-group .btn-outline-secondary.active, html[data-theme] .btn-group .btn-outline-secondary.active:hover,
html[data-theme] .rb-bln.active, html[data-theme] .rb-bln.active:hover {
    background: var(--bs-primary) !important; border-color: var(--bs-primary) !important; color: #fff !important;
}
html[data-theme] .rb-bln.active .badge { background: #fff !important; color: var(--bs-primary) !important; }

/* Tom Select — disamakan dengan .form-select tema (gelap & terang). */
.ts-wrapper.single .ts-control { flex-wrap: nowrap; }
.ts-wrapper.single .ts-control .item { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ts-wrapper.bd-ts-dept { width: 250px; } .ts-wrapper.bd-ts-tahun { width: 100px; } .ts-wrapper.bd-ts-sd { width: 92px; }
/* Tom Select tak menyalin style lebar dari <select> aslinya — beri lebar lewat kelas, mis. bd-ts-sd. */
@media (max-width: 575.98px) { .ts-wrapper.bd-ts-dept { width: 100%; } }

/* Periode asal (flatpickr pemilih bulan) — tombol kosongkan di dalam kotak. */
.bd-asal { position: relative; } .bd-asal .flatpickr-wrapper { display: block; }
.bd-asal-hapus { position: absolute; right: .4rem; top: 1rem; transform: translateY(-50%); z-index: 3; border: 0; background: none;
    color: var(--bs-secondary-color); padding: 0 .2rem; line-height: 1; font-size: .8rem; }
.flatpickr-calendar { background: rgba(8,19,31,.98) !important; border: 1px solid rgba(255,255,255,.1) !important; box-shadow: 0 8px 32px rgba(0,0,0,.55) !important; }
.flatpickr-calendar .flatpickr-month, .flatpickr-calendar .flatpickr-current-month, .flatpickr-calendar .flatpickr-current-month input.cur-year { color: var(--txt) !important; fill: var(--txt) !important; }
.flatpickr-calendar .flatpickr-prev-month svg, .flatpickr-calendar .flatpickr-next-month svg { fill: var(--txt) !important; }
.flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after { border-bottom-color: rgba(8,19,31,.98) !important; }
.flatpickr-monthSelect-month { color: rgba(180,210,255,.85) !important; border-radius: .45rem !important; }
.flatpickr-monthSelect-month:hover { background: rgba(232,65,90,.14) !important; border-color: transparent !important; }
.flatpickr-monthSelect-month.selected { background: var(--bs-primary) !important; border-color: var(--bs-primary) !important; color: #fff !important; }
[data-theme="light"] .flatpickr-calendar { background: #fff !important; border-color: rgba(249,115,22,.18) !important; box-shadow: 0 8px 24px rgba(0,0,0,.1) !important; }
[data-theme="light"] .flatpickr-calendar .flatpickr-month, [data-theme="light"] .flatpickr-calendar .flatpickr-current-month, [data-theme="light"] .flatpickr-calendar .flatpickr-current-month input.cur-year { color: #1e293b !important; fill: #1e293b !important; }
[data-theme="light"] .flatpickr-calendar .flatpickr-prev-month svg, [data-theme="light"] .flatpickr-calendar .flatpickr-next-month svg { fill: #1e293b !important; }
[data-theme="light"] .flatpickr-calendar.arrowTop:after { border-bottom-color: #fff !important; }
[data-theme="light"] .flatpickr-monthSelect-month { color: #374151 !important; }
[data-theme="light"] .flatpickr-monthSelect-month:hover { background: rgba(249,115,22,.08) !important; }
.ts-wrapper.form-select, .ts-wrapper.form-select.focus { padding: 0 !important; }
.ts-wrapper .ts-control { background: transparent !important; border: 0 !important; box-shadow: none !important;
    color: var(--txt) !important; padding: .25rem 2rem .25rem .6rem !important; min-height: 0; cursor: pointer; }
.ts-wrapper.form-select-sm .ts-control { font-size: .875rem; padding-top: .2rem !important; padding-bottom: .2rem !important; }
.ts-wrapper .ts-control input, .ts-wrapper .ts-control .item { color: var(--txt) !important; }
.ts-wrapper .ts-control input::placeholder { color: rgba(180,210,255,.35); }
.ts-wrapper.focus { border-color: rgba(232,65,90,.5) !important; box-shadow: 0 0 0 .2rem rgba(232,65,90,.18) !important; }
.ts-dropdown { background: rgba(8,19,31,.98) !important; border: 1px solid rgba(255,255,255,.1) !important; border-radius: .6rem !important;
    box-shadow: 0 8px 32px rgba(0,0,0,.55) !important; color: rgba(180,210,255,.8) !important; z-index: 1065 !important; margin-top: 4px; overflow: hidden; }
.ts-dropdown .option { padding: .4rem .7rem; }
.ts-dropdown .option.active { background: rgba(232,65,90,.14) !important; color: var(--txt) !important; }
.ts-dropdown .option.selected { color: #f8829a !important; font-weight: 600; }
.ts-dropdown .no-results { padding: .4rem .7rem; color: rgba(180,210,255,.45); }
.ts-dropdown .dropdown-input-wrap { padding: .4rem; border-bottom: 1px solid rgba(255,255,255,.08); }
.ts-dropdown .dropdown-input { background: rgba(255,255,255,.06) !important; border: 1px solid rgba(255,255,255,.12) !important;
    color: var(--txt) !important; border-radius: .45rem; padding: .3rem .55rem; width: 100%; }
.ts-dropdown .dropdown-input:focus { outline: 0; border-color: rgba(232,65,90,.5) !important; box-shadow: 0 0 0 .2rem rgba(232,65,90,.18) !important; }
[data-theme="light"] .ts-dropdown .dropdown-input:focus { border-color: rgba(249,115,22,.5) !important; box-shadow: 0 0 0 .2rem rgba(249,115,22,.12) !important; }
[data-theme="light"] .ts-wrapper .ts-control, [data-theme="light"] .ts-wrapper .ts-control input,
[data-theme="light"] .ts-wrapper .ts-control .item { color: #1e293b !important; }
[data-theme="light"] .ts-wrapper.focus { border-color: rgba(249,115,22,.5) !important; box-shadow: 0 0 0 .2rem rgba(249,115,22,.12) !important; }
[data-theme="light"] .ts-dropdown { background: #fff !important; border-color: rgba(249,115,22,.18) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,.1) !important; color: #374151 !important; }
[data-theme="light"] .ts-dropdown .option.active { background: rgba(249,115,22,.08) !important; color: #ea580c !important; }
[data-theme="light"] .ts-dropdown .option.selected { color: #ea580c !important; }
[data-theme="light"] .ts-dropdown .dropdown-input-wrap { border-bottom-color: rgba(249,115,22,.12); }
[data-theme="light"] .ts-dropdown .dropdown-input { background: #fff !important; border-color: rgba(249,115,22,.25) !important; color: #1e293b !important; }
</style>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
/* Semua <select class="bd-ts"> di modul Budget → Tom Select. Daftar panjang
   (> 6 pilihan) bisa dicari dengan mengetik. onchange="this.form.submit()"
   tetap jalan karena Tom Select memicu event change pada <select> aslinya. */
window.bdTomSelect = function (sel) {
    if (!window.TomSelect || sel.tomselect) return sel.tomselect;
    const banyak = sel.options.length > 6;
    return new TomSelect(sel, {
        create: false, allowEmptyOption: true, maxOptions: null,
        controlInput: null,                                   // kotak cari ada di dropdown, bukan di kontrol
        plugins: banyak ? ['dropdown_input'] : [],
        // Dropdown tetap di dalam pembungkusnya (bukan di body): di dalam jendela Bootstrap,
        // dropdown di body membuat focus trap menarik fokus kembali dan dropdown langsung tertutup.
        dropdownParent: null,
        render: { no_results: () => '<div class="no-results">Tidak ditemukan</div>' },
    });
};
/* Isi nilai <select> yang mungkin sudah jadi Tom Select. */
window.bdSetNilai = function (sel, v) {
    if (sel.tomselect) sel.tomselect.setValue(v == null ? '' : String(v), true);
    else sel.value = v == null ? '' : v;
};
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('select.bd-ts').forEach(bdTomSelect));
</script>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Budget Departemen</h4>
        <div class="text-muted small">
            <?= esc($dept['name']) ?> · catatan internal budget &amp; realisasi
            <?php if (! $canEdit && (int) $dept['id'] !== $deptSendiri): ?>
            <span class="badge text-bg-secondary ms-1"><i class="bi bi-eye me-1"></i>hanya lihat</span>
            <?php endif; ?>
        </div>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <?php if ($depts): ?>
        <select name="dept" class="form-select form-select-sm bd-ts bd-ts-dept" onchange="this.form.submit()" aria-label="Departemen">
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) $dept['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?><?= (int) $d['id'] === $deptSendiri ? ' (dept saya)' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="tahun" class="form-select form-select-sm bd-ts bd-ts-tahun" onchange="this.form.submit()" aria-label="Tahun">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= (int) $y === (int) $tahun ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($_tabs as $k => [$path, $ikon, $label]): ?>
    <li class="nav-item">
        <a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="<?= $url($path, $k === 'pos' ? [] : $_bawaQ) ?>">
            <i class="bi <?= $ikon ?> me-1"></i><?= $label ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
