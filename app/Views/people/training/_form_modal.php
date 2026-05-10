<?php
$p = $program ?? null;
$ids = $compIds ?? [];
?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= $formAction ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $modalTitle ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Program <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" value="<?= esc($p['nama'] ?? '') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Tipe</label>
                            <select name="tipe" class="form-select">
                                <option value="internal" <?= ($p['tipe'] ?? '') === 'internal' ? 'selected' : '' ?>>Internal</option>
                                <option value="eksternal" <?= ($p['tipe'] ?? '') === 'eksternal' ? 'selected' : '' ?>>Eksternal</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['draft'=>'Draft','scheduled'=>'Dijadwalkan','ongoing'=>'Berjalan','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($p['status'] ?? 'draft') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Vendor / Trainer</label>
                            <input type="text" name="vendor" class="form-control" value="<?= esc($p['vendor'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" value="<?= esc($p['lokasi'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="<?= $p['tanggal_mulai'] ?? '' ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="<?= $p['tanggal_selesai'] ?? '' ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Biaya per Peserta (Rp)</label>
                            <input type="number" name="biaya_per_peserta" class="form-control" step="1000"
                                   value="<?= isset($p['biaya_per_peserta']) && $p['biaya_per_peserta'] !== null ? (int)$p['biaya_per_peserta'] : '' ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Kuota Peserta</label>
                            <input type="number" name="kuota" class="form-control" min="1" value="<?= $p['kuota'] ?? '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Kompetensi yang Dikembangkan</label>
                            <div class="border rounded p-2" style="max-height:160px;overflow-y:auto">
                                <?php foreach (['hard' => 'Hard Skill', 'soft' => 'Soft Skill'] as $cat => $catLabel): ?>
                                <div class="text-muted fw-semibold mb-1" style="font-size:.72rem;text-transform:uppercase"><?= $catLabel ?></div>
                                <?php foreach (($competencies[$cat] ?? []) as $c): ?>
                                <div class="form-check form-check-inline" style="min-width:180px">
                                    <input class="form-check-input" type="checkbox" name="competency_ids[]"
                                           id="comp_<?= $modalId ?>_<?= $c['id'] ?>" value="<?= $c['id'] ?>"
                                           <?= in_array($c['id'], $ids) ? 'checked' : '' ?>>
                                    <label class="form-check-label" style="font-size:.8rem" for="comp_<?= $modalId ?>_<?= $c['id'] ?>">
                                        <?= esc($c['nama']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <div class="mb-2"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2"><?= esc($p['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"><?= esc($p['catatan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
