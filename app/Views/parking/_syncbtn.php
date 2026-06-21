<?php // Tombol tarik data SPI terbaru (sync 7 hari) — butuh route POST parking/sync ?>
<div class="d-inline-flex align-items-center gap-2 flex-wrap">
    <button id="pk-sync-btn" type="button" class="btn btn-outline-success btn-sm">
        <i class="bi bi-cloud-arrow-down"></i> Tarik Data Terbaru
    </button>
    <span id="pk-sync-status" class="small text-secondary"></span>
</div>
<script>
(function () {
    const btn = document.getElementById('pk-sync-btn');
    if (!btn || btn.dataset.bound) return;
    btn.dataset.bound = '1';
    const st = document.getElementById('pk-sync-status');
    const NAME = '<?= csrf_token() ?>';
    let csrf = '<?= csrf_hash() ?>';
    const orig = btn.innerHTML;
    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menarik…';
        if (st) { st.className = 'small text-secondary'; st.textContent = 'Menghubungi SPI, mohon tunggu (~30 dtk)…'; }
        try {
            const body = new URLSearchParams();
            body.append(NAME, csrf);
            const r = await fetch('<?= base_url('parking/sync') ?>', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const d = await r.json();
            if (d && d.csrf) csrf = d.csrf;
            if (d && d.ok) {
                if (st) { st.className = 'small text-success'; st.textContent = '✓ ' + d.message + ' — memuat ulang…'; }
                setTimeout(() => location.reload(), 1200);
            } else {
                if (st) { st.className = 'small text-danger'; st.textContent = '✗ ' + ((d && d.message) || 'Gagal sinkronisasi.'); }
                btn.disabled = false; btn.innerHTML = orig;
            }
        } catch (e) {
            if (st) { st.className = 'small text-danger'; st.textContent = '✗ Gagal menghubungi server.'; }
            btn.disabled = false; btn.innerHTML = orig;
        }
    });
})();
</script>
