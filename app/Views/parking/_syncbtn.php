<?php // Tombol + overlay tarik data SPI terbaru (sync 7 hari). Butuh route POST parking/sync ?>
<style>
.pksync-ov { position:fixed; inset:0; z-index:1080; display:none; align-items:center; justify-content:center;
    background:rgba(15,23,42,.6); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); }
.pksync-ov.show { display:flex; }
.pksync-card { background:#1e293b; color:#e2e8f0; border:1px solid rgba(148,163,184,.25); border-radius:1rem;
    padding:1.6rem 1.8rem; width:min(94%,410px); text-align:center; box-shadow:0 12px 48px rgba(0,0,0,.45); }
.pksync-spin { width:2.6rem; height:2.6rem; border:3px solid rgba(148,163,184,.3); border-top-color:#22c55e;
    border-radius:50%; margin:.2rem auto 1rem; animation:pksyncspin .8s linear infinite; }
@keyframes pksyncspin { to { transform:rotate(360deg) } }
.pksync-prog { height:8px; background:rgba(148,163,184,.25); border-radius:6px; overflow:hidden; margin:1rem 0 .35rem; }
.pksync-bar { height:100%; width:0; background:linear-gradient(90deg,#16a34a,#22c55e); border-radius:6px; transition:width .5s ease; }
.pksync-ico { font-size:2.8rem; line-height:1; margin:.1rem auto .5rem; }
.pksync-card .small.text-secondary { color:#94a3b8 !important; }
</style>
<button id="pk-sync-btn" type="button" class="btn btn-outline-success btn-sm">
    <i class="bi bi-cloud-arrow-down"></i> Tarik Data Terbaru
</button>
<div id="pksync-ov" class="pksync-ov">
    <div class="pksync-card">
        <div id="pksync-loading">
            <div class="pksync-spin"></div>
            <div class="fw-semibold mb-1"><i class="bi bi-cloud-arrow-down text-success me-1"></i>Menarik data terbaru dari SPI</div>
            <div id="pksync-status" class="small text-secondary">Menyambung ke server SPI…</div>
            <div class="pksync-prog"><div id="pksync-bar" class="pksync-bar"></div></div>
            <div class="small text-secondary" style="font-size:.72rem">Menyinkronkan 7 hari terakhir — butuh beberapa detik.</div>
        </div>
        <div id="pksync-result" style="display:none">
            <div id="pksync-ico" class="pksync-ico"></div>
            <div id="pksync-msg" class="fw-semibold mb-1"></div>
            <div id="pksync-detail" class="small text-secondary mb-3" style="font-size:.74rem"></div>
            <div class="d-flex justify-content-center gap-2">
                <button id="pksync-close" type="button" class="btn btn-sm btn-outline-secondary">Tutup</button>
                <button id="pksync-reload" type="button" class="btn btn-sm btn-success" style="display:none"><i class="bi bi-arrow-clockwise"></i> Muat ulang</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const btn = document.getElementById('pk-sync-btn');
    if (!btn || btn.dataset.bound) return;
    btn.dataset.bound = '1';
    const $ = id => document.getElementById(id);
    const ov = $('pksync-ov');
    const NAME = '<?= csrf_token() ?>';
    let csrf = '<?= csrf_hash() ?>';
    const stages = [
        { p:15, t:'Menyambung ke server SPI…' },
        { p:40, t:'Menarik kendaraan & income…' },
        { p:65, t:'Menarik metode pembayaran…' },
        { p:88, t:'Menarik distribusi durasi…' },
    ];
    let si = 0, timer = null, reloadTimer = null, done = false;
    function tick() {
        if (done || si >= stages.length) return;
        const s = stages[si++];
        $('pksync-bar').style.width = s.p + '%';
        $('pksync-status').textContent = s.t;
        timer = setTimeout(tick, 1200);
    }
    function resetLoading() {
        done = false; si = 0; clearTimeout(timer); clearTimeout(reloadTimer);
        $('pksync-loading').style.display = ''; $('pksync-result').style.display = 'none';
        $('pksync-bar').style.width = '0'; $('pksync-reload').style.display = 'none';
    }
    function showResult(status, msg, detail) {
        done = true; clearTimeout(timer); $('pksync-bar').style.width = '100%';
        $('pksync-loading').style.display = 'none'; $('pksync-result').style.display = '';
        const ico = $('pksync-ico');
        if (status === 'new' || status === 'updated') {
            ico.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            $('pksync-reload').style.display = '';
        } else if (status === 'nochange') {
            ico.innerHTML = '<i class="bi bi-info-circle-fill text-info"></i>';
        } else {
            ico.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
        }
        $('pksync-msg').textContent = msg;
        $('pksync-detail').textContent = detail || '';
    }
    function close() { ov.classList.remove('show'); resetLoading(); }
    $('pksync-close').addEventListener('click', close);
    $('pksync-reload').addEventListener('click', () => location.reload());
    ov.addEventListener('click', e => { if (e.target === ov && done) close(); });

    btn.addEventListener('click', async () => {
        resetLoading();
        ov.classList.add('show');
        $('pksync-status').textContent = 'Menyambung ke server SPI…';
        tick();
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
                showResult(d.status, d.message, d.detail);
                if (d.status === 'new' || d.status === 'updated') {
                    reloadTimer = setTimeout(() => { if (ov.classList.contains('show')) location.reload(); }, 3000);
                }
            } else {
                showResult('error', (d && d.message) || 'Gagal sinkronisasi.', '');
            }
        } catch (e) {
            showResult('error', 'Gagal menghubungi server.', '');
        }
    });
})();
</script>
