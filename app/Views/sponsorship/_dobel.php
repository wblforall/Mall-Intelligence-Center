<?php
/**
 * Panel "Kemungkinan Sponsor Dobel" — HANYA dirender untuk pengguna berhak (diputuskan di controller;
 * view ini tidak pernah dipanggil tanpa hak). Dipakai di /sponsorship/summary.
 *
 * Variabel: $dobel (SponsorshipDobel::deteksi()), $kembali ('summary'), $bulan, $fmtRp, $fmtShort.
 * Semua tindakan = POST + CSRF ke SponsorshipCtrl::putuskanDobel / batalkanDobel (hak diperiksa lagi di server).
 */
$grup       = $dobel['grup'] ?? [];
$riwayat    = $dobel['keputusan'] ?? [];
$perlu      = array_values(array_filter($grup, fn($g) => $g['n_belum'] > 0));
$nBelum     = (int)($dobel['n_belum'] ?? 0);
$nPutus     = (int)($dobel['n_keputusan'] ?? 0);
$bulanPendek = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$tglJam = function (?string $dt) use ($bulanPendek): string {
    if (! $dt) return '';
    $t = strtotime($dt);
    return date('j', $t) . ' ' . $bulanPendek[(int)date('n', $t) - 1] . ' ' . date('Y H:i', $t);
};
$periodeEv = function (array $e) use ($bulanPendek): string {
    if (empty($e['start_date'])) return '';
    $t = strtotime($e['start_date']);
    $hari = max(1, (int)($e['event_days'] ?? 1));
    return date('j', $t) . ' ' . $bulanPendek[(int)date('n', $t) - 1] . ' ' . date('Y', $t) . ($hari > 1 ? ' · ' . $hari . ' hari' : '');
};
$periodeProg = function (array $p) use ($bulanPendek): string {
    if (empty($p['tanggal_mulai'])) return '';
    $a = strtotime($p['tanggal_mulai']);
    $s = date('j', $a) . ' ' . $bulanPendek[(int)date('n', $a) - 1] . ' ' . date('Y', $a);
    if (! empty($p['tanggal_selesai']) && $p['tanggal_selesai'] !== $p['tanggal_mulai']) {
        $b = strtotime($p['tanggal_selesai']);
        $s .= ' – ' . date('j', $b) . ' ' . $bulanPendek[(int)date('n', $b) - 1] . ' ' . date('Y', $b);
    }
    return $s;
};
$yakinLbl = ['kuat' => 'Kuat', 'sedang' => 'Sedang'];
$aksiUrl  = base_url('sponsorship/dobel/putuskan');
$batalUrl = base_url('sponsorship/dobel/batal');
?>
<section class="ss-card ss-dbl" id="dobel" aria-labelledby="ssDblT">
    <div class="ss-card-h">
        <h2 id="ssDblT"><i class="bi bi-files"></i>Kemungkinan Sponsor Dobel</h2>
        <span class="ss-count<?= $nBelum === 0 ? ' is-ok' : '' ?>" id="ssDblCount"><?= $nBelum ?></span>
        <span class="ss-hint d-none d-md-inline">sponsor yang tercatat di program mandiri <em>dan</em> di event pada periode yang sama · hanya tim Sponsorship yang melihat ini</span>
        <?php if ($nPutus > 0): ?>
        <a href="#ssDblHist" class="ss-hint ms-auto ss-dbl-jump" data-open="ssDblHist"><?= $nPutus ?> keputusan tercatat</a>
        <?php endif; ?>
    </div>

    <?php if (empty($perlu)): ?>
    <div class="ss-empty is-compact ss-dbl-empty">
        <i class="bi bi-check2-circle"></i>
        <p><strong>Tidak ada kemungkinan dobel</strong><?= $nPutus > 0 ? ' yang menunggu keputusan.' : '.' ?><br>
        <span class="ss-hint">Pendeteksi mencocokkan nama sponsor (tanpa beda huruf/tanda baca) dan nilainya antara program mandiri dan event pada periode yang beririsan.</span></p>
    </div>
    <?php else: ?>
    <div class="ss-dbl-intro">
        Berikut sponsor yang <strong>kemungkinan diinput dua kali</strong>. Angka belum diubah — tim yang memutuskan.
        <strong>Konfirmasi dobel</strong> mengecualikan satu sisi dari semua total gabungan (data tidak dihapus);
        <strong>Bukan dobel</strong> menyembunyikan pasangan itu dari pendeteksi.
        <span class="ss-hint d-block mt-1">Pasangan lain dengan nama sama dari sponsor berbeda dapat muncul setelah Bukan dobel.</span>
    </div>
    <div class="ss-dbl-list">
        <?php foreach ($perlu as $gi => $g):
            $p = $g['program']; $e = $g['event'];
            $fid = 'dblf' . $gi;
            $belum = array_values(array_filter($g['pasangan'], fn($x) => $x['keputusan'] === null));
        ?>
        <article class="ss-dbl-g" data-yakin="<?= esc($g['keyakinan']) ?>">
            <header class="ss-dbl-gh">
                <div class="ss-dbl-gt">
                    <div class="ss-dbl-pair">
                        <span class="ss-tag is-std">Program</span><b><?= esc($p['nama_program']) ?></b>
                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                        <span class="ss-tag is-evt">Event</span><b><?= esc($e['name']) ?></b>
                    </div>
                    <div class="ss-dbl-sub">
                        <?php if ($periodeProg($p)): ?><span><i class="bi bi-calendar3"></i><?= esc($periodeProg($p)) ?></span><?php endif; ?>
                        <?php if ($periodeEv($e)): ?><span><i class="bi bi-calendar-event"></i><?= esc($periodeEv($e)) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="ss-dbl-gs">
                    <span class="ss-yakin" data-y="<?= esc($g['keyakinan']) ?>" title="Keyakinan: kuat = nama &amp; nilai sama; sedang = nama mirip &amp; nilai sama, atau nama sama &amp; nilai beda ≤ 50%">
                        <?= $g['n_kuat'] ?> kuat<?= $g['n_sedang'] > 0 ? ' · ' . $g['n_sedang'] . ' sedang' : '' ?></span>
                </div>
            </header>
            <p class="ss-dbl-sum">
                <strong><?= $g['n'] ?> dari <?= $g['m_program'] ?> sponsor</strong> program ini kembar di event ·
                nilai kembar <strong class="num" title="<?= esc($fmtRp($g['nilai_min'])) ?>"><?= esc($fmtShort($g['nilai_min'])) ?></strong>
                <span class="ss-muted">(program <?= esc($fmtShort($g['nilai_prog'])) ?> · event <?= esc($fmtShort($g['nilai_event'])) ?>)</span>
            </p>

            <form method="post" action="<?= $aksiUrl ?>" class="ss-dbl-form" id="<?= $fid ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                <input type="hidden" name="bulan" value="<?= esc($bulan) ?>">
                <details class="ss-dbl-det">
                    <summary><span class="ss-dbl-sumtxt"><i class="bi bi-chevron-right"></i>Lihat <?= count($g['pasangan']) ?> pasangan sponsor</span></summary>
                    <div class="ss-scroll ss-dbl-scroll">
                    <table class="ss-mini ss-dbl-table">
                        <thead><tr>
                            <th class="ss-dbl-chk"><input type="checkbox" class="ss-dbl-all" aria-label="Pilih semua pasangan yang belum diputuskan" checked></th>
                            <th>Sponsor di program</th><th>Sponsor di event</th><th class="t-r">Selisih</th><th>Keyakinan</th><th>Keputusan</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($g['pasangan'] as $x): $k = $x['keputusan']; ?>
                        <tr<?= $k ? ' class="is-decided"' : '' ?>>
                            <td class="ss-dbl-chk"><?php if (! $k): ?><input type="checkbox" name="pasangan[]" value="<?= $x['ps_id'] ?>:<?= $x['es_id'] ?>" checked aria-label="Pilih pasangan <?= esc($x['nama_p']) ?>"><?php endif; ?></td>
                            <td><span class="ss-spn-t"><?= esc($x['nama_p']) ?></span> <span class="num ss-muted" title="<?= esc($fmtRp($x['nilai_p'])) ?>"><?= esc($fmtShort($x['nilai_p'])) ?></span></td>
                            <td><span class="ss-spn-t"><?= esc($x['nama_e']) ?></span> <span class="num ss-muted" title="<?= esc($fmtRp($x['nilai_e'])) ?>"><?= esc($fmtShort($x['nilai_e'])) ?></span></td>
                            <td class="t-r num"><?= $x['selisih'] === 0 ? '<span class="ss-dash">sama</span>' : '<span title="Program − Event">' . ($x['selisih'] > 0 ? '+' : '−') . esc($fmtShort(abs($x['selisih']))) . '</span>' ?></td>
                            <td><span class="ss-yakin" data-y="<?= esc($x['keyakinan']) ?>"><?= $yakinLbl[$x['keyakinan']] ?></span></td>
                            <td><?php if ($k): ?><span class="ss-dbl-st">dobel · <?= $k['sisi_kecuali'] === 'program' ? 'program dikecualikan' : 'event dikecualikan' ?></span><?php else: ?><span class="ss-dash">belum</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </details>

                <?php if ($belum): ?>
                <div class="ss-dbl-act">
                    <label class="ss-dbl-f"><span>Sisi yang dikecualikan</span>
                        <select name="sisi" class="ss-select" aria-label="Sisi yang dikecualikan dari total">
                            <option value="program" selected>Program mandiri (bawaan)</option>
                            <option value="event">Event</option>
                        </select></label>
                    <label class="ss-dbl-f ss-dbl-f-cat"><span>Catatan (opsional)</span>
                        <input type="text" name="catatan" maxlength="500" class="ss-dbl-in" placeholder="mis. input ganda, sudah ada di event" autocomplete="off"></label>
                    <div class="ss-dbl-btns">
                        <button type="submit" name="aksi" value="dobel" class="ss-btn ss-btn-primary ss-btn-sm" data-konfirmasi="dobel"><i class="bi bi-files"></i>Konfirmasi dobel</button>
                        <button type="submit" name="aksi" value="bukan_dobel" class="ss-btn ss-btn-sm" data-konfirmasi="bukan"><i class="bi bi-x-circle"></i>Bukan dobel</button>
                    </div>
                </div>
                <div class="ss-dbl-fn">Tindakan berlaku untuk pasangan yang dicentang (<?= count($belum) ?> belum diputuskan). Realisasi sponsor di sisi yang dikecualikan ikut dikecualikan dari total.</div>
                <?php endif; ?>
            </form>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($nPutus > 0): ?>
    <details class="ss-dbl-hist" id="ssDblHist">
        <summary><span class="ss-dbl-sumtxt"><i class="bi bi-chevron-right"></i><i class="bi bi-clock-history"></i>Keputusan tercatat <b><?= $nPutus ?></b></span></summary>
        <?php if ($nPutus > 1): ?>
        <form method="post" action="<?= $batalUrl ?>" class="ss-dbl-undo ss-dbl-undoall" data-konfirmasi="batal">
            <?= csrf_field() ?>
            <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
            <input type="hidden" name="bulan" value="<?= esc($bulan) ?>">
            <?php foreach ($riwayat as $k): ?><input type="hidden" name="id[]" value="<?= (int)$k['id'] ?>"><?php endforeach; ?>
            <button type="submit" class="ss-btn ss-btn-ghost ss-btn-sm" title="Hapus semua keputusan di bawah ini"><i class="bi bi-arrow-counterclockwise"></i>Batalkan semua (<?= $nPutus ?>)</button>
        </form>
        <?php endif; ?>
        <div class="ss-scroll ss-dbl-scroll">
        <table class="ss-mini ss-dbl-table ss-dbl-histt">
            <thead><tr><th>Sponsor program ↔ event</th><th>Keputusan</th><th>Catatan</th><th>Oleh</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($riwayat as $k):
                $oleh  = $k['pengubah'] ?: $k['pembuat'];
                $waktu = $k['diubah_pada'] ?: $k['dibuat_pada'];
            ?>
            <tr>
                <td><span class="ss-spn-t"><?= esc($k['p']['nama_sponsor']) ?></span> <span class="ss-muted num"><?= esc($fmtShort((int)$k['p']['nilai'])) ?></span>
                    <i class="bi bi-arrow-left-right ss-muted" aria-hidden="true"></i>
                    <span class="ss-spn-t"><?= esc($k['e']['nama_sponsor']) ?></span> <span class="ss-muted num"><?= esc($fmtShort((int)$k['e']['nilai'])) ?></span>
                    <div class="ss-sub"><?= esc($k['p']['nama_program'] ?? '') ?> ↔ <?= esc($k['e']['event_name'] ?? '') ?></div></td>
                <td><?php if ($k['status'] === 'dobel'): ?><span class="ss-dbl-st">dobel · <?= $k['sisi_kecuali'] === 'program' ? 'program dikecualikan' : 'event dikecualikan' ?></span>
                    <?php else: ?><span class="ss-dbl-st is-no">bukan dobel</span><?php endif; ?></td>
                <td class="ss-dbl-cat"><?= $k['catatan'] ? esc($k['catatan']) : '<span class="ss-dash">—</span>' ?></td>
                <td class="ss-dbl-by"><?= esc($oleh ?: '—') ?><div class="ss-sub"><?= esc($tglJam($waktu)) ?></div></td>
                <td class="t-r">
                    <form method="post" action="<?= $batalUrl ?>" class="ss-dbl-undo" data-konfirmasi="batal">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                        <input type="hidden" name="bulan" value="<?= esc($bulan) ?>">
                        <input type="hidden" name="id[]" value="<?= (int)$k['id'] ?>">
                        <button type="submit" class="ss-btn ss-btn-ghost ss-btn-sm" title="Hapus keputusan ini; pasangan kembali dinilai pendeteksi"><i class="bi bi-arrow-counterclockwise"></i>Batalkan</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </details>
    <?php endif; ?>
</section>
