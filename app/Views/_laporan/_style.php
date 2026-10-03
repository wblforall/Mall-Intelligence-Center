<?php
/* Gaya baku Laporan Bulanan MIC (A4 landscape) — dipakai tujuh laporan cetak:
   traffic, parkir (pendapatan & kendaraan), pest, loyalty, sponsorship,
   pemantauan AI.

   Bahasa rupa mengikuti tema PDF OpsJobs (utils/pdfTema.js: sampul, kartuKpi,
   judulBagian, gayaTabel, panel, lencana) tetapi dengan identitas MIC:
   navy #091528 → #0f2a4f → #1e3a6e untuk kop, emas #b8902f / #d4af37 untuk
   aksen. Hanya kop yang gelap; isi tetap putih supaya ramah cetak.

   Semua dibuat lewat CSS pada markup yang sudah ada (.doc-header, .kpi-box,
   .sec-title, .main-table, .chart-panel, …) — laporan tidak wajib diubah.

   Utilitas untuk dipakai per laporan:
     <span class="lencana baik|buruk|waspada|info|ungu|netral">teks</span>
     <span class="chip"><b>Label</b> isi</span>
     <div class="deret-angka"><div><span>Label</span><b>123</b></div>…</div>
     <div class="catatan waspada">teks</div>
     <tr class="empty-row"><td colspan="N">Belum ada data.</td></tr>
     <div class="sec-title tanpa-nomor">…</div>     (judul bagian tanpa angka)
     <div class="putus-halaman"></div>              (mulai halaman baru)
     <div class="kpi-box" style="--aksen:#0ea5e9">  (warna bilah aksen bebas) */
$logoMic = base_url('img/mic-logo.png');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --navy-1: #091528; --navy-2: #0f2a4f; --navy-3: #1e3a6e;
    --emas: #b8902f; --emas-terang: #d4af37; --emas-pucat: #f3e7c4;
    --tinta: #0f172a; --teks: #334155; --redup: #64748b; --redup2: #94a3b8;
    --garis: #e2e8f0; --garis-halus: #edf1f6; --latar: #f8fafc; --zebra: #f6f8fb;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
* { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
html { background: #fff; }
body {
    font-family: 'Inter', Arial, Helvetica, sans-serif; font-size: 10.5px; line-height: 1.4;
    color: var(--teks); background: #fff; counter-reset: bagian;
    font-feature-settings: 'cv11', 'ss01';
}
strong, b { color: var(--tinta); font-weight: 600; }

/* ── Halaman ────────────────────────────────────────────────────────────── */
@page {
    size: A4 landscape; margin: 13mm 14mm 14mm;
    @top-left {
        content: "MALL INTELLIGENCE CENTER";
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; font-weight: 700; letter-spacing: 1.2pt; color: #0f2a4f;
        vertical-align: bottom; padding-bottom: 2.5mm;
    }
    @top-right {
        content: "Laporan Bulanan";
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; color: #94a3b8;
        vertical-align: bottom; padding-bottom: 2.5mm;
    }
    @bottom-left {
        content: "Mall Intelligence Center  ·  PT. Wulandari Bangun Laksana Tbk.";
        font-family: 'Inter', Arial, sans-serif; font-size: 7.5pt; color: #94a3b8;
        vertical-align: middle;
    }
    @bottom-right {
        content: "Hal. " counter(page) " / " counter(pages);
        font-family: 'Inter', Arial, sans-serif; font-size: 7.5pt; font-weight: 600; color: #334155;
        vertical-align: middle;
    }
}
/* Halaman pertama sudah punya kop besar: tanpa kop berjalan. */
@page :first {
    @top-left { content: none; }
    @top-right { content: none; }
}
@media screen {
    html { background: #e9edf3; }
    body {
        max-width: 297mm; margin: 24px auto 40px; padding: 12mm 14mm;
        box-shadow: 0 6px 28px rgba(9, 21, 40, .14); border-radius: 4px;
    }
}
@media print {
    html, body { background: #fff !important; }
    .no-print, #debug-icon, #debug-bar, #toolbarContainer, .debug-bar { display: none !important; }
    .putus-halaman { break-after: page; page-break-after: always; }
}

thead { display: table-header-group; }
tfoot { display: table-row-group; }
.main-table tr { break-inside: avoid; page-break-inside: avoid; }
.sec-title { break-after: avoid; page-break-after: avoid; break-inside: avoid; }
.kpi-row, .kpi-box, .chart-panel, .insight-box, .sign-row, .deret-angka, .catatan, .doc-header {
    break-inside: avoid; page-break-inside: avoid;
}

/* ── Kop (sampul) ───────────────────────────────────────────────────────── */
.doc-header {
    position: relative; overflow: hidden;
    display: flex; justify-content: space-between; align-items: stretch; gap: 24px;
    min-height: 104px; margin-bottom: 18px; padding: 16px 22px 16px 118px;
    border-radius: 12px; color: #fff;
    background:
        radial-gradient(circle at 96% -18%, rgba(255,255,255,.075) 0 118px, transparent 119px),
        radial-gradient(circle at 74% 128%, rgba(255,255,255,.06) 0 62px, transparent 63px),
        radial-gradient(circle at 74% 128%, rgba(212,175,55,.10) 0 66px, transparent 67px),
        linear-gradient(100deg, var(--navy-1) 0%, var(--navy-2) 52%, var(--navy-3) 100%);
}
/* Tile logo: logo MIC bertulisan navy → wadah putih supaya terbaca di latar gelap. */
.doc-header::before {
    content: ''; position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
    width: 82px; height: 68px; border-radius: 10px;
    background: #fff url('<?= $logoMic ?>') center / 92% auto no-repeat;
    box-shadow: 0 0 0 3px rgba(212,175,55,.35);
}
/* Garis emas tipis di kaki kop. */
.doc-header::after {
    content: ''; position: absolute; left: 118px; right: 0; bottom: 0; height: 3px;
    background: linear-gradient(90deg, var(--emas-terang), rgba(212,175,55,.15) 70%, transparent);
}
.doc-header > div:first-child {
    display: flex; flex-direction: column; align-items: flex-start; justify-content: center; min-width: 0;
}
.doc-header > div:first-child::before {
    content: 'MALL INTELLIGENCE CENTER'; order: 0;
    font-size: 8px; font-weight: 600; letter-spacing: 2.2px; color: var(--emas-terang); margin-bottom: 3px;
}
.doc-header .title { order: 1; font-size: 21px; font-weight: 800; line-height: 1.15; color: #fff; letter-spacing: -.2px; }
.doc-header .org   { order: 2; font-size: 9.5px; color: #c7d3e6; margin-top: 3px; }
.doc-header .sub   {
    order: 3; display: inline-flex; align-items: center; gap: 7px; margin-top: 9px;
    padding: 3px 10px 3px 9px; border-radius: 6px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.26);
    font-size: 10.5px; font-weight: 600; color: #fff;
}
.doc-header .sub::before {
    content: 'PERIODE'; font-size: 7.5px; font-weight: 600; letter-spacing: 1.2px; color: var(--emas-terang);
}
.doc-header .meta {
    flex: 0 0 auto; align-self: center; text-align: right;
    font-size: 9.5px; line-height: 1.75; color: #e6ecf5;
    padding-left: 16px; border-left: 1px solid rgba(255,255,255,.16);
}
.doc-header .meta::before {
    content: 'INFORMASI CETAK'; display: block; margin-bottom: 2px;
    font-size: 7.5px; font-weight: 600; letter-spacing: 1.4px; color: var(--emas-terang);
}

/* ── Kartu KPI ──────────────────────────────────────────────────────────── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 20px; }
.kpi-box { --aksen: var(--navy-3); }
/* Warna aksen per varian — termasuk varian lokal lama di tiap laporan. */
.kpi-blue, .kpi-total, .kpi-member, .kpi-deal                   { --aksen: #2563eb; }
.kpi-ewalk                                                      { --aksen: #2a78d6; }
.kpi-green, .kpi-penta, .kpi-aktif, .kpi-real                   { --aksen: #16a34a; }
.kpi-amber, .kpi-avg, .kpi-sebar, .kpi-kum                      { --aksen: #d4a017; }
.kpi-purple, .kpi-hadiah, .kpi-komit                            { --aksen: #7c3aed; }
.kpi-pakai, .kpi-red                                            { --aksen: #dc2626; }
.kpi-gold                                                       { --aksen: var(--emas); }
/* Spesifisitas dua kelas: menetralkan latar/garis berwarna dari varian lokal lama. */
.kpi-row .kpi-box {
    position: relative; flex: 1; min-width: 0;
    background: #fff; border: 1px solid var(--garis); border-radius: 9px;
    padding: 9px 12px 9px 16px;
}
.kpi-row .kpi-box::before {
    content: ''; position: absolute; left: -1px; top: -1px; bottom: -1px; width: 4px;
    border-radius: 9px 0 0 9px; background: var(--aksen);
}
.kpi-label {
    font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px;
    color: var(--redup); margin-bottom: 4px;
}
.kpi-num   { font-size: 20px; font-weight: 800; line-height: 1.12; color: var(--tinta); letter-spacing: -.3px; font-variant-numeric: tabular-nums; }
.kpi-sub   { font-size: 9px; color: var(--redup2); margin-top: 4px; line-height: 1.45; }
.kpi-blue .kpi-num   { color: #1d4ed8; }
.kpi-green .kpi-num  { color: #15803d; }
.kpi-amber .kpi-num  { color: #a16207; }
.kpi-purple .kpi-num { color: #6d28d9; }

/* Lencana selisih berpanah (markup lama: <span class="delta-up">▲ 12%</span>). */
.delta-up, .delta-down {
    display: inline-block; padding: 1px 6px; border-radius: 999px;
    font-size: 8.5px; font-weight: 700; line-height: 1.45; white-space: nowrap; vertical-align: 1px;
}
.delta-up   { color: #15803d; background: #dcfce7; }
.delta-down { color: #b91c1c; background: #fee2e2; }

/* ── Judul bagian (judulBagian) ─────────────────────────────────────────── */
.sec-title {
    counter-increment: bagian;
    position: relative; display: flex; align-items: center; gap: 9px;
    margin: 4px 0 10px; padding-bottom: 7px;
    border-bottom: 1.5px solid var(--garis-halus);
    font-size: 12.5px; font-weight: 700; color: var(--tinta); letter-spacing: -.1px;
    text-transform: none; background: none; border-radius: 0;
}
.sec-title::before {
    content: counter(bagian, decimal-leading-zero);
    flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
    min-width: 25px; height: 20px; padding: 0 5px; border-radius: 5px;
    background: var(--navy-2); color: var(--emas-terang);
    font-size: 9.5px; font-weight: 700; letter-spacing: .3px; font-variant-numeric: tabular-nums;
}
.sec-title.tanpa-nomor { counter-increment: none; }
.sec-title.tanpa-nomor::before { content: ''; min-width: 4px; width: 4px; padding: 0; background: var(--emas); }
.sec-title::after {
    content: ''; position: absolute; left: 0; bottom: -1.5px; width: 64px; height: 2.5px;
    border-radius: 2px; background: var(--emas);
}
.sec-title .sec-sub {
    margin-left: auto; padding-left: 12px; text-align: right;
    font-size: 9px; font-weight: 400; color: var(--redup); letter-spacing: 0; text-transform: none;
}
.duo .sec-title { font-size: 11.5px; }

/* ── Tabel (gayaTabel) ──────────────────────────────────────────────────── */
.main-table {
    width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px;
    font-variant-numeric: tabular-nums;
}
.main-table th {
    background: var(--navy-2); color: #fff; font-size: 9px; font-weight: 600; letter-spacing: .25px;
    padding: 6px 8px; border: 0; text-align: left; white-space: nowrap; vertical-align: middle;
}
.main-table th.text-center { text-align: center; }
.main-table th.num { text-align: right; }
.main-table td {
    padding: 5px 8px; border: 0; border-bottom: 1px solid var(--garis-halus);
    font-size: 10.5px; color: var(--teks); vertical-align: middle;
}
.main-table tbody tr:nth-child(even) td { background: var(--zebra); }
.main-table tfoot td {
    background: #eef2f8; color: var(--tinta); font-weight: 700;
    border-top: 1.5px solid var(--navy-2); border-bottom: 0;
}
.main-table .text-center { text-align: center; }
.num  { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.zero { color: #cbd5e1; text-align: right; }
.subnote { font-size: 8.5px; color: var(--redup2); }
.we-row td { background: #fdf6e3 !important; }
/* Baris "belum ada data". */
.main-table tr.empty-row td, .main-table td.empty {
    text-align: center; color: var(--redup2); font-style: italic; padding: 14px 8px; background: #fbfcfe !important;
}
/* Tabel tanpa satu baris pun di tbody: tampilkan pesan, jangan hanya kepala tabel. */
.main-table:not(:has(tbody tr))::after {
    content: 'Belum ada data untuk periode ini.'; display: table-caption; caption-side: bottom;
    padding: 12px 8px; text-align: center; font-size: 10px; font-style: italic; color: var(--redup2);
    border: 1px solid var(--garis-halus); border-top: 0; border-radius: 0 0 6px 6px; background: #fbfcfe;
}
.duo { display: flex; gap: 16px; }
.duo > div { flex: 1; min-width: 0; }

/* ── Panel analisa & grafik (panel) ─────────────────────────────────────── */
.chart-panel {
    display: flex; gap: 14px; margin-bottom: 20px; padding: 12px;
    background: #fff; border: 1px solid var(--garis); border-radius: 10px;
}
.insight-box {
    flex: 0 0 30%; padding: 10px 12px 8px 13px; border-radius: 8px;
    background: #fbf8ef; border: 1px solid #efe4c4; border-left: 3px solid var(--emas);
}
.insight-title {
    font-size: 8.5px; font-weight: 700; color: #8a6a1f; margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .8px;
}
.insight-list { margin: 0; padding-left: 13px; }
.insight-list li { font-size: 10px; color: var(--teks); line-height: 1.5; margin-bottom: 5px; }
.insight-list li::marker { color: var(--emas); }
.chart-box { flex: 1; min-width: 0; }
.chart-title { font-size: 10px; font-weight: 600; color: var(--tinta); margin-bottom: 6px; }
.chart-wrap { height: 165px; position: relative; min-width: 0; }
/* Grafik Chart.js saat dicetak. Chart.js menulis lebar/tinggi piksel ke gaya inline
   <canvas> menurut tata letak LAYAR dan tidak menggambar ulang sendiri ketika
   halaman ditata ulang untuk kertas. Bila lebar isi di layar ≠ lebar isi kertas,
   canvas lama itu melewati kotaknya (menimpa grafik sebelah / terpotong di tepi
   kertas) atau menyisakan ruang kosong. Dua lapis pengaman:
   1) CSS: di media cetak canvas selalu dipaksa mengisi kotaknya, apa pun gaya
      inline-nya — tidak mungkin lagi meluber.
   2) Skrip di bawah: sebelum cetak, lebar isi layar disamakan dengan lebar isi
      kertas (kelas html.siap-cetak) lalu semua grafik di-resize(), sehingga
      bitmap digambar ulang pada ukuran kertas (tidak gepeng/melar). */
@media print {
    canvas { max-width: 100% !important; }
    .chart-wrap > canvas { width: 100% !important; height: 100% !important; }
}
:root { --lebar-isi-cetak: 269mm; } /* 297mm (A4 lanskap) − margin @page kiri+kanan 2×14mm */
@media screen {
    html.siap-cetak body {
        width: var(--lebar-isi-cetak) !important; max-width: none !important;
        padding-left: 0 !important; padding-right: 0 !important; box-sizing: content-box !important;
    }
}
/* Pada cetak sungguhan isi kertas memang selebar itu (no-op); ini hanya menahan
   pratinjau cetak yang diemulasikan di viewport lebar (mis. alat uji headless). */
@media print { html.siap-cetak body { max-width: var(--lebar-isi-cetak) !important; } }

/* ── Utilitas ───────────────────────────────────────────────────────────── */
.lencana {
    display: inline-block; padding: 1.5px 7px; border-radius: 999px; white-space: nowrap;
    font-size: 8.5px; font-weight: 600; line-height: 1.45; vertical-align: 1px;
    color: #475569; background: #f1f5f9;
}
.lencana.baik    { color: #15803d; background: #dcfce7; }
.lencana.buruk   { color: #b91c1c; background: #fee2e2; }
.lencana.waspada { color: #b45309; background: #fef3c7; }
.lencana.info    { color: #0369a1; background: #e0f2fe; }
.lencana.ungu    { color: #6d28d9; background: #ede9fe; }
.lencana.netral  { color: #475569; background: #f1f5f9; }
.lencana.emas    { color: #7a5b14; background: var(--emas-pucat); }
.chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 2px 9px; border-radius: 6px;
    border: 1px solid var(--garis); background: var(--latar); font-size: 9.5px; font-weight: 600; color: var(--tinta);
}
.chip > b:first-child, .chip > .chip-label {
    font-size: 7.5px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--emas);
}
.deret-angka {
    display: flex; margin-bottom: 16px; border: 1px solid var(--garis); border-radius: 8px; background: var(--latar);
}
.deret-angka > div { flex: 1; min-width: 0; padding: 7px 12px; }
.deret-angka > div + div { border-left: 1px solid var(--garis); }
.deret-angka span { display: block; font-size: 8.5px; color: var(--redup); }
.deret-angka b { display: block; font-size: 14px; font-weight: 700; color: var(--tinta); font-variant-numeric: tabular-nums; }
.deret-angka b.baik { color: #15803d; } .deret-angka b.buruk { color: #b91c1c; } .deret-angka b.waspada { color: #b45309; }
.catatan {
    margin-bottom: 14px; padding: 7px 11px; border-radius: 6px; border-left: 3px solid #94a3b8;
    background: #f1f5f9; color: #334155; font-size: 10px;
}
.catatan.baik    { background: #f0fdf4; border-left-color: #16a34a; color: #166534; }
.catatan.buruk   { background: #fef2f2; border-left-color: #dc2626; color: #991b1b; }
.catatan.waspada { background: #fffbeb; border-left-color: #d97706; color: #92400e; }
.catatan.info    { background: #f0f9ff; border-left-color: #0284c7; color: #075985; }

/* ── Tanda tangan (TandaTangan) ─────────────────────────────────────────── */
.sign-row { display: flex; gap: 32px; margin-top: 22px; }
.sign-box {
    flex: 1; min-width: 0; text-align: center; font-size: 10px; color: var(--redup);
    border: 0; padding: 0;
}
.sign-box .sign-label { font-size: 10px; color: var(--teks); }
.sign-box .sign-pair  { display: flex; gap: 18px; }
.sign-box .sign-pair > div { flex: 1; min-width: 0; }
.sign-box .sign-space { height: 46px; }
.sign-box .sign-role {
    display: block; max-width: 230px; margin: 0 auto; padding-top: 4px; border-top: 1px solid #334155;
    font-weight: 700; color: var(--tinta); font-size: 10.5px; text-decoration: none;
}
.sign-box .sign-jabatan { font-size: 8.5px; color: var(--redup); margin-top: 2px; }

/* ── Kaki dokumen ───────────────────────────────────────────────────────── */
.doc-footer {
    margin-top: 22px; padding-top: 7px; border-top: 1px solid var(--garis);
    display: flex; justify-content: space-between; gap: 16px; font-size: 8.5px; color: var(--redup2);
}
.doc-footer span:first-child { color: var(--navy-2); font-weight: 600; }

/* ── Tombol cetak (layar saja) ──────────────────────────────────────────── */
.btn-print {
    position: fixed; top: 16px; right: 16px; z-index: 50;
    display: inline-flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, var(--navy-2), var(--navy-3)); color: #fff;
    border: 1px solid rgba(212,175,55,.55); padding: 9px 18px; border-radius: 8px; cursor: pointer;
    font: 600 12px 'Inter', Arial, sans-serif; letter-spacing: .2px;
    box-shadow: 0 4px 14px rgba(9,21,40,.28);
}
/* Teks tombol lama memakai karakter 🖶 yang tak ada di banyak font → ganti lewat CSS. */
.btn-print { font-size: 0; }
.btn-print::before {
    content: ''; width: 14px; height: 14px; flex: 0 0 14px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23d4af37' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9V2h12v7'/%3E%3Cpath d='M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2'/%3E%3Crect x='6' y='14' width='12' height='8'/%3E%3C/svg%3E") center / contain no-repeat;
}
.btn-print::after { content: 'Cetak / Simpan PDF'; font-size: 12px; }
.btn-print:hover { background: linear-gradient(135deg, var(--navy-1), var(--navy-2)); border-color: var(--emas-terang); }
</style>
<script>
/* Gambar ulang semua grafik Chart.js pada ukuran kertas ketika dicetak (Ctrl+P,
   tombol Cetak, window.print() otomatis, atau cetak-ke-PDF headless). Aman bila
   Chart.js tidak dimuat. Lihat catatan "Grafik Chart.js saat dicetak" di atas. */
(function () {
    function semuaGrafik() {
        var C = window.Chart;
        if (!C || !C.instances) return [];
        return Object.keys(C.instances).map(function (k) { return C.instances[k]; });
    }
    function ukurUlang() {
        semuaGrafik().forEach(function (g) { try { g.resize(); } catch (e) {} });
    }
    function mauCetak() {
        document.documentElement.classList.add('siap-cetak');
        ukurUlang();
    }
    function selesaiCetak() {
        document.documentElement.classList.remove('siap-cetak');
        ukurUlang();
    }
    window.addEventListener('beforeprint', mauCetak);
    window.addEventListener('afterprint', selesaiCetak);
    // Chrome/Edge/Safari juga memicu perubahan media saat tata letak cetak aktif.
    if (window.matchMedia) {
        var mq = window.matchMedia('print');
        var ubah = function (e) { if (e.matches) mauCetak(); else selesaiCetak(); };
        if (mq.addEventListener) mq.addEventListener('change', ubah); else if (mq.addListener) mq.addListener(ubah);
    }
})();
</script>
