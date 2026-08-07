<?php
/**
 * Panel opsi desain + keputusan + riwayat — dipakai bersama oleh halaman
 * Creative standalone dan Creative per-event, yang alurnya identik.
 *
 * Semua aksi memakai form POST biasa (bukan fetch), sehingga tidak perlu
 * sinkronisasi token CSRF antar-form seperti pada modul ber-AJAX.
 *
 * Variabel yang diharapkan:
 *   $iid         int    id item
 *   $item        array  baris item
 *   $iFiles      array  seluruh file item (opsi + pendukung)
 *   $iReviews    array  riwayat keputusan, terlama dulu
 *   $canEdit     bool
 *   $canApprove  bool
 *   $aksiBase    string prefix URL aksi, mis. 'creative/12' | 'events/3/creative/12'
 *   $fileBase    string prefix URL file, mis. 'uploads/creative-standalone/12/'
 *   $imageExts   array
 */
$status   = $item['status'] ?? 'draft';
$versi    = (int) ($item['versi'] ?? 1) ?: 1;
$opsi     = array_values(array_filter($iFiles, fn($f) => (int) ($f['is_opsi'] ?? 1) === 1));
$pendukung= array_values(array_filter($iFiles, fn($f) => (int) ($f['is_opsi'] ?? 1) === 0));
$terpilih = array_values(array_filter($opsi, fn($f) => (int) ($f['is_terpilih'] ?? 0) === 1));

$bisaAjukan  = $canEdit    && in_array($status, ['draft', 'revision'], true);
$bisaPutus   = $canApprove && $status === 'review';
$bisaBuka    = ($canEdit || $canApprove) && $status === 'approved';
$bisaUnggah  = $canEdit    && $status !== 'approved';
// Opsi hanya boleh dihapus selagi materi masih digarap; file pendukung
// tetap bisa dirapikan sampai materi disetujui. Lihat
// CreativeApprovalService::fileTerkunci() — aturannya ditegakkan di controller.
$bisaHapusOpsi      = $canEdit && in_array($status, ['draft', 'revision'], true);
$bisaHapusPendukung = $canEdit && $status !== 'approved';
// Item yang pernah direvisi memuat opsi dari beberapa putaran sekaligus.
// Peninjau boleh saja kembali memilih konsep lama, jadi opsi lama tetap
// bisa dipilih — tapi asal putarannya harus terlihat agar tidak tertukar.
$adaBanyakPutaran = count(array_unique(array_column($opsi, 'versi'))) > 1;

$aksiLabel = [
    'ajukan'  => ['Diajukan untuk ditinjau', 'bi-send',         'secondary'],
    'setujui' => ['Disetujui',               'bi-check-circle',  'success'],
    'revisi'  => ['Diminta revisi',          'bi-arrow-repeat',  'danger'],
    'buka'    => ['Dibuka kembali',          'bi-unlock',        'warning'],
];
?>

<div class="px-3 py-2 bg-primary-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small fw-semibold text-primary">
        <i class="bi bi-images me-1"></i>Opsi Desain
        <?php if (!empty($opsi)): ?>
        <span class="badge bg-primary text-white ms-1"><?= count($opsi) ?></span>
        <?php endif; ?>
        <span class="badge bg-secondary-subtle text-secondary ms-1" title="Putaran review ke-<?= $versi ?>">v<?= $versi ?></span>
    </span>
    <?php if ($status === 'approved'): ?>
        <?php if (!empty($terpilih)): ?>
        <span class="d-flex flex-wrap gap-1">
            <?php foreach ($terpilih as $t): ?>
            <a href="<?= base_url($fileBase . $t['file_name']) ?>" download="<?= esc($t['original_name'], 'attr') ?>"
               class="btn btn-xs btn-success" style="padding:.2rem .6rem;font-size:.72rem">
                <i class="bi bi-download me-1"></i>Materi Final<?= count($terpilih) > 1 ? ' ' . esc($t['opsi_label']) : '' ?>
            </a>
            <?php endforeach; ?>
        </span>
        <?php else: ?>
        <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:.68rem">
            <i class="bi bi-question-circle me-1"></i>Opsi final belum ditentukan
        </span>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="px-3 py-2">
    <?php if (empty($opsi) && empty($pendukung)): ?>
    <div class="small text-muted fst-italic mb-2">
        <?= $canEdit ? 'Belum ada opsi desain. Unggah minimal satu file sebelum mengajukan review.' : 'Belum ada opsi desain diunggah.' ?>
    </div>
    <?php endif; ?>

    <?php // ── Grid opsi ── ?>
    <?php if (!empty($opsi)): ?>
    <form method="POST" action="<?= base_url($aksiBase . '/setujui') ?>" id="setujui-<?= $iid ?>">
        <?= csrf_field() ?>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <?php foreach ($opsi as $f):
                $ext      = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                $isImage  = in_array($ext, $imageExts);
                $fileUrl  = base_url($fileBase . $f['file_name']);
                $isWin    = (int) ($f['is_terpilih'] ?? 0) === 1;
                $kalah    = $status === 'approved' && !empty($terpilih) && ! $isWin;
            ?>
            <div class="border rounded-2 p-1 text-center position-relative <?= $isWin ? 'border-success border-2' : '' ?>"
                 style="min-width:96px;max-width:130px<?= $kalah ? ';opacity:.45' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-1" style="gap:.25rem">
                    <span class="badge <?= $isWin ? 'bg-success' : 'bg-secondary-subtle text-secondary' ?>" style="font-size:.62rem">
                        Opsi <?= esc($f['opsi_label'] ?? '?') ?>
                        <?php if ($adaBanyakPutaran): ?><span class="opacity-75">· v<?= (int) ($f['versi'] ?? 1) ?></span><?php endif; ?>
                    </span>
                    <?php if ($bisaPutus && count($opsi) > 1): ?>
                    <input type="checkbox" name="opsi[]" value="<?= (int) $f['id'] ?>" class="form-check-input mt-0"
                           title="Pilih opsi ini" aria-label="Pilih opsi <?= esc($f['opsi_label'] ?? '', 'attr') ?>">
                    <?php endif; ?>
                </div>
                <?php if ($isImage): ?>
                <a href="<?= $fileUrl ?>" target="_blank">
                    <img src="<?= $fileUrl ?>" alt="Opsi <?= esc($f['opsi_label'] ?? '', 'attr') ?>"
                         class="rounded-1 d-block mb-1" style="width:100%;height:74px;object-fit:cover">
                </a>
                <?php else: ?>
                <a href="<?= $fileUrl ?>" target="_blank" class="d-block mb-1 text-muted" style="font-size:2rem">
                    <i class="bi bi-file-earmark-<?= $ext === 'pdf' ? 'pdf text-danger' : 'fill' ?>"></i>
                </a>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.6rem;line-height:1.2;word-break:break-all"><?= esc($f['original_name']) ?></div>
                <?php if ($isWin): ?>
                <div class="badge bg-success w-100 mt-1" style="font-size:.58rem"><i class="bi bi-check-lg me-1"></i>TERPILIH</div>
                <?php endif; ?>
                <?php if ($bisaHapusOpsi): ?>
                <button type="submit" form="hapus-file-<?= (int) $f['id'] ?>"
                        class="btn btn-xs btn-outline-danger w-100 mt-1" style="padding:.1rem .3rem;font-size:.6rem"
                        title="Hapus opsi ini">
                    <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($bisaPutus): ?>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <button type="submit" class="btn btn-sm btn-success" style="font-size:.78rem">
                <i class="bi bi-check-lg me-1"></i>Setujui<?= count($opsi) > 1 ? ' Opsi Terpilih' : '' ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger toggle-revisi" data-iid="<?= $iid ?>" style="font-size:.78rem">
                <i class="bi bi-arrow-repeat me-1"></i>Minta Revisi
            </button>
            <?php if (count($opsi) > 1): ?>
            <span class="small text-muted">Centang dulu opsi mana yang disetujui.</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </form>

    <?php // Form hapus file dipisah dari form setujui — form tidak boleh bersarang. ?>
    <?php if ($bisaHapusOpsi): ?>
        <?php foreach ($opsi as $f): ?>
        <form method="POST" id="hapus-file-<?= (int) $f['id'] ?>" class="d-none"
              action="<?= base_url($aksiBase . '/file/' . (int) $f['id'] . '/delete') ?>"
              onsubmit="return confirm('Hapus opsi <?= esc($f['opsi_label'] ?? '', 'attr') ?>?')">
            <?= csrf_field() ?>
        </form>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; // opsi ?>

    <?php // Materi lama bisa berstatus 'review' tanpa satu pun opsi. Tanpa jalan
          // keluar di sini, item itu menggantung selamanya di Kotak Persetujuan. ?>
    <?php if ($bisaPutus && empty($opsi)): ?>
    <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:.78rem">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Materi ini menunggu peninjauan tetapi belum punya opsi desain untuk disetujui.
        <button type="button" class="btn btn-sm btn-outline-danger ms-2 toggle-revisi" data-iid="<?= $iid ?>" style="font-size:.75rem">
            <i class="bi bi-arrow-repeat me-1"></i>Kembalikan untuk Revisi
        </button>
    </div>
    <?php endif; ?>

    <?php // ── Panel minta revisi ── ?>
    <?php if ($bisaPutus): ?>
    <div id="revisi-<?= $iid ?>" class="d-none border rounded-2 p-2 mb-2 bg-body-tertiary">
        <form method="POST" action="<?= base_url($aksiBase . '/revisi') ?>">
            <?= csrf_field() ?>
            <label class="form-label small fw-semibold mb-1">Alasan revisi <span class="text-danger">*</span></label>
            <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" required minlength="10"
                      placeholder="Contoh: logo di kanan bawah terlalu kecil, warna latar terlalu gelap."></textarea>
            <?php if (count($opsi) > 1): ?>
            <label class="form-label small mb-1">Lanjutkan dari opsi <span class="text-muted">(opsional)</span></label>
            <select name="basis_file_id" class="form-select form-select-sm mb-2" style="max-width:220px">
                <option value="">— bebas, mulai dari awal —</option>
                <?php foreach ($opsi as $f): ?>
                <option value="<?= (int) $f['id'] ?>">Opsi <?= esc($f['opsi_label'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-danger" style="font-size:.78rem">
                    <i class="bi bi-send me-1"></i>Kirim Permintaan Revisi
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary toggle-revisi" data-iid="<?= $iid ?>" style="font-size:.78rem">Batal</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php // ── File pendukung ── ?>
    <?php if (!empty($pendukung)): ?>
    <div class="mb-2">
        <div class="small text-muted mb-1"><i class="bi bi-paperclip me-1"></i>File pendukung</div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($pendukung as $f):
                $ext     = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                $isImage = in_array($ext, $imageExts);
                $fileUrl = base_url($fileBase . $f['file_name']);
            ?>
            <div class="border rounded-2 p-1 text-center" style="min-width:80px;max-width:110px">
                <?php if ($isImage): ?>
                <a href="<?= $fileUrl ?>" target="_blank">
                    <img src="<?= $fileUrl ?>" alt="<?= esc($f['original_name'], 'attr') ?>" class="rounded-1 d-block mb-1"
                         style="width:100%;height:56px;object-fit:cover">
                </a>
                <?php else: ?>
                <a href="<?= $fileUrl ?>" target="_blank" class="d-block mb-1 text-muted" style="font-size:1.6rem">
                    <i class="bi bi-file-earmark-<?= $ext === 'pdf' ? 'pdf text-danger' : 'fill' ?>"></i>
                </a>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.58rem;line-height:1.2;word-break:break-all"><?= esc($f['original_name']) ?></div>
                <?php if ($bisaHapusPendukung): ?>
                <form method="POST" action="<?= base_url($aksiBase . '/file/' . (int) $f['id'] . '/delete') ?>"
                      onsubmit="return confirm('Hapus file pendukung ini?')" class="mt-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger w-100" style="padding:.1rem .3rem;font-size:.6rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php // ── Unggah & ajukan ── ?>
    <?php if ($bisaUnggah): ?>
    <form method="POST" action="<?= base_url($aksiBase . '/upload') ?>" enctype="multipart/form-data" class="mb-2">
        <?= csrf_field() ?>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="file" name="<?= esc($fileField ?? 'file', 'attr') ?>" class="form-control form-control-sm" style="max-width:260px"
                   accept="image/*,.pdf,.psd,.ai,.zip,.rar" required>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload</button>
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="is_pendukung" value="1" id="pendukung-<?= $iid ?>">
                <label class="form-check-label small text-muted" for="pendukung-<?= $iid ?>">
                    File pendukung (bukan opsi desain)
                </label>
            </div>
        </div>
        <div class="form-text" style="font-size:.68rem">Satu file = satu opsi. Kirim tiap alternatif sebagai file terpisah.</div>
    </form>
    <?php endif; ?>

    <?php if ($bisaAjukan): ?>
    <form method="POST" action="<?= base_url($aksiBase . '/ajukan') ?>" class="mb-2">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-warning" style="font-size:.78rem" <?= empty($opsi) ? 'disabled' : '' ?>>
            <i class="bi bi-send me-1"></i><?= $status === 'revision' ? 'Ajukan Ulang' : 'Ajukan Review' ?>
            <?= !empty($opsi) ? '(' . count($opsi) . ' opsi)' : '' ?>
        </button>
        <?php if (empty($opsi)): ?>
        <span class="small text-muted ms-1">Unggah minimal satu opsi dulu.</span>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php // ── Buka kembali ── ?>
    <?php if ($bisaBuka): ?>
    <button type="button" class="btn btn-sm btn-outline-warning mb-2 toggle-buka" data-iid="<?= $iid ?>" style="font-size:.78rem">
        <i class="bi bi-unlock me-1"></i>Buka &amp; Ajukan Ulang
    </button>
    <div id="buka-<?= $iid ?>" class="d-none border rounded-2 p-2 mb-2 bg-body-tertiary">
        <form method="POST" action="<?= base_url($aksiBase . '/buka') ?>"
              onsubmit="return confirm('Persetujuan sebelumnya akan dibatalkan dan materi kembali menjadi draft. Lanjutkan?')">
            <?= csrf_field() ?>
            <label class="form-label small fw-semibold mb-1">Alasan membuka kembali <span class="text-danger">*</span></label>
            <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" required minlength="10"
                      placeholder="Contoh: tanggal event berubah, materi harus disesuaikan."></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-warning" style="font-size:.78rem">
                    <i class="bi bi-unlock me-1"></i>Buka Kembali
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary toggle-buka" data-iid="<?= $iid ?>" style="font-size:.78rem">Batal</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php // ── Riwayat keputusan ── ?>
    <?php if (!empty($iReviews)): ?>
    <div class="border-top pt-2 mt-1">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-clock-history me-1"></i>Riwayat Keputusan</div>
        <ul class="list-unstyled mb-0">
            <?php foreach (array_reverse($iReviews) as $r):
                [$labelAksi, $ikon, $warna] = $aksiLabel[$r['aksi']] ?? ['Perubahan', 'bi-dot', 'secondary'];
            ?>
            <li class="d-flex gap-2 mb-1" style="font-size:.72rem">
                <i class="bi <?= $ikon ?> text-<?= $warna ?> mt-1"></i>
                <div class="min-w-0">
                    <span class="fw-semibold text-<?= $warna ?>"><?= $labelAksi ?></span>
                    <?php if ($r['aksi'] === 'setujui' && !empty($r['opsi_label'])): ?>
                    <span class="badge bg-success-subtle text-success" style="font-size:.6rem">opsi <?= esc($r['opsi_label']) ?></span>
                    <?php elseif ($r['aksi'] === 'revisi' && !empty($r['opsi_label'])): ?>
                    <span class="badge bg-info-subtle text-info" style="font-size:.6rem">lanjut dari opsi <?= esc($r['opsi_label']) ?></span>
                    <?php endif; ?>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:.6rem">v<?= (int) $r['versi'] ?></span>
                    <span class="text-muted">
                        · <?= esc($r['oleh_nama'] ?? 'Sistem') ?>
                        · <?= $r['created_at'] ? date('d M Y H:i', strtotime($r['created_at'])) : '' ?>
                    </span>
                    <?php if (!empty($r['catatan'])): ?>
                    <div class="text-muted fst-italic" style="white-space:pre-line">"<?= esc($r['catatan']) ?>"</div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
