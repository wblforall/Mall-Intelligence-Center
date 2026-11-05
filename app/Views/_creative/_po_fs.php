<?php
/* Pemilih "Tautkan PO FlowStore" di form realisasi Creative, Content Event, dan Dekorasi & VM. Opsional: tanpa pilihan = realisasi manual biasa.
   Logika di public/js/creative-po-fs.js; antrean dibaca dari $antrean (bawaan creative/antrean-po) saat panel dibuka.
   Variabel opsional: $antrean (path endpoint), $ket (kalimat penjelasan), $dept (singkatan dept untuk teks kosong). */
$antrean = $antrean ?? 'creative/antrean-po';
$ket     = $ket ?? 'Pilih barang yang sudah diterima dari PO FlowStore. Nilai dan tanggal realisasi mengikuti BTB, dan barang yang dipilih langsung tercatat di Budget Departemen CCD sesuai posnya.';
$dept    = $dept ?? 'CCD';
?>
<div class="po-fs" data-po-fs data-url="<?= base_url($antrean) ?>" data-dept="<?= esc($dept, 'attr') ?>">
    <input type="hidden" name="fs_po" value="">
    <button type="button" class="po-fs-buka" data-po-fs-buka aria-expanded="false"><i class="bi bi-box-seam"></i><span>Tautkan PO FlowStore</span><span class="po-fs-ringkas" data-po-fs-ringkas></span></button>
    <div class="po-fs-panel" data-po-fs-panel hidden>
        <p class="po-fs-ket"><?= esc($ket) ?></p>
        <div class="po-fs-cari">
            <input type="search" class="form-control form-control-sm" placeholder="Cari no. PO, barang, supplier, keperluan" data-po-fs-q aria-label="Cari PO FlowStore">
        </div>
        <div class="po-fs-daftar" data-po-fs-daftar><div class="po-fs-kosong">Memuat antrean FlowStore…</div></div>
    </div>
</div>
