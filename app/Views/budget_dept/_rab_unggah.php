<?php /* Modal unggah PDF bertanda tangan e-Sign (RAB & laporan realisasi). Tombol pemicu: data-aksi, data-nomor. */ ?>
<div class="modal fade" id="mUnggah" tabindex="-1" aria-labelledby="mUnggahJudul" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" enctype="multipart/form-data" id="fUnggah">
    <?= csrf_field() ?>
    <div class="modal-header">
        <h5 class="modal-title fw-semibold" id="mUnggahJudul"><i class="bi bi-upload me-2"></i>Unggah berkas bertanda tangan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body">
        <p class="small text-muted">Unggah PDF hasil PAM e-Sign yang <strong>sudah lengkap</strong> tanda tangannya. Setelah diunggah, dokumen berstatus disetujui/disahkan.</p>
        <div class="mb-3">
            <label class="form-label small fw-semibold" for="uNomor">Ketik ulang nomor dokumen</label>
            <input type="text" name="nomor" id="uNomor" class="form-control" autocomplete="off" required>
            <div class="form-text">Nomor tercetak di PDF: <code id="uNomorAsli"></code> — memastikan berkas yang diunggah versi yang benar.</div>
        </div>
        <div class="mb-1">
            <label class="form-label small fw-semibold" for="uBerkas">Berkas PDF</label>
            <input type="file" name="berkas" id="uBerkas" class="form-control" accept="application/pdf,.pdf" required>
            <div class="form-text">PDF, maks 20 MB.</div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Unggah &amp; setujui</button>
    </div>
</form>
</div></div></div>
<script>
document.getElementById('mUnggah')?.addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('fUnggah').action = b.dataset.aksi;
    document.getElementById('uNomorAsli').textContent = b.dataset.nomor;
    document.getElementById('uNomor').value = '';
});
</script>
