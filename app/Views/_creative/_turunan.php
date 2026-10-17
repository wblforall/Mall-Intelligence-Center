<?php
/**
 * Bagian "Turunan" pada kartu induk Design / Motion Graphic.
 * Dipakai di /creative (kartu item standalone, baca-saja untuk item event) dan di tab Creative event.
 *
 * Variabel:
 *   $iid          int     id item induk
 *   $item         array   baris item induk
 *   $tur          array   ['rows'=>[], 'total'=>n, 'selesai'=>n] dari CreativeTurunanService::untukItems
 *   $pfx          string  'cr' (halaman /creative) | 'ev' (tab Creative event) -> kelas tombol / chip
 *   $scope        string  'standalone' | 'event'
 *   $canEdit      bool    boleh mengubah (sudah memperhitungkan Tema ditutup)
 *   $canApprove   bool    lead / admin (sudah memperhitungkan Tema ditutup)
 *   $readOnly     bool    true = hanya tampil (item event di /creative)
 *   $isiDiEvent   string  URL "Isi di event" (hanya bila $readOnly)
 *   $temaTutup    bool
 */
use App\Services\CreativeTurunanService as TS;

$rows    = $tur['rows'] ?? [];
$total   = (int) ($tur['total'] ?? 0);
$selesai = (int) ($tur['selesai'] ?? 0);
$pct     = $total > 0 ? (int) round($selesai / $total * 100) : 0;
$induk   = \App\Services\CreativeTurunanService::indukDariTipe($item['tipe']);
$aksi    = base_url('creative/turunan/' . $scope . '/');
$imgExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$fTg     = function (?string $d): string {
    if (! $d || $d === '0000-00-00') return '';
    $b = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return date('d', $t) . ' ' . $b[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
};
$hariIni = date('Y-m-d');

// kelompokkan per kelompok
$grup = ['digital' => [], 'cetak' => [], 'video' => []];
foreach ($rows as $r) $grup[$r['kelompok']][] = $r;

$ada = [];   // untuk menandai pilihan yang sudah ada di daftar centang
foreach ($rows as $r) $ada[] = [(int) ($r['jenis_id'] ?? 0), $r['ukuran'], (int) ($r['durasi_detik'] ?? 0)];
?>
<div class="ct" data-ct data-ct-iid="<?= $iid ?>">
    <div class="ct-head">
        <div class="ct-title"><i class="bi bi-diagram-2"></i>Turunan
            <?php if ($total > 0): ?><span class="<?= $pfx ?>-chip <?= $selesai === $total ? $pfx . '-chip-ok' : $pfx . '-chip-mute' ?>"><?= $selesai ?> dari <?= $total ?> selesai</span><?php endif; ?>
        </div>
        <?php if ($total > 0): ?>
        <div class="ct-prog" aria-hidden="true"><div class="<?= $pfx ?>-bar <?= $selesai === $total ? $pfx . '-bar-ok' : '' ?>"><i style="width:<?= $pct ?>%"></i></div></div>
        <?php endif; ?>
        <div class="ct-head-act">
            <?php if ($readOnly): ?>
            <a href="<?= esc($isiDiEvent, 'attr') ?>" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm <?= $pfx ?>-btn-warn"><i class="bi bi-box-arrow-up-right"></i><span>Isi di event</span></a>
            <?php elseif ($canEdit && $induk): ?>
            <button type="button" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm <?= $pfx ?>-btn-primary" data-ct-pick
                    data-induk="<?= $induk ?>" data-action="<?= esc($aksi . $iid . '/buat', 'attr') ?>"
                    data-nama="<?= esc($item['nama'], 'attr') ?>" data-ada="<?= esc(json_encode($ada), 'attr') ?>">
                <i class="bi bi-ui-checks-grid"></i><span>Pilih turunan</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($temaTutup && $total > 0): ?>
    <p class="ct-note"><i class="bi bi-lock-fill"></i>Tema sudah ditutup; turunan terkunci.</p>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <div class="ct-empty">
        Belum ada turunan.
        <?= $readOnly ? 'Turunan dikelola di tab Creative event.' : ($canEdit ? 'Klik “Pilih turunan” untuk mencentang format dan ukuran yang perlu dibuat.' : '') ?>
    </div>
    <?php endif; ?>

    <?php foreach ($grup as $kel => $list): if (! $list) continue; ?>
    <div class="ct-group">
        <div class="ct-group-h"><i class="bi <?= ['digital' => 'bi-phone', 'cetak' => 'bi-printer', 'video' => 'bi-camera-reels'][$kel] ?>"></i><?= TS::KELOMPOK[$kel] ?> <span><?= count($list) ?></span></div>
        <div class="ct-table">
        <div class="ct-th" aria-hidden="true"><span>Turunan</span><span>Status</span><span>PIC</span><span>Tenggat</span><span>Berkas</span><span></span></div>
        <?php foreach ($list as $t):
            $tid    = (int) $t['id'];
            $ap     = $t['approval_status'];
            $kunci  = $ap === 'disetujui';
            $bisaUbah = $canEdit && ! $kunci && ! $readOnly;
            $telat  = $t['tenggat'] && $t['tenggat'] < $hariIni && ! $kunci;
            $apChip = ['belum' => [$pfx . '-chip-mute', 'bi-dash-circle', 'Belum diajukan'],
                       'diajukan' => [$pfx . '-chip-warn', 'bi-hourglass-split', 'Menunggu lead'],
                       'disetujui' => [$pfx . '-chip-ok', 'bi-check-circle-fill', 'Disetujui'],
                       'ditolak' => [$pfx . '-chip-bad', 'bi-arrow-repeat', 'Ditolak']][$ap];
            $ukLabel = $t['ukuran'] !== '' ? $t['ukuran'] : 'Ukuran menyusul';
            $stChip = ['belum' => $pfx . '-chip-mute', 'dikerjakan' => $pfx . '-chip-blue', 'review' => $pfx . '-chip-warn', 'selesai' => $pfx . '-chip-ok'][$t['status']];
        ?>
        <div class="ct-row <?= $kunci ? 'is-done' : '' ?>" id="turunan-<?= $tid ?>" data-ct-row data-approval="<?= $ap ?>">
            <div class="ct-c ct-c-name"><span class="ct-nm"><?= esc($t['nama']) ?></span>
                <span class="<?= $pfx ?>-chip <?= $pfx ?>-chip-blue"><?= esc($ukLabel) ?></span>
                <?php if ($t['durasi_detik']): ?><span class="<?= $pfx ?>-chip <?= $pfx ?>-chip-mute"><i class="bi bi-stopwatch"></i><?= (int) $t['durasi_detik'] ?> dtk</span><?php endif; ?>
                <?php if ($t['sumber'] === 'otomatis'): $srcLbl = strpos((string) $t['sumber_ref'], 'digital') === 0 ? 'Konten Digital' : 'Media Cetak'; ?>
                <span class="<?= $pfx ?>-chip <?= $pfx ?>-chip-blue" title="Dibuat otomatis dari item <?= $srcLbl ?> di Tema ini; ikut berubah bila item-nya berubah"><i class="bi bi-link-45deg"></i>dari <?= $srcLbl ?></span>
                <?php else: ?><span class="<?= $pfx ?>-chip <?= $pfx ?>-chip-mute" title="Ditambahkan manual lewat daftar centang">manual</span><?php endif; ?>
                <?php if ($t['sumber'] === 'otomatis' && ! empty($t['keterangan']) && empty($t['penanda'])): ?><span class="ct-ket" title="<?= esc($t['keterangan'], 'attr') ?>"><i class="bi bi-geo-alt"></i><?= esc($t['keterangan']) ?></span><?php endif; ?>
            </div>
            <div class="ct-c ct-c-st" data-l="Status">
                <span class="<?= $pfx ?>-chip <?= $stChip ?>"><?= TS::STATUS[$t['status']] ?></span>
                <span class="<?= $pfx ?>-chip <?= $apChip[0] ?>" title="<?= $apChip[2] ?>"><i class="bi <?= $apChip[1] ?>"></i><?= $ap === 'belum' ? '' : $apChip[2] ?><?= (int) $t['versi'] > 1 ? ' v' . (int) $t['versi'] : '' ?></span>
            </div>
            <div class="ct-c ct-c-pic" data-l="PIC"><?= $t['pic'] !== null && $t['pic'] !== '' ? esc($t['pic']) : '<span class="ct-nil">—</span>' ?></div>
            <div class="ct-c ct-c-tg <?= $telat ? 'ct-late' : '' ?>" data-l="Tenggat"><?= $t['tenggat'] ? esc($fTg($t['tenggat'])) . ($telat ? ' <i class="bi bi-exclamation-triangle-fill" title="Lewat tenggat"></i>' : '') : '<span class="ct-nil">—</span>' ?></div>
            <div class="ct-c ct-c-files" data-l="Berkas">
                <?php foreach ($t['files'] as $f):
                    $ext = strtolower(pathinfo($f['nama_berkas'], PATHINFO_EXTENSION));
                    $url = base_url('uploads/creative-turunan/' . $tid . '/' . $f['nama_berkas']);
                ?>
                <a class="ct-fi" href="<?= $url ?>" target="_blank" rel="noopener" title="<?= esc((string) $f['asli'], 'attr') ?>" aria-label="Buka <?= esc((string) $f['asli'], 'attr') ?>">
                    <?php if (in_array($ext, $imgExt, true)): ?><img src="<?= $url ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-file-earmark-<?= $ext === 'pdf' ? 'pdf' : 'fill' ?>"></i><?php endif; ?></a>
                <?php endforeach; ?>
                <?php if (! $t['files']): ?><span class="ct-nil">—</span><?php endif; ?>
                <?php if ($bisaUbah): ?>
                <form method="POST" action="<?= $aksi . $tid ?>/unggah" enctype="multipart/form-data" class="ct-up">
                    <?= csrf_field() ?>
                    <label class="ct-ib" title="Unggah berkas hasil (otomatis terkirim setelah dipilih)"><i class="bi bi-paperclip"></i>
                        <input type="file" name="berkas" required accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" hidden onchange="this.form.submit()" aria-label="Unggah berkas hasil">
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <div class="ct-c ct-c-act">
                <?php if (! $readOnly): ?>
                <?php if ($bisaUbah): ?><button type="button" class="ct-ib" data-ct-edit title="Ubah status, PIC, tenggat, catatan" aria-label="Ubah turunan <?= esc($t['nama'], 'attr') ?>"><i class="bi bi-pencil"></i></button><?php endif; ?>
                <?php if ($canEdit && in_array($ap, ['belum', 'ditolak'], true)): ?>
                <form method="POST" action="<?= $aksi . $tid ?>/ajukan"><?= csrf_field() ?>
                    <button type="submit" class="ct-ib ct-ib-soft" title="Ajukan ke lead untuk disetujui" aria-label="Ajukan turunan"><i class="bi bi-send"></i></button></form>
                <?php endif; ?>
                <?php if ($canApprove && $ap === 'diajukan'): ?>
                <form method="POST" action="<?= $aksi . $tid ?>/setujui"><?= csrf_field() ?>
                    <button type="submit" class="ct-ib ct-ib-ok" title="Setujui" aria-label="Setujui turunan"><i class="bi bi-check-lg"></i></button></form>
                <details class="ct-pop">
                    <summary class="ct-ib ct-ib-bad" title="Tolak dengan alasan" aria-label="Tolak turunan"><i class="bi bi-x-lg"></i></summary>
                    <form method="POST" action="<?= $aksi . $tid ?>/tolak" class="ct-popbox"><?= csrf_field() ?>
                        <label class="ct-f ct-f-wide"><span>Alasan penolakan (min. <?= TS::MIN_ALASAN ?> karakter)</span>
                            <textarea name="alasan" class="form-control form-control-sm" rows="2" required minlength="<?= TS::MIN_ALASAN ?>"></textarea></label>
                        <button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm"><i class="bi bi-send-x"></i><span>Kirim penolakan</span></button>
                    </form>
                </details>
                <?php endif; ?>
                <?php if ($canApprove && $kunci): ?>
                <details class="ct-pop">
                    <summary class="ct-ib" title="Buka kembali (lead)" aria-label="Buka kembali turunan"><i class="bi bi-unlock"></i></summary>
                    <form method="POST" action="<?= $aksi . $tid ?>/buka" class="ct-popbox"><?= csrf_field() ?>
                        <label class="ct-f ct-f-wide"><span>Catatan (opsional)</span><input type="text" name="catatan" class="form-control form-control-sm" maxlength="200"></label>
                        <button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm"><i class="bi bi-unlock"></i><span>Buka</span></button>
                    </form>
                </details>
                <?php endif; ?>
                <?php if ($bisaUbah && ($t['sumber'] !== 'otomatis' || ! empty($t['penanda']))): ?>
                <form method="POST" action="<?= $aksi . $tid ?>/hapus" onsubmit="return confirm('Hapus turunan ini beserta berkasnya?')"><?= csrf_field() ?>
                    <button type="submit" class="ct-ib ct-ib-bad" title="Hapus turunan" aria-label="Hapus turunan <?= esc($t['nama'], 'attr') ?>"><i class="bi bi-trash"></i></button></form>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($ap === 'ditolak' && $t['alasan']): ?>
            <div class="ct-sub is-bad"><i class="bi bi-chat-left-text"></i><span><b>Ditolak<?= $t['keputusan_nama'] ? ' oleh ' . esc($t['keputusan_nama']) : '' ?>:</b> <?= esc($t['alasan']) ?></span></div>
            <?php elseif ($kunci): ?>
            <div class="ct-sub"><i class="bi bi-lock-fill"></i><span>Disetujui<?= $t['keputusan_nama'] ? ' oleh ' . esc($t['keputusan_nama']) : '' ?><?= $t['keputusan_pada'] ? ' pada ' . esc($fTg(substr($t['keputusan_pada'], 0, 10))) : '' ?>. Terkunci.</span></div>
            <?php elseif ($ap === 'belum' && $t['alasan']): ?>
            <div class="ct-sub"><i class="bi bi-unlock"></i><span><?= esc($t['alasan']) ?></span></div>
            <?php elseif ($ap === 'diajukan'): ?>
            <div class="ct-sub"><i class="bi bi-send"></i><span>Diajukan<?= $t['diajukan_nama'] ? ' oleh ' . esc($t['diajukan_nama']) : '' ?><?= $t['diajukan_pada'] ? ' pada ' . esc($fTg(substr($t['diajukan_pada'], 0, 10))) : '' ?>.</span></div>
            <?php endif; ?>
            <?php if (! empty($t['penanda'])): ?><div class="ct-sub ct-penanda"><i class="bi bi-exclamation-triangle"></i><span><?= esc($t['penanda']) ?>. Sudah dikerjakan sehingga tidak dihapus otomatis<?= $canEdit && ! $readOnly ? '; hapus sendiri bila tidak diperlukan' : '' ?>.</span></div>
<?php endif; ?>
            <?php if ($t['catatan']): ?><div class="ct-sub"><i class="bi bi-sticky"></i><span><?= esc($t['catatan']) ?></span></div><?php endif; ?>

            <?php if ($bisaUbah): ?>
            <div class="ct-editbox" hidden>
                <form method="POST" action="<?= $aksi . $tid ?>/ubah" class="ct-edit">
                    <?= csrf_field() ?>
                    <label class="ct-f"><span>Status</span>
                        <?php if ($ap === 'diajukan'): ?>
                        <select class="form-select form-select-sm" disabled><option>Review</option></select>
                        <?php else: ?>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['belum', 'dikerjakan', 'review'] as $sk): ?><option value="<?= $sk ?>" <?= $t['status'] === $sk ? 'selected' : '' ?>><?= TS::STATUS[$sk] ?></option><?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </label>
                    <label class="ct-f"><span>PIC</span><input type="text" name="pic" value="<?= esc((string) $t['pic'], 'attr') ?>" class="form-control form-control-sm" maxlength="150" placeholder="Nama PIC"></label>
                    <label class="ct-f"><span>Tenggat</span><input type="date" name="tenggat" value="<?= esc((string) $t['tenggat'], 'attr') ?>" class="form-control form-control-sm"></label>
                    <label class="ct-f ct-f-wide"><span>Catatan</span><input type="text" name="catatan" value="<?= esc((string) $t['catatan'], 'attr') ?>" class="form-control form-control-sm" placeholder="Opsional"></label>
                    <button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm" title="Simpan perubahan turunan"><i class="bi bi-check2"></i><span>Simpan</span></button>
                </form>
                <?php if ($t['files']): ?>
                <div class="ct-delfiles"><span>Hapus berkas:</span>
                    <?php foreach ($t['files'] as $f): ?>
                    <form method="POST" action="<?= $aksi . $tid ?>/hapus-berkas" onsubmit="return confirm('Hapus berkas ini?')"><?= csrf_field() ?><input type="hidden" name="file_id" value="<?= (int) $f['id'] ?>">
                        <button type="submit" class="ct-delfile" title="Hapus berkas"><i class="bi bi-x-lg"></i><?= esc((string) $f['asli']) ?></button></form>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
