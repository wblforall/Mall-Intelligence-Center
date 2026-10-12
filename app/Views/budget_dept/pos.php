<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php $ids = array_map(fn ($p) => (int) $p['id'], $pos); ?>

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
                <th style="width:40px">#</th><th>Nama pos</th><th class="text-center">Status</th>
                <?php if ($canEdit): ?><th class="text-end" style="width:150px"></th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php if (! $pos): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pos. Tambahkan pos sesuai baris di Excel budget departemen Anda.</td></tr>
            <?php endif; ?>
            <?php foreach ($pos as $i => $p):
                $n = $jumlah[$p['id']] ?? ['n_rencana' => 0, 'n_realisasi' => 0];
                $punyaData = $n['n_rencana'] > 0 || $n['n_realisasi'] > 0;
            ?>
            <tr class="<?= empty($p['aktif']) ? 'text-body-tertiary' : '' ?>">
                <td class="text-muted"><?= $i + 1 ?></td>
                <td>
                    <?= esc($p['nama']) ?>
                    <?php if ($p['keterangan']): ?><div class="small text-muted"><?= esc($p['keterangan']) ?></div><?php endif; ?>
                </td>
                <td class="text-center">
                    <?= ! empty($p['aktif']) ? '<span class="badge text-bg-success-subtle text-success-emphasis">aktif</span>' : '<span class="badge text-bg-secondary">nonaktif</span>' ?>
                </td>
                <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                    <?php foreach ([-1 => 'up', 1 => 'down'] as $arah => $ikon):
                        $j = $i + $arah;
                        if ($j < 0 || $j >= count($ids)) { echo '<span class="d-inline-block" style="width:22px"></span>'; continue; }
                        $urut = $ids; [$urut[$i], $urut[$j]] = [$urut[$j], $urut[$i]];
                    ?>
                    <form method="POST" action="<?= $url('pos/urut') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <?php foreach ($urut as $u): ?><input type="hidden" name="ids[]" value="<?= $u ?>"><?php endforeach; ?>
                        <button class="btn btn-sm btn-link p-0 me-1" title="Pindah ke <?= $arah < 0 ? 'atas' : 'bawah' ?>" aria-label="Pindah ke <?= $arah < 0 ? 'atas' : 'bawah' ?>"><i class="bi bi-arrow-<?= $ikon ?>"></i></button>
                    </form>
                    <?php endforeach; ?>
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
