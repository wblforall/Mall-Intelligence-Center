<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.bd-pegangan { cursor: grab; color: var(--bs-secondary-color); width: 34px; text-align: center; touch-action: none; }
.bd-pegangan:hover { color: var(--bs-body-color); }
.bd-pos-hantu { opacity: .45; background: rgba(var(--bs-primary-rgb), .08) !important; }
.bd-pos-dipilih { box-shadow: 0 6px 18px rgba(0,0,0,.25); }
#bd-daftar-pos button[data-geser]:disabled { visibility: hidden; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>

<div class="row g-3">
<div class="col-lg-8">
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0 fw-semibold">Pos Budget <?= esc($dept['name']) ?></h6>
            <?php if ($canEdit): ?>
            <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#mPos" data-pos="">
                <i class="bi bi-plus-lg me-1"></i>Tambah Pos</button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.86rem">
            <thead><tr class="small text-muted">
                <?php if ($canEdit): ?><th style="width:34px"><span class="visually-hidden">Geser</span></th><?php endif; ?>
                <th style="width:40px">#</th><th>Nama pos</th><th class="text-center">Status</th>
                <?php if ($canEdit): ?><th class="text-end" style="width:150px"></th><?php endif; ?>
            </tr></thead>
            <tbody id="bd-daftar-pos">
            <?php if (! $pos): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pos. Tambahkan pos sesuai baris di Excel budget departemen Anda.</td></tr>
            <?php endif; ?>
            <?php foreach ($pos as $i => $p):
                $n = $jumlah[$p['id']] ?? ['n_rencana' => 0, 'n_realisasi' => 0];
                $punyaData = $n['n_rencana'] > 0 || $n['n_realisasi'] > 0;
            ?>
            <tr class="<?= empty($p['aktif']) ? 'text-body-tertiary' : '' ?>" data-id="<?= (int) $p['id'] ?>">
                <?php if ($canEdit): ?>
                <td class="bd-pegangan" title="Seret untuk memindah urutan" aria-hidden="true"><i class="bi bi-grip-vertical"></i></td>
                <?php endif; ?>
                <td class="text-muted" data-nomor><?= $i + 1 ?></td>
                <td>
                    <?= esc($p['nama']) ?>
                    <?php if ($p['keterangan']): ?><div class="small text-muted"><?= esc($p['keterangan']) ?></div><?php endif; ?>
                </td>
                <td class="text-center">
                    <?= ! empty($p['aktif']) ? '<span class="badge text-bg-success-subtle text-success-emphasis">aktif</span>' : '<span class="badge text-bg-secondary">nonaktif</span>' ?>
                </td>
                <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                    <button type="button" class="btn btn-sm btn-link p-0 me-1" data-geser="-1" <?= $i === 0 ? 'disabled' : '' ?> title="Pindah ke atas" aria-label="Pindah <?= esc($p['nama']) ?> ke atas"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-link p-0 me-1" data-geser="1" <?= $i === count($pos) - 1 ? 'disabled' : '' ?> title="Pindah ke bawah" aria-label="Pindah <?= esc($p['nama']) ?> ke bawah"><i class="bi bi-arrow-down"></i></button>
                    <button class="btn btn-sm btn-link p-0 ms-2" title="Ubah" aria-label="Ubah" data-bs-toggle="modal" data-bs-target="#mPos"
                            data-pos="<?= esc(json_encode(['id' => $p['id'], 'nama' => $p['nama'], 'keterangan' => $p['keterangan'], 'aktif' => (int) $p['aktif']])) ?>">
                        <i class="bi bi-pencil"></i></button>
                    <?php if (! $punyaData): ?>
                    <form method="POST" action="<?= $url('pos/' . $p['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus pos ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-link p-0 ms-2 text-danger" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span class="d-inline-block ms-2" style="width:16px"></span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <p class="small text-muted mt-2">Pos yang sudah punya budget atau realisasi tidak bisa dihapus. Nonaktifkan bila tak dipakai lagi; angkanya tetap tampil di rekap tahun yang bersangkutan.</p>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-header"><h6 class="mb-0 fw-semibold">Pengaturan</h6></div>
        <div class="card-body">
            <form method="POST" action="<?= $url('setting') ?>">
                <?= csrf_field() ?>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="pisahMal" name="pisah_mal" value="1"
                           <?= $pisahMal ? 'checked' : '' ?> <?= $canEdit ? 'onchange="this.form.submit()"' : 'disabled' ?>>
                    <label class="form-check-label" for="pisahMal">Pisah budget per mal (eWalk / Pentacity)</label>
                </div>
                <div class="small text-muted mt-2">
                    Bila aktif, budget diisi per mal dan tiap realisasi memilih eWalk, Pentacity, atau Keduanya (dibagi 50:50).
                    Rekap bisa dilihat gabungan atau per mal.
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="mPos" tabindex="-1" aria-labelledby="mPosJudul" aria-hidden="true">
<div class="modal-dialog">
<form method="POST" action="<?= $url('pos/simpan') ?>" class="modal-content">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="">
    <div class="modal-header">
        <h6 class="modal-title" id="mPosJudul">Tambah Pos</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label small" for="pos-nama">Nama pos</label>
            <input id="pos-nama" name="nama" maxlength="150" class="form-control form-control-sm" required placeholder="mis. Biaya reimburse poin">
        </div>
        <div class="mb-3">
            <label class="form-label small" for="pos-ket">Keterangan <span class="text-muted">(opsional)</span></label>
            <textarea id="pos-ket" name="keterangan" rows="2" class="form-control form-control-sm"></textarea>
        </div>
        <div class="form-check" id="pos-aktif-wrap">
            <input class="form-check-input" type="checkbox" name="aktif" value="1" id="pos-aktif" checked>
            <label class="form-check-label small" for="pos-aktif">Aktif</label>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
    </div>
</form>
</div>
</div>
<script>
(function () {
    const m = document.getElementById('mPos'), f = m.querySelector('form');
    m.addEventListener('show.bs.modal', e => {
        const d = e.relatedTarget && e.relatedTarget.dataset.pos ? JSON.parse(e.relatedTarget.dataset.pos) : null;
        f.elements['id'].value = d ? d.id : '';
        f.nama.value = d ? d.nama : '';
        f.keterangan.value = d && d.keterangan ? d.keterangan : '';
        f.aktif.checked = d ? !!d.aktif : true;
        document.getElementById('pos-aktif-wrap').hidden = !d;
        document.getElementById('mPosJudul').textContent = d ? 'Ubah Pos' : 'Tambah Pos';
    });
    m.addEventListener('shown.bs.modal', () => f.nama.focus());
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($canEdit && count($pos) > 1): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
/* Urutan pos: seret pegangan ⋮⋮ atau tombol panah → tersimpan otomatis (AJAX).
   regenerate CSRF = true: token baru dari respons disalin ke SEMUA input token
   di halaman, kalau tidak form lain (Tambah/Ubah/Hapus) ditolak diam-diam. */
(function () {
    const daftar = document.getElementById('bd-daftar-pos');
    const URL = '<?= $url('pos/urut') ?>';
    const NAMA_TOKEN = '<?= csrf_token() ?>';
    const token = () => document.querySelector(`input[name="${NAMA_TOKEN}"]`)?.value || '<?= csrf_hash() ?>';
    const syncCsrf = (h) => { if (h) document.querySelectorAll(`input[name="${NAMA_TOKEN}"]`).forEach((i) => { i.value = h; }); };
    const baris = () => [...daftar.querySelectorAll('tr[data-id]')];

    function rapikan() {
        baris().forEach((tr, i, semua) => {
            tr.querySelector('[data-nomor]').textContent = i + 1;
            tr.querySelector('[data-geser="-1"]').disabled = i === 0;
            tr.querySelector('[data-geser="1"]').disabled = i === semua.length - 1;
        });
    }

    function kabar(teks, galat = false) {
        const t = document.createElement('div');
        t.className = `position-fixed bottom-0 end-0 m-3 alert alert-${galat ? 'danger' : 'success'} py-2 px-3 small shadow`;
        t.style.cssText = 'z-index:2000;opacity:0;transition:opacity .2s';
        t.setAttribute('role', 'status');
        t.textContent = teks;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; });
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 250); }, galat ? 4000 : 1600);
    }

    let tunda = null, urutanTersimpan = baris().map((tr) => tr.dataset.id);
    function simpan() {
        clearTimeout(tunda);
        tunda = setTimeout(async () => {
            const ids = baris().map((tr) => tr.dataset.id);
            if (ids.join() === urutanTersimpan.join()) return;
            const badan = new URLSearchParams();
            badan.append(NAMA_TOKEN, token());
            ids.forEach((id) => badan.append('ids[]', id));
            try {
                const r = await fetch(URL, { method: 'POST', body: badan, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                const d = await r.json().catch(() => ({}));
                syncCsrf(d.csrf);
                if (!r.ok || !d.ok) throw new Error(d.pesan || 'Gagal');
                urutanTersimpan = ids;
                kabar('Urutan disimpan');
            } catch (e) {
                kabar('Urutan gagal disimpan — muat ulang halaman lalu coba lagi.', true);
            }
        }, 350);
    }

    Sortable.create(daftar, {
        handle: '.bd-pegangan', draggable: 'tr[data-id]', animation: 150,
        ghostClass: 'bd-pos-hantu', chosenClass: 'bd-pos-dipilih',
        onEnd: () => { rapikan(); simpan(); },
    });

    daftar.addEventListener('click', (e) => {
        const b = e.target.closest('[data-geser]');
        if (!b) return;
        const tr = b.closest('tr');
        const tujuan = b.dataset.geser === '-1' ? tr.previousElementSibling : tr.nextElementSibling;
        if (!tujuan || !tujuan.dataset.id) return;
        b.dataset.geser === '-1' ? tujuan.before(tr) : tujuan.after(tr);
        rapikan(); simpan();
        tr.querySelector(`[data-geser="${b.dataset.geser}"]:not(:disabled)`)?.focus();
    });

    rapikan();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
