<?php
/**
 * Modal "Pilih titik" (satu per halaman). Daftar titik dimuat lewat JS (creative-titik.js) dari
 * GET creative/titik/{scope}/{itemId}/daftar: tombol [data-tp-open] mengisi action form dan memuat ketersediaan.
 * Variabel: $pfx ('cr'|'ev')
 */
?>
<div class="modal fade tk-modal" id="titikPickModal" tabindex="-1" aria-labelledby="titikPickTitle" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
<form id="titikPickForm" method="POST" action="">
<?= csrf_field() ?>
<div class="modal-header">
    <div class="min-w-0">
        <h5 class="modal-title fw-semibold" id="titikPickTitle">Pilih titik</h5>
        <div class="small text-muted text-truncate" id="titikPickNama"></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <div class="tp-info" id="tpInfo"></div>
    <div class="tp-tools">
        <div class="tp-search"><i class="bi bi-search"></i><input type="search" id="tpQ" placeholder="Cari kode, mall, atau area…" autocomplete="off" aria-label="Cari titik"></div>
        <label class="tp-only"><input type="checkbox" id="tpBebas"> Hanya yang tersedia</label>
    </div>
    <div id="tpList" class="tp-list" aria-live="polite"></div>
    <div class="tp-nomatch" id="tpNoMatch" hidden>Tidak ada titik yang cocok dengan pencarian.</div>
    <div class="tp-error" id="tpError" hidden></div>
</div>
<div class="modal-footer">
    <span class="tp-sum" id="tpSum" aria-live="polite">Belum ada yang dipilih</span>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary" id="tpSubmit" disabled><i class="bi bi-check2-square me-1"></i>Pilih titik &amp; buat pemesanan</button>
</div>
</form>
</div></div></div>
