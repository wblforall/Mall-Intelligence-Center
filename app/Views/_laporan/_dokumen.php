<?php
/* Gaya baku DOKUMEN / FORMULIR cetak MIC (A4 tegak; landscape bila diminta).

   Saudara ringan dari _laporan/_style.php (laporan bulanan): warna & tipografi
   sama — Inter, navy #091528 → #1e3a6e, emas #b8902f / #d4af37 — tetapi kopnya
   tipis ala KertasLaporan OpsJobs (label kecil, judul tebal, logo di kanan,
   pita gradien tipis), bukan sampul gelap besar. Isi tetap putih & ramah cetak,
   dengan garis tabel yang cukup tegas supaya formulir bisa diisi tangan.

   Pemakaian (di <head>):
     view('_laporan/_dokumen', ['orientasi' => 'landscape', 'labelHalaman' => 'Booking Sheet'])
   Param opsional: orientasi ('portrait' bawaan | 'landscape'), labelHalaman
   (teks kop berjalan halaman 2 dst).

   Kerangka markup:
     <button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>
     <header class="dk-kop">
        <div><div class="dk-label">Mall Intelligence Center</div>
             <h1 class="dk-judul">Judul</h1><div class="dk-sub">Subjudul</div></div>
        <img class="dk-logo" src="…/img/mic-logo.png" alt="MIC">
     </header>
     <div class="dk-pita"></div>
     <dl class="dk-identitas"><div><dt>Label</dt><dd>Isi</dd></div>…</dl>
     <h2 class="dk-bagian">Judul Bagian <span class="dk-bagian-ket">keterangan</span></h2>
     <table class="dk-tabel">…</table>          (.dk-isian → baris kosong utk tulisan tangan)
     <div class="dk-kotak [info|waspada|buruk|baik]">teks</div>
     <div class="dk-ttd"><div><div class="dk-ttd-peran">Peran</div><div class="dk-ttd-ruang"></div>
          <div class="dk-ttd-nama">Nama</div><div class="dk-ttd-jabatan">Jabatan</div></div>…</div>
     <div class="dk-utuh">…</div>   (bagian yang harus tetap satu halaman)
     <footer class="dk-kaki"><span><b>Mall Intelligence Center</b> · dicetak …</span><span>…</span></footer>
     <span class="dk-lencana [biru|hijau|kuning|merah|abu]">…</span> */
$orientasi    = ($orientasi ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
$labelHalaman = addcslashes(strip_tags($labelHalaman ?? 'Dokumen'), '"\\');
$lebarKertas  = $orientasi === 'landscape' ? '297mm' : '210mm';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --navy-1: #091528; --navy-2: #0f2a4f; --navy-3: #1e3a6e;
    --emas: #b8902f; --emas-terang: #d4af37; --emas-pucat: #f3e7c4;
    --tinta: #0f172a; --teks: #334155; --redup: #64748b; --redup2: #94a3b8;
    --garis: #e2e8f0; --garis-isian: #cfd8e3; --garis-halus: #edf1f6; --latar: #f8fafc; --zebra: #f6f8fb;
}
*, *::before, *::after { box-sizing: border-box; }
* { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
html { background: #fff; }
body {
    margin: 0; padding: 0;
    font-family: 'Inter', Arial, Helvetica, sans-serif; font-size: 10.5px; line-height: 1.45;
    color: var(--teks); background: #fff;
}
strong, b { color: var(--tinta); font-weight: 600; }

/* ── Halaman ────────────────────────────────────────────────────────────── */
@page {
    size: A4 <?= $orientasi ?>; margin: 12mm 13mm 14mm;
    @top-left {
        content: "MALL INTELLIGENCE CENTER";
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; font-weight: 700; letter-spacing: 1.2pt; color: #0f2a4f;
        vertical-align: bottom; padding-bottom: 2.5mm;
    }
    @top-right {
        content: "<?= $labelHalaman ?>";
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; color: #94a3b8;
        vertical-align: bottom; padding-bottom: 2.5mm;
    }
    @bottom-left {
        content: "Mall Intelligence Center  ·  PT. Wulandari Bangun Laksana Tbk.";
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; color: #94a3b8; vertical-align: middle;
    }
    @bottom-right {
        content: "Hal. " counter(page) " / " counter(pages);
        font-family: 'Inter', Arial, sans-serif; font-size: 7pt; font-weight: 600; color: #334155; vertical-align: middle;
    }
}
@page :first { @top-left { content: none; } @top-right { content: none; } }
@media screen {
    html { background: #e9edf3; }
    body {
        max-width: <?= $lebarKertas ?>; min-height: 280mm; margin: 24px auto 40px; padding: 12mm 13mm;
        background: #fff; box-shadow: 0 6px 28px rgba(9, 21, 40, .14); border-radius: 4px;
    }
}
@media print {
    html, body { background: #fff !important; }
    .no-print, .noprint, .dk-tombol, #debug-icon, #debug-bar, #toolbarContainer, .debug-bar { display: none !important; }
    .dk-putus { break-after: page; page-break-after: always; }
}
thead { display: table-header-group; }
/* .dk-utuh: kelompok yang tak boleh terbelah (mis. tanda tangan + catatan + kaki). */
.dk-tabel tr, .dk-kotak, .dk-ttd, .dk-identitas, .dk-kop, .dk-utuh { break-inside: avoid; page-break-inside: avoid; }
.dk-bagian { break-after: avoid; page-break-after: avoid; }

/* ── Kop ────────────────────────────────────────────────────────────────── */
.dk-kop { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 9px; }
.dk-kop > div:first-child { min-width: 0; }
.dk-label { font-size: 8px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--emas); }
.dk-judul { margin: 1px 0 0; font-size: 18px; font-weight: 800; line-height: 1.2; color: var(--navy-1); letter-spacing: -.2px; }
.dk-sub   { margin-top: 2px; font-size: 11px; color: var(--redup); }
.dk-sub b { color: var(--teks); }
.dk-logo  { flex: 0 0 auto; height: 38px; width: auto; }
.dk-pita {
    height: 5px; border-radius: 999px; margin-bottom: 12px;
    background: linear-gradient(90deg, var(--navy-1) 0%, var(--navy-3) 62%, var(--emas) 86%, var(--emas-terang) 100%);
}

/* ── Identitas dua kolom ───────────────────────────────────────────────── */
.dk-identitas {
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 0 0 14px; font-size: 10.5px;
}
.dk-identitas > div { display: flex; gap: 8px; min-width: 0; }
.dk-identitas > div.penuh { grid-column: 1 / -1; }
.dk-identitas dt { flex: 0 0 118px; color: var(--redup); }
.dk-identitas dd { margin: 0; min-width: 0; font-weight: 600; color: var(--tinta); }

/* ── Judul bagian ──────────────────────────────────────────────────────── */
.dk-bagian {
    position: relative; display: flex; align-items: baseline; gap: 10px;
    margin: 14px 0 8px; padding-bottom: 4px; border-bottom: 2px solid var(--garis-halus);
    font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: var(--tinta);
}
.dk-bagian::after {
    content: ''; position: absolute; left: 0; bottom: -2px; width: 64px; height: 2px; background: var(--emas);
}
.dk-bagian-ket { margin-left: auto; font-size: 9px; font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--redup); }

/* ── Tabel formulir ─────────────────────────────────────────────────────── */
.dk-tabel { width: calc(100% - 1px); border-collapse: collapse; margin-bottom: 10px; font-variant-numeric: tabular-nums; }
.dk-tabel th {
    background: var(--navy-2); color: #fff; font-size: 9px; font-weight: 600; letter-spacing: .25px;
    padding: 6px 8px; border: 1px solid var(--navy-2); text-align: left; vertical-align: middle;
}
.dk-tabel td {
    padding: 5px 8px; border: 1px solid var(--garis-isian); font-size: 10px; color: var(--teks); vertical-align: top;
}
.dk-tabel tbody tr:nth-child(even) > td { background: var(--zebra); }
.dk-tabel td.dk-sel-label { color: var(--redup); background: var(--latar); }
.dk-tabel tr.dk-grup > td {
    background: #eef2f8 !important; color: var(--navy-2); font-weight: 700; font-size: 9.5px;
    text-transform: uppercase; letter-spacing: .4px;
}
.dk-tabel tr.dk-total > td { background: #eef2f8 !important; color: var(--tinta); font-weight: 700; border-top: 1.5px solid var(--navy-2); }
.dk-tabel .c { text-align: center; } .dk-tabel .r { text-align: right; }
.dk-tabel td.dk-isian { height: 46px; background: #fff !important; }
.dk-tabel .dk-kosong { text-align: center; color: var(--redup2); font-style: italic; padding: 12px; }

/* ── Kotak teks ─────────────────────────────────────────────────────────── */
.dk-kotak {
    padding: 8px 11px; border-radius: 6px; border-left: 3px solid var(--navy-3);
    background: var(--latar); color: var(--teks); font-size: 10.5px;
}
.dk-kotak.info    { border-left-color: #0284c7; background: #f0f9ff; }
.dk-kotak.waspada { border-left-color: #d97706; background: #fffbeb; }
.dk-kotak.buruk   { border-left-color: #dc2626; background: #fef2f2; }
.dk-kotak.baik    { border-left-color: #16a34a; background: #f0fdf4; }

/* ── Lencana ────────────────────────────────────────────────────────────── */
.dk-lencana {
    display: inline-block; padding: 1.5px 8px; border-radius: 999px; white-space: nowrap;
    font-size: 8.5px; font-weight: 600; line-height: 1.45; color: #475569; background: #f1f5f9;
}
.dk-lencana.biru  { color: #1d4ed8; background: #dbeafe; }
.dk-lencana.hijau { color: #15803d; background: #dcfce7; }
.dk-lencana.kuning{ color: #b45309; background: #fef3c7; }
.dk-lencana.merah { color: #b91c1c; background: #fee2e2; }
.dk-lencana.abu   { color: #475569; background: #f1f5f9; }

/* ── Tanda tangan ───────────────────────────────────────────────────────── */
.dk-ttd { display: grid; grid-auto-columns: minmax(0, 1fr); grid-auto-flow: column; gap: 28px; margin-top: 20px; }
.dk-ttd > div { text-align: center; font-size: 10px; color: var(--teks); }
.dk-ttd-peran   { font-size: 10px; color: var(--teks); }
.dk-ttd-ruang   { height: 54px; }
.dk-ttd-nama    { margin: 0 12px; padding-top: 4px; border-top: 1px solid var(--teks); font-weight: 700; color: var(--tinta); min-height: 19px; }
.dk-ttd-jabatan { font-size: 8.5px; color: var(--redup); margin-top: 1px; }

/* ── Kaki ───────────────────────────────────────────────────────────────── */
.dk-kaki {
    margin-top: 16px; padding-top: 6px; border-top: 1px solid var(--garis);
    display: flex; justify-content: space-between; gap: 16px; font-size: 8.5px; color: var(--redup2);
}
.dk-kaki b { color: var(--navy-2); font-weight: 700; }

/* ── Tombol cetak (layar saja) ──────────────────────────────────────────── */
.dk-tombol {
    position: fixed; top: 16px; right: 16px; z-index: 50;
    display: inline-flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, var(--navy-2), var(--navy-3)); color: #fff;
    border: 1px solid rgba(212,175,55,.55); padding: 9px 18px; border-radius: 8px; cursor: pointer;
    font: 600 12px 'Inter', Arial, sans-serif; letter-spacing: .2px; box-shadow: 0 4px 14px rgba(9,21,40,.28);
}
.dk-tombol::before {
    content: ''; width: 14px; height: 14px; flex: 0 0 14px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23d4af37' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9V2h12v7'/%3E%3Cpath d='M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2'/%3E%3Crect x='6' y='14' width='12' height='8'/%3E%3C/svg%3E") center / contain no-repeat;
}
.dk-tombol:hover { background: linear-gradient(135deg, var(--navy-1), var(--navy-2)); border-color: var(--emas-terang); }
</style>
