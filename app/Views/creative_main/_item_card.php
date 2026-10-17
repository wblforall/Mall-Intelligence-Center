<?php
/* Kartu satu item Creative. Variabel dari index.php: $item, $ord, $files, $realisasi, $insights, $reviews,
   $canEdit, $canApprove, $tipeConfig, $statusConfig, $platformLabels, $imageExts, $insightFields,
   $fTgl, $rp, $dlInfo, $fileCell. */
$tipe = $item['tipe'];
$cfg  = $tipeConfig[$tipe] ?? ['label' => $tipe, 'icon' => 'bi-circle'];
$hasBudgetField = in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon']);
$temaTutup = !empty($item['_temaTutup']);   // Tema ditutup: seluruh materi terkunci
    $isS    = $item['_source'] === 's';
        $iid    = (int) $item['id'];
        $status = $item['status'] ?? 'draft';
        $sCfg   = $statusConfig[$status] ?? $statusConfig['draft'];
        $closed = !empty($item['is_closed']);
        $iBudget = (int) ($item['budget'] ?? 0);
        if ($isS) {
            $iFiles   = $files[$iid] ?? [];
            $iReal    = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
            $iInsight = $insights[$iid] ?? null;
            $iReviews = $reviews[$iid] ?? [];
        } else {
            $iFiles = []; $iReal = ['total' => 0, 'entries' => []]; $iInsight = null; $iReviews = [];
        }
        // Biaya dari FlowStore (BTB tertaut ke item ini; sudah termasuk di total). Item event: hanya bila ada tautan, selain itu tampilan lama.
        if (!$isS && !empty(($eRealisasi[$iid] ?? [])['fs'])) $iReal = $eRealisasi[$iid];
        $iFs      = $iReal['fs'] ?? [];
        $iFsTotal = (int) ($iReal['fs_total'] ?? 0);
        $iDobel   = $iReal['dobel'] ?? [];
        $adaReal  = $isS || $iFs;
        $iTotal = (int) ($iReal['total'] ?? 0);
        $iPct   = $iBudget > 0 ? min(100, round($iTotal / $iBudget * 100)) : null;
        $over   = $iTotal > $iBudget && $iBudget > 0;
        $barCls = $over ? 'cr-bar-over' : ($iPct !== null && $iPct >= 100 ? 'cr-bar-ok' : 'cr-bar-ok');
        $dl     = $dlInfo($item);
        $isMD   = \App\Services\CreativeApprovalService::isDesain($tipe);
        $rencana     = !empty($item['dari_paket']);   // item "Rencana" dari paket awal, belum dikerjakan
        $tur         = $isMD ? (($isS ? ($turunanS[$iid] ?? null) : ($turunanE[$iid] ?? null)) ?: ['rows' => [], 'total' => 0, 'selesai' => 0]) : ['rows' => [], 'total' => 0, 'selesai' => 0];
        $ttkKosong   = ['rows' => [], 'total' => 0, 'terpasang' => 0];
        $isDig       = $tipe === 'digital';
        $punyaTitik  = in_array($tipe, ['cetak', 'digital'], true);   // Media Cetak: titik cetak; Konten Digital: titik layar digital (opsional)
        $ttk         = $punyaTitik ? (($isS ? ($titikS[$iid] ?? null) : ($titikE[$iid] ?? null)) ?: $ttkKosong) : $ttkKosong;
        $tipeSpot    = $punyaTitik ? \App\Services\CreativeTitikService::tipeItem($item) : null;
        $pasangM     = (string) ($item['pasang_mulai'] ?? ''); $pasangS = (string) ($item['pasang_selesai'] ?? '');
        $canEditI    = $canEdit && !$temaTutup;
        $canApproveI = $canApprove && !$temaTutup;
        $terkunci   = $isS && (($isMD && $status === 'approved') || $temaTutup);
        $perluTinjau = $isS && $isMD && $canApproveI && $status === 'review';
        $itgl   = ($item['tanggal'] ?? '') ?: ($item['tanggal_take'] ?? '');
        $stKey  = $isMD ? $status : ($closed ? 'closed' : 'run');
        $jmlOpsi = $isMD ? count(array_filter($iFiles, fn($f) => (int) ($f['is_opsi'] ?? 1) === 1)) : 0;
        $nEntries = count($iReal['entries'] ?? []);
        $adaBuktiLama = $tipe === 'cetak' && count(array_filter($iReal['entries'] ?? [], fn($e) => !empty($e['bukti_terpasang_file_name']))) > 0;
        $nIns     = $iInsight && !empty($iInsight['entries']) ? count($iInsight['entries']) : 0;
        $actReach = (int) ($iInsight['max_reach'] ?? 0);
        $tgtReach = (int) ($item['target_reach'] ?? 0);
        $tgtImpr  = (int) ($item['target_impressions'] ?? 0);
        $actImpr  = (int) ($iInsight['max_impressions'] ?? 0);
        // Konten Digital: status konten (tampilan; NULL + ada insight tampil 'Dilaporkan' tanpa menulis DB) dan format
        $nInsK    = $isS ? $nIns : (int) (($insightN['e'] ?? [])[$iid] ?? 0);
        $kn       = $isDig ? \App\Services\CreativeKontenService::tampil($item, $nInsK) : null;
        $fmtJ     = $isDig ? trim((string) ($item['jenis_media'] ?? '')) : ''; $fmtU = $isDig ? trim((string) ($item['ukuran'] ?? '')) : '';
        $fmtLbl   = trim($fmtJ . ($fmtU !== '' ? ' ' . preg_replace('/\s*\(.*\)\s*$/u', '', $fmtU) : ''));
        $knChip   = ['rencana' => 'kn-chip-rencana', 'produksi' => 'kn-chip-produksi', 'tayang' => 'kn-chip-tayang', 'dilaporkan' => 'kn-chip-dilaporkan', 'belum' => 'kn-chip-belum'];
        $knIcon   = ['rencana' => 'bi-lightbulb', 'produksi' => 'bi-gear', 'tayang' => 'bi-broadcast', 'dilaporkan' => 'bi-clipboard-data', 'belum' => 'bi-dash-circle'];

        // Panel yang tersedia (hanya item standalone yang bisa dikelola di sini)
        $panes = [];
        if ($isS && $isMD && ($canEditI || !empty($iFiles) || $iReviews)) $panes['files'] = ['Opsi & Persetujuan', 'bi-images', $jmlOpsi ?: null];
        if ($isMD && (($isS && $canEditI) || $tur['total'] > 0)) $panes['turunan'] = ['Turunan', 'bi-diagram-2', $tur['total'] ?: null];
        if ($punyaTitik && (($isS && ($canEditI || $tipeSpot)) || $ttk['total'] > 0)) $panes['titik'] = [$isDig ? 'Layar' : 'Titik', $isDig ? 'bi-tv' : 'bi-geo-alt', $ttk['total'] ?: null];
        if (($isS && $hasBudgetField && ($canEditI || $nEntries || $iFs || ($punyaTitik && $ttk['total'] > 0))) || (!$isS && $iFs)) $panes['realisasi'] = ['Realisasi', 'bi-receipt', ($nEntries + count($iFs)) ?: null];
        if ($isS && $tipe === 'digital' && ($canEditI || $nIns)) $panes['insight'] = ['Insight', 'bi-graph-up', $nIns ?: null];
        $autoOpen = $perluTinjau && isset($panes['files']);
        $primaryPane = $autoOpen ? 'files' : (array_key_first($panes) ?: 'detail');

        $search = mb_strtolower(implode(' ', [
            $item['nama'], $item['deskripsi'] ?? '', $item['catatan'] ?? '', $item['pic'] ?? '', $item['_temaNama'] ?? '', $item['event_name'] ?? '',
            $platformLabels[$item['platform'] ?? ''] ?? '', $cfg['label'],
        ]));
    ?>
    <article class="cr-item <?= $isS ? 'is-s' : 'is-e' ?> <?= $rencana ? 'is-rencana' : '' ?> <?= ($closed || ($isMD && $status === 'approved')) ? 'is-done' : '' ?> creative-item-card"
        id="<?= $isS ? 'item-' . $iid . '-s' : 'item-e-' . $iid ?>"
        data-id="<?= $iid ?>" data-ord="<?= $ord ?>" data-src="<?= $isS ? 's' : 'e' ?>" data-tipe="<?= $tipe ?>" data-tema="<?= esc($item['_temaKey'] ?? 'none', 'attr') ?>" data-tab="<?= esc($item['_tab'], 'attr') ?>"
        data-status="<?= $stKey ?>" data-konten="<?= esc($kn['key'] ?? '', 'attr') ?>" data-pic="<?= esc(mb_strtolower((string) ($item['pic'] ?? '')), 'attr') ?>" data-closed="<?= $closed ? '1' : '0' ?>" data-dl="<?= $dl['key'] ?>" data-rencana="<?= $rencana ? '1' : '0' ?>"
        data-deadline="<?= esc($item['deadline'] ?? '', 'attr') ?>" data-tgl="<?= esc($itgl, 'attr') ?>"
        data-name="<?= esc($item['nama'], 'attr') ?>" data-budget="<?= $iBudget ?>" data-real="<?= $iTotal ?>"
        data-pane="<?= $primaryPane ?>" data-autoopen="<?= $autoOpen ? '1' : '0' ?>"
        data-search="<?= esc($search, 'attr') ?>">
        <div class="cr-item-head" data-cr-toggle>
            <button type="button" class="cr-chev" aria-expanded="false" aria-label="Buka rincian <?= esc($item['nama'], 'attr') ?>"><i class="bi bi-chevron-right"></i></button>

            <div class="cr-item-main">
                <div class="cr-item-line">
                    <i class="bi <?= $cfg['icon'] ?> cr-rowico" title="<?= esc($cfg['label'], 'attr') ?>" aria-hidden="true"></i>
                    <span class="cr-item-name"><?= esc($item['nama']) ?></span>
                    <?php if ($tipe === 'cetak' && (($item['jenis_media'] ?? '') !== '' || ($item['ukuran'] ?? '') !== '')):
                        $jm = (string) ($item['jenis_media'] ?? ''); $ukr = (string) ($item['ukuran'] ?? '');
                        $cetakLbl = trim(($jm !== '' && mb_strtolower($jm) !== mb_strtolower((string) $item['nama']) ? $jm : '') . ($ukr !== '' ? ($jm !== '' && mb_strtolower($jm) !== mb_strtolower((string) $item['nama']) ? ' · ' : '') . $ukr : '')); ?>
                    <?php if ($cetakLbl !== ''): ?><span class="cr-chip cr-chip-blue" title="Jenis media · ukuran"><i class="bi bi-rulers"></i><?= esc($cetakLbl) ?></span><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($rencana): ?><span class="cr-chip cr-chip-acc" title="Item dari paket awal, belum dikerjakan. Hilang otomatis begitu diisi atau diubah; boleh dihapus bila tidak diperlukan."><i class="bi bi-lightbulb"></i>Rencana</span><?php endif; ?>
                    <?php if ($isMD): ?>
                    <span class="cr-chip <?= $sCfg['chip'] ?>"><i class="bi <?= $sCfg['icon'] ?>"></i><?= $sCfg['label'] ?><?= ($item['versi'] ?? 1) > 1 ? ' · v' . (int) $item['versi'] : '' ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['platform'] ?? '')): ?>
                    <span class="cr-chip cr-chip-t"><i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?>"></i><?= esc($platformLabels[$item['platform']] ?? $item['platform']) ?></span>
                    <?php endif; ?>
                    <?php if ($isDig && $fmtLbl !== ''): ?><span class="cr-chip cr-chip-blue" title="Format konten · ukuran: <?= esc(trim($fmtJ . ' ' . $fmtU), 'attr') ?>"><i class="bi bi-aspect-ratio"></i><?= esc($fmtLbl) ?></span><?php endif; ?>
                    <?php if ($isDig && !$rencana): ?><span class="cr-chip <?= $knChip[$kn['key']] ?><?= $kn['turunan'] ? ' is-turunan' : '' ?>" title="<?= $kn['diatur'] ? 'Status konten: ' . $kn['label'] : ($kn['turunan'] ? 'Ada insight, jadi ditampilkan Dilaporkan. Status belum tersimpan; gunakan Atur status.' : 'Status konten belum diatur (item lama). Gunakan Atur status.') ?>"><i class="bi <?= $knIcon[$kn['key']] ?>"></i><?= $kn['label'] ?></span><?php endif; ?>
                    <?php if (!$isS): ?><span class="cr-chip cr-chip-gold" title="Dikerjakan di tab Creative event"><i class="bi bi-eye"></i>Baca-saja</span><?php endif; ?>
                    <?php if ($closed): ?><span class="cr-chip cr-chip-mute"><i class="bi bi-check2-circle"></i>Selesai</span><?php endif; ?>
                    <?php if ($dl['key'] === 'done'): ?>
                    <span class="cr-chip cr-chip-ok" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-check-circle"></i>Tepat waktu</span>
                    <?php elseif ($dl['key'] === 'late'): ?>
                    <span class="cr-chip cr-chip-bad" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-exclamation-triangle-fill"></i>Terlambat <?= abs($dl['diff']) ?> hari</span>
                    <?php elseif ($dl['key'] === 'soon'): ?>
                    <span class="cr-chip cr-chip-warn" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-clock"></i><?= $dl['diff'] === 0 ? 'Hari ini!' : $dl['diff'] . ' hari lagi' ?></span>
                    <?php elseif ($dl['key'] === 'ok'): ?>
                    <span class="cr-chip cr-chip-blue" title="Deadline"><i class="bi bi-calendar-check"></i><?= $fTgl($item['deadline'], false) ?></span>
                    <?php endif; ?>
                    <?php if ($perluTinjau): ?><span class="cr-chip cr-chip-warn"><i class="bi bi-clipboard-check"></i>Perlu ditinjau</span><?php endif; ?>
                    <?php if ($iDobel): ?><span class="cr-chip cr-chip-warn" title="Item ini punya realisasi manual di bulan yang sama dengan biaya FlowStore tertaut (<?= esc(implode(', ', $iDobel), 'attr') ?>). Periksa agar tidak terhitung dua kali; angka tidak diubah."><i class="bi bi-files"></i>Kemungkinan dobel</span><?php endif; ?>
                    <?php if ($temaTutup): ?><span class="cr-chip cr-chip-mute" title="Tema sudah ditutup; buka kembali Tema untuk mengubah"><i class="bi bi-lock-fill"></i>Tema ditutup</span>
                    <?php elseif ($terkunci && $canEdit): ?><span class="cr-chip cr-chip-mute" title="Buka kembali materi di panel Opsi &amp; Persetujuan bila perlu diubah"><i class="bi bi-lock-fill"></i>Terkunci</span><?php endif; ?>
                </div>
                <div class="cr-item-meta">
                    <?php if ($itgl && !($isDig && $itgl === ($item['tanggal_take'] ?? ''))): ?><span><i class="bi bi-calendar3"></i><?= $fTgl($itgl) ?></span><?php endif; ?>
                    
                    <?php if ($tipe === 'digital' && (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? ''))): ?>
                    <span><i class="bi bi-camera"></i>Take <b><?= $fTgl($item['tanggal_take'] ?? '') ?></b><?= ($item['jam_take'] ?? '') ? ' ' . substr($item['jam_take'], 0, 5) : '' ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['pic'] ?? '')): ?><span><i class="bi bi-person"></i><b><?= esc($item['pic']) ?></b></span><?php endif; ?>
                    <?php if ($isDig && (($pasangM !== '' && $pasangS !== '') || $ttk['total'] > 0)): ?><span title="Periode tayang di layar · layar yang sudah ditandai tayang"><i class="bi bi-tv"></i><?php if ($pasangM !== '' && $pasangS !== ''): ?><?= $fTgl($pasangM, false) ?> – <?= $fTgl($pasangS, false) ?><?php endif; ?><?php if ($ttk['total'] > 0): ?><?= $pasangM !== '' ? ' · ' : '' ?><b><?= $ttk['terpasang'] ?></b>/<b><?= $ttk['total'] ?></b> layar<?php endif; ?></span><?php endif; ?>
                    <?php if ($tipe === 'cetak' && $pasangM !== '' && $pasangS !== ''): ?><span title="Periode pasang"><i class="bi bi-calendar-range"></i><?= $fTgl($pasangM, false) ?> – <?= $fTgl($pasangS) ?></span><?php endif; ?>
                    <?php if ($tipe === 'cetak' && $ttk['total'] > 0): ?><span title="Titik yang sudah ditandai terpasang"><i class="bi bi-geo-alt"></i><b><?= $ttk['terpasang'] ?></b>/<b><?= $ttk['total'] ?></b> titik terpasang</span><?php endif; ?>
                    <?php if ($isMD && $tur['total'] > 0): ?><span title="Turunan yang sudah disetujui lead"><i class="bi bi-diagram-2"></i><b><?= $tur['selesai'] ?></b> dari <b><?= $tur['total'] ?></b> turunan selesai</span><?php endif; ?>
                </div>
                <?php if (($item['deskripsi'] ?? '') !== ''): ?><div class="cr-desc1"><?= esc($item['deskripsi']) ?></div><?php endif; ?>
            </div>

            <div class="cr-fig">
                <?php if ($isMD): ?>
                <div class="cr-fig-row cr-fig-md">
                    <div data-l="Putaran · opsi · keputusan"><b>v<?= (int) ($item['versi'] ?? 1) ?></b><span class="cr-fig-s"> · <?= $isS ? $jmlOpsi : '—' ?> opsi · </span><b class="cr-st-<?= $status ?>"><?= $sCfg['label'] ?></b></div>
                </div>
                <?php else: ?>
                <div class="cr-fig-row">
                    <div data-l="Budget"><b class="<?= $iBudget > 0 ? '' : 'cr-nil' ?>"><?= $iBudget > 0 ? $rp($iBudget) : '—' ?></b></div>
                    <div data-l="Realisasi"><b class="<?= $iTotal > 0 && $adaReal ? ($over ? 'cr-c-bad' : 'cr-c-ok') : 'cr-nil' ?>"><?= $adaReal ? ($iTotal > 0 ? $rp($iTotal) : '—') : '—' ?></b><?php if ($iFsTotal > 0): ?><i class="bi bi-box-seam cr-fsmark" title="Termasuk <?= $rp($iFsTotal) ?> dari FlowStore (<?= count($iFs) ?> BTB tertaut)" aria-label="Termasuk <?= $rp($iFsTotal) ?> dari FlowStore"></i><?php endif; ?></div>
                    <?php if ($tipe === 'digital'): ?>
                    <div data-l="Reach"><b class="<?= $actReach > 0 ? '' : 'cr-nil' ?>"><?= $actReach > 0 ? number_format($actReach, 0, ',', '.') : '—' ?></b><?php if ($tgtReach > 0): ?> <span class="cr-fig-s">/ <?= number_format($tgtReach, 0, ',', '.') ?></span><?php endif; ?></div>
                    <?php else: ?>
                    <div data-l="Serapan"><b class="<?= $iPct !== null && $adaReal ? '' : 'cr-nil' ?>"><?= $iPct !== null && $adaReal ? $iPct . '%' : '—' ?></b></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="cr-item-act" data-cr-notoggle>
                <?php if (!$isS): ?>
                <a href="<?= base_url('events/' . $item['event_id'] . '/creative#item-' . $iid) ?>" class="cr-btn cr-btn-sm cr-btn-warn cr-btn-ico" data-cr-isi-event title="Isi di event" aria-label="Isi di event"><i class="bi bi-box-arrow-up-right"></i><span>Isi di event</span></a>
                <?php elseif ($perluTinjau): ?>
                <button type="button" class="cr-btn cr-btn-sm cr-btn-primary cr-btn-ico toggle-files" data-iid="<?= $iid ?>" data-cr-open="files" title="Tinjau materi" aria-label="Tinjau materi"><i class="bi bi-clipboard-check"></i><span>Tinjau</span></button>
                <?php elseif ($panes): $pk = array_key_first($panes); ?>
                <button type="button" class="cr-btn cr-btn-sm cr-btn-soft cr-btn-ico" data-cr-open="<?= $pk ?>" title="<?= esc($panes[$pk][0] . ($panes[$pk][2] ? ' (' . $panes[$pk][2] . ')' : ''), 'attr') ?>" aria-label="<?= esc($panes[$pk][0], 'attr') ?>"><i class="bi <?= $panes[$pk][1] ?>"></i><span><?= $panes[$pk][0] ?></span><?php if ($panes[$pk][2]): ?><span class="cr-chip cr-chip-mute"><?= $panes[$pk][2] ?></span><?php endif; ?></button>
                <?php endif; ?>
                <?php if ($isS && $canEditI): ?>
                <div class="dropdown">
                    <button type="button" class="cr-iconbtn cr-iconbtn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi lain untuk <?= esc($item['nama'], 'attr') ?>"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end cr-menu">
                        <?php if (!$terkunci): ?>
                        <li><button type="button" class="dropdown-item edit-item-btn"
                            data-id="<?= $iid ?>"
                            data-nama="<?= esc($item['nama'], 'attr') ?>"
                            data-tipe="<?= $tipe ?>"
                            data-platform="<?= esc($item['platform'] ?? '', 'attr') ?>"
                            data-tanggal-take="<?= $item['tanggal_take'] ?? '' ?>"
                            data-jam-take="<?= substr($item['jam_take'] ?? '', 0, 5) ?>"
                            data-pic="<?= esc($item['pic'] ?? '', 'attr') ?>"
                            data-deskripsi="<?= esc($item['deskripsi'] ?? '', 'attr') ?>"
                            data-tanggal="<?= $item['tanggal'] ?? '' ?>"
                            data-is-closed="<?= $closed ? '1' : '0' ?>"
                            data-budget="<?= number_format($iBudget, 0, ',', '.') ?>"
                            data-target-reach="<?= (int) ($item['target_reach'] ?? 0) ?>"
                            data-target-impressions="<?= (int) ($item['target_impressions'] ?? 0) ?>"
                            data-deadline="<?= $item['deadline'] ?? '' ?>"
                            data-jenis-media="<?= esc($item['jenis_media'] ?? '', 'attr') ?>"
                            data-ukuran="<?= esc($item['ukuran'] ?? '', 'attr') ?>"
                            data-pasang-mulai="<?= esc($pasangM, 'attr') ?>"
                            data-pasang-selesai="<?= esc($pasangS, 'attr') ?>"
                            data-design-ref="<?= esc($item['design_ref'] ?? '', 'attr') ?>"
                            data-tema="<?= esc($item['_temaKey'] ?? 'none', 'attr') ?>"
                            data-catatan="<?= esc($item['catatan'] ?? '', 'attr') ?>"><i class="bi bi-pencil"></i>Ubah item</button></li>
                        <?php endif; ?>
                        <li><button type="button" class="dropdown-item pindah-item-btn" data-id="<?= $iid ?>" data-nama="<?= esc($item['nama'], 'attr') ?>" data-tema="<?= esc($item['_temaKey'] ?? 'none', 'attr') ?>"><i class="bi bi-arrow-left-right"></i>Pindahkan ke tema…</button></li>
                        <?php if (!$terkunci): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/delete') ?>" onsubmit="return confirm('<?= $rencana ? 'Hapus item rencana ini?' : 'Hapus item ini beserta semua file, turunan, dan realisasinya?' . ($ttk['total'] > 0 ? ' ' . $ttk['total'] . ' pemesanan titik di Media Promo ikut dilepas' . ($ttk['terpasang'] > 0 ? ' dan bukti pasang ' . $ttk['terpasang'] . ' titik ikut terhapus' : '') . '.' : '') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus item</button>
                            </form>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cr-item-body" hidden>
            <?php if (count($panes) > 0): ?>
            <div class="cr-tabs" role="tablist">
                <button type="button" role="tab" data-cr-tab="detail"><i class="bi bi-card-text"></i>Rincian</button>
                <?php foreach ($panes as $pk => $pv): ?>
                <button type="button" role="tab" data-cr-tab="<?= $pk ?>"><i class="bi <?= $pv[1] ?>"></i><?= $pv[0] ?><?php if ($pv[2]): ?> <span class="n"><?= $pv[2] ?></span><?php endif; ?></button>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div style="height:.75rem"></div><?php endif; ?>

            <!-- Rincian -->
            <div class="cr-pane" data-cr-pane="detail">
                <?php if ($isS && $isMD && $status === 'approved'): ?>
                <div class="cr-final">
                <?= view('_creative/_final', ['iFiles' => $iFiles, 'fileBase' => 'uploads/creative-standalone/' . $iid . '/'], ['saveData' => false]) ?>
                </div>
                <?php endif; ?>
                <?php if ($isDig): ?>
                <?= view('_creative/_status_konten', ['item' => $item, 'iid' => $iid, 'nIns' => $nInsK, 'pfx' => 'cr', 'aksi' => base_url('creative/' . $iid . '/status'), 'canEdit' => $canEditI, 'readOnly' => !$isS], ['saveData' => false]) ?>
                <?php endif; ?>
                <dl class="cr-dl">
                    <div><dt>Jenis</dt><dd><?= $cfg['label'] ?></dd></div>
                    <div><dt>Tema</dt><dd><?= esc($item['_temaNama'] ?? 'Tanpa Tema') ?></dd></div>
                    <?php if ($itgl): ?><div><dt>Tanggal</dt><dd><?= $fTgl($itgl) ?></dd></div><?php endif; ?>
                    <div><dt>Deadline</dt><dd><?= ($item['deadline'] ?? '') ? $fTgl($item['deadline']) : '—' ?></dd></div>
                    <?php if ($tipe === 'cetak' && (($item['jenis_media'] ?? '') !== '' || ($item['ukuran'] ?? '') !== '')): ?>
                    <div><dt>Jenis media</dt><dd><?= ($item['jenis_media'] ?? '') !== '' ? esc($item['jenis_media']) : '—' ?></dd></div>
                    <div><dt>Ukuran</dt><dd><?= ($item['ukuran'] ?? '') !== '' ? esc($item['ukuran']) : '—' ?></dd></div>
                    <?php endif; ?>
                    <?php if ($tipe === 'cetak' && $pasangM !== ''): ?><div><dt>Periode pasang</dt><dd><?= $fTgl($pasangM) ?> – <?= $fTgl($pasangS) ?></dd></div><?php endif; ?>
                    <?php if ($tipe === 'cetak' && $ttk['total'] > 0): ?><div><dt>Titik</dt><dd><?= $ttk['terpasang'] ?> dari <?= $ttk['total'] ?> terpasang</dd></div><?php endif; ?>
                    <?php if ($hasBudgetField || $iBudget > 0): ?><div><dt>Budget</dt><dd><?= $iBudget > 0 ? $rp($iBudget) : '—' ?></dd></div><?php endif; ?>
                    <?php if (($isS && $hasBudgetField) || $iFs): ?><div><dt>Realisasi</dt><dd class="<?= $over ? 'cr-c-bad' : '' ?>"><?= $iTotal > 0 ? $rp($iTotal) . ($iPct !== null ? ' (' . $iPct . '%)' : '') : '—' ?><?php if ($iFsTotal > 0): ?> <span class="cr-fs-ket">· <?= $rp($iFsTotal) ?> dari FlowStore</span><?php endif; ?></dd></div><?php endif; ?>
                    <?php if ($tipe === 'digital'): ?>
                    <div><dt>Platform</dt><dd><?= esc($platformLabels[$item['platform'] ?? ''] ?? '—') ?></dd></div>
                    <div><dt>Take</dt><dd><?= (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? '')) ? trim($fTgl($item['tanggal_take'] ?? '') . ' ' . substr($item['jam_take'] ?? '', 0, 5)) : '—' ?></dd></div>
                    <div><dt>PIC</dt><dd><?= ($item['pic'] ?? '') !== '' ? esc($item['pic']) : '—' ?></dd></div>
                    <div><dt>Status konten</dt><dd><?= esc($kn['label']) ?><?= $kn['turunan'] ? ' (tampilan)' : '' ?></dd></div>
                    <?php if ($fmtJ !== '' || $fmtU !== ''): ?><div><dt>Format · ukuran</dt><dd><?= esc(trim($fmtJ . ($fmtU !== '' ? ' · ' . $fmtU : ''))) ?></dd></div><?php endif; ?>
                    <?php if ($pasangM !== ''): ?><div><dt>Tayang di layar</dt><dd><?= $fTgl($pasangM) ?> – <?= $fTgl($pasangS) ?></dd></div><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($tgtReach > 0 || $tgtImpr > 0): ?>
                    <div><dt>Target reach / impresi</dt><dd><?= number_format($tgtReach, 0, ',', '.') ?> / <?= number_format($tgtImpr, 0, ',', '.') ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if (($item['deskripsi'] ?? '') !== ''): ?><p class="cr-text"><?= esc($item['deskripsi']) ?></p><?php endif; ?>
                <?php if (($item['catatan'] ?? '') !== ''): ?><p class="cr-note"><i class="bi bi-sticky"></i><?= esc($item['catatan']) ?></p><?php endif; ?>
                <?php if (!$isS): ?>
                <p class="cr-note"><i class="bi bi-info-circle"></i>Item ini dikerjakan di tab Creative event dan hanya tampil baca-saja di sini. <a href="<?= base_url('events/' . $item['event_id'] . '/creative#item-' . $iid) ?>" style="color:var(--cr-link);font-weight:600">Isi di event</a></p>
                <?php endif; ?>
            </div>

            <?php if (isset($panes['files'])): ?>
            <!-- Opsi desain, keputusan & riwayat -->
            <div class="cr-pane" data-cr-pane="files" id="files-<?= $iid ?>" hidden>
                <div class="cr-approval">
                <?= view('_creative/_approval', [
                    'iid'        => $iid,
                    'item'       => $item,
                    'iFiles'     => $iFiles,
                    'iReviews'   => $iReviews,
                    'canEdit'    => $canEditI,
                    'canApprove' => $canApproveI,
                    'aksiBase'   => 'creative/' . $iid,
                    'fileBase'   => 'uploads/creative-standalone/' . $iid . '/',
                    'fileField'  => 'file',
                    'imageExts'  => $imageExts,
                ], ['saveData' => false]) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['turunan'])): ?>
            <!-- Turunan -->
            <div class="cr-pane" data-cr-pane="turunan" id="turunan-<?= $isS ? 's' : 'e' ?>-<?= $iid ?>" hidden>
                <?= view('_creative/_turunan', [
                    'iid' => $iid, 'item' => $item, 'tur' => $tur, 'pfx' => 'cr',
                    'scope' => $isS ? 'standalone' : 'event',
                    'canEdit' => $canEditI && $isS, 'canApprove' => $canApproveI && $isS,
                    'readOnly' => !$isS, 'temaTutup' => $temaTutup,
                    'isiDiEvent' => $isS ? '' : base_url('events/' . $item['event_id'] . '/creative#item-' . $iid),
                ], ['saveData' => false]) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['titik'])): ?>
            <!-- Titik Media Promo -->
            <div class="cr-pane" data-cr-pane="titik" id="titik-<?= $isS ? 's' : 'e' ?>-<?= $iid ?>" hidden>
                <?= view('_creative/_cetak_titik', [
                    'iid' => $iid, 'item' => $item, 'titik' => $ttk, 'pfx' => 'cr', 'scope' => $isS ? 'standalone' : 'event',
                    'canEdit' => $canEditI && $isS, 'readOnly' => !$isS, 'temaTutup' => $temaTutup,
                    'isiDiEvent' => $isS ? '' : base_url('events/' . $item['event_id'] . '/creative#item-' . $iid),
                ], ['saveData' => false]) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['realisasi'])): ?>
            <!-- Realisasi biaya -->
            <div class="cr-pane" data-cr-pane="realisasi" id="realisasi-<?= $iid ?>" hidden>
                <div class="cr-pane-h">
                    <div class="cr-pane-t"><i class="bi bi-receipt"></i><?= $punyaTitik && $ttk['total'] > 0 ? 'Biaya (per item)' : 'Realisasi Biaya' ?>
                        <?php if ($iTotal > 0): ?><span class="cr-chip cr-chip-ok"><?= $rp($iTotal) ?></span><?php endif; ?>
                        <?php if ($iFsTotal > 0): ?><span class="cr-chip cr-chip-blue" title="Bagian yang berasal dari BTB FlowStore tertaut"><i class="bi bi-box-seam"></i>FlowStore <?= $rp($iFsTotal) ?></span><?php endif; ?>
                        <?php if ($nEntries && $isS): ?><span class="cr-chip cr-chip-mute"><?= $nEntries ?> entri</span><?php endif; ?>
                    </div>
                    <?php if ($isS && $fsBuka !== ''): ?><a href="<?= esc($fsBuka, 'attr') ?>" class="cr-btn cr-btn-sm cr-btn-soft cr-btn-ico" title="Buka tab FlowStore Budget Departemen CCD untuk menautkan BTB ke item ini"><i class="bi bi-box-arrow-up-right"></i><span>Buka antrean FlowStore</span></a><?php endif; ?>
                    <?php if ($isS && $canEditI): ?>
                    <button type="button" class="cr-btn cr-btn-sm cr-btn-primary toggle-add-realisasi" data-iid="<?= $iid ?>"><i class="bi bi-plus-lg"></i>Input</button>
                    <?php endif; ?>
                </div>

                <?php if ($isS): ?>
                <?php if ($canEditI): ?>
                <div id="add-realisasi-<?= $iid ?>" class="cr-formbox" hidden>
                    <p class="cr-note cr-fs-catatan"><i class="bi bi-box-seam"></i>Biaya baru diambil dari FlowStore (tab FlowStore Budget Departemen CCD); isi manual hanya untuk biaya di luar FlowStore.<?php if ($fsBuka !== ''): ?> <a href="<?= esc($fsBuka, 'attr') ?>">Buka antrean FlowStore</a><?php endif; ?></p>
                    <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/add') ?>" <?= in_array($tipe, ['influencer', 'cetak', 'media_prescon']) ? 'enctype="multipart/form-data"' : '' ?>>
                        <?= csrf_field() ?>
                        <div class="cr-fgrid">
                            <div class="cr-f w2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="cr-f w3"><label>Nilai (Rp) <span class="req">*</span></label><input type="text" name="nilai" class="form-control currency-input" placeholder="0" required></div>
                            <?php if ($tipe === 'influencer'): ?>
                            <div class="cr-f w3"><label>Nama Influencer <span class="req">*</span></label><input type="text" name="nama_influencer" class="form-control" placeholder="@username" required></div>
                            <?php endif; ?>
                            <div class="cr-f"><label>Catatan</label><input type="text" name="catatan" class="form-control" placeholder="Opsional"></div>
                            <?php if ($tipe === 'influencer'): ?>
                            <div class="cr-f w6"><label><i class="bi bi-image"></i> Screenshot Insight (opsional)</label><input type="file" name="bukti" class="form-control" accept="image/*"></div>
                            <div class="cr-f w6"><label><i class="bi bi-file-earmark-check"></i> Bukti Serah Terima (opsional)</label><input type="file" name="serah_terima" class="form-control" accept="image/*,.pdf"></div>
                            <?php elseif ($tipe === 'media_prescon'): ?>
                            <div class="cr-f w6"><label><i class="bi bi-image"></i> Dokumentasi (opsional)</label><input type="file" name="bukti" class="form-control" accept="image/*,.pdf"></div>
                            <?php endif; ?>
                            <div class="cr-f w12 cr-f-act" style="justify-content:flex-end"><button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-save"></i>Simpan</button></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (!empty($iReal['entries'])): ?>
                <div class="cr-tbl-wrap">
                <table class="cr-tbl">
                    <thead><tr>
                        <th>Tanggal</th>
                        <?php if ($tipe === 'influencer'): ?><th>Influencer</th><?php endif; ?>
                        <th class="r">Nilai</th>
                        <th>Catatan</th>
                        <?php if ($tipe === 'influencer'): ?><th title="Screenshot Insight"><i class="bi bi-image"></i> Insight</th><th title="Bukti Serah Terima"><i class="bi bi-file-earmark-check"></i> Serah terima</th><?php endif; ?>
                        <?php if ($tipe === 'cetak' && $adaBuktiLama): ?><th title="Bukti terpasang (catatan lama; bukti baru dicatat per titik)"><i class="bi bi-image"></i> Terpasang</th><?php endif; ?>
                        <?php if ($tipe === 'media_prescon'): ?><th title="Dokumentasi"><i class="bi bi-image"></i> Dokumentasi</th><?php endif; ?>
                        <?php if ($canEditI): ?><th style="width:48px"></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($iReal['entries'] as $e): ?>
                    <tr>
                        <td class="nw"><?= $fTgl($e['tanggal']) ?></td>
                        <?php if ($tipe === 'influencer'): ?><td class="fw-semibold"><?= esc($e['nama_influencer'] ?? '—') ?></td><?php endif; ?>
                        <td class="r nw cr-c-ok fw-bold"><?= $rp($e['nilai']) ?></td>
                        <td class="cr-note-cell"><?= esc($e['catatan'] ?? '') ?></td>
                        <?php if ($tipe === 'influencer'): ?>
                        <td><?= $fileCell($e['file_name'] ?? null, $e['original_name'] ?? null, $iid) ?></td>
                        <td><?= $fileCell($e['serah_terima_file_name'] ?? null, $e['serah_terima_original_name'] ?? null, $iid) ?></td>
                        <?php elseif ($tipe === 'cetak' && $adaBuktiLama): ?>
                        <td><?= $fileCell($e['bukti_terpasang_file_name'] ?? null, $e['bukti_terpasang_original_name'] ?? null, $iid) ?></td>
                        <?php elseif ($tipe === 'media_prescon'): ?>
                        <td><?= $fileCell($e['file_name'] ?? null, $e['original_name'] ?? null, $iid) ?></td>
                        <?php endif; ?>
                        <?php if ($canEditI): ?>
                        <td>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/' . $e['id'] . '/delete') ?>" onsubmit="return confirm('Hapus entri ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="cr-iconbtn cr-iconbtn-sm" title="Hapus entri" aria-label="Hapus entri"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr>
                        <td colspan="<?= $tipe === 'influencer' ? 2 : 1 ?>">Total</td>
                        <td class="r nw cr-c-ok"><?= $rp((int) ($iReal['manual'] ?? $iTotal)) ?></td>
                        <td colspan="<?= 1 + ($tipe === 'influencer' ? 2 : (($tipe === 'media_prescon' || ($tipe === 'cetak' && $adaBuktiLama)) ? 1 : 0)) + ($canEditI ? 1 : 0) ?>"></td>
                    </tr></tfoot>
                </table>
                </div>
                <?php elseif (!$iFs): ?>
                <div class="cr-groupempty">Belum ada realisasi biaya.<?= $canEditI ? ' Klik “Input” untuk mencatat.' : '' ?></div>
                <?php endif; ?>
                <?php endif; /* $isS: input & tabel manual */ ?>
                <?= view('_creative/_biaya_fs', ['iid' => $iid, 'rows' => $iFs, 'total' => $iFsTotal, 'dobel' => $iDobel, 'isS' => $isS, 'totalSemua' => $iTotal, 'fsBuka' => $fsBuka, 'rp' => $rp, 'fTgl' => $fTgl], ['saveData' => false]) ?>
                <?php if ($punyaTitik && $ttk['total'] > 0): ?>
                <?= view('_creative/_cetak_bukti', ['iid' => $iid, 'item' => $item, 'titik' => $ttk, 'pfx' => 'cr', 'scope' => 'standalone', 'canEdit' => $canEditI], ['saveData' => false]) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['insight'])): ?>
            <!-- Insight -->
            <div class="cr-pane" data-cr-pane="insight" id="insight-<?= $iid ?>" hidden>
                <div class="cr-pane-h">
                    <div class="cr-pane-t"><i class="bi bi-graph-up"></i>Insight
                        <?php if ($nIns): ?><span class="cr-chip cr-chip-mute"><?= $nIns ?> entri</span><?php endif; ?>
                    </div>
                    <?php if ($canEditI): ?>
                    <button type="button" class="cr-btn cr-btn-sm cr-btn-primary toggle-add-insight" data-iid="<?= $iid ?>"><i class="bi bi-plus-lg"></i>Input</button>
                    <?php endif; ?>
                </div>

                <?php if ($iInsight): ?>
                <?php
                $insightMetricMap = [
                    'views' => $iInsight['max_views'] ?? 0, 'reach' => $iInsight['max_reach'] ?? 0, 'impressions' => $iInsight['max_impressions'] ?? 0,
                    'likes' => $iInsight['max_likes'] ?? 0, 'comments' => $iInsight['max_comments'] ?? 0, 'shares' => $iInsight['max_shares'] ?? 0,
                    'saves' => $iInsight['max_saves'] ?? 0, 'followers_gained' => $iInsight['total_followers_gained'] ?? 0,
                ];
                ?>
                <div class="cr-metrics">
                    <?php foreach ($insightFields as $field => $fcfg): $val = $insightMetricMap[$field] ?? 0; if ($val <= 0) continue; ?>
                    <div class="cr-metric"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i><b><?= number_format($val, 0, ',', '.') ?></b><span><?= $fcfg['label'] ?></span></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (($tgtReach > 0 || $tgtImpr > 0) && ($actReach > 0 || $actImpr > 0)): ?>
                <div style="margin-bottom:.8rem">
                    <?php foreach ([['Reach', $actReach, $tgtReach], ['Impresi', $actImpr, $tgtImpr]] as [$tl, $ta, $tt]): if ($tt <= 0) continue;
                        $pp = min(100, round($ta / $tt * 100)); $bc = $pp >= 100 ? 'cr-bar-ok' : ($pp >= 60 ? '' : 'cr-bar-mid'); ?>
                    <div class="cr-target"><span class="cr-c-mute"><?= $tl ?></span><div class="cr-bar <?= $bc ?>"><i style="width:<?= $pp ?>%"></i></div><em><?= number_format($ta, 0, ',', '.') ?> / <?= number_format($tt, 0, ',', '.') ?> (<?= $pp ?>%)</em></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($canEditI): ?>
                <div id="add-insight-<?= $iid ?>" class="cr-formbox" hidden>
                    <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/add') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="cr-fgrid" style="margin-bottom:.6rem">
                            <div class="cr-f w2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <?php if (($item['platform'] ?? '') === 'keduanya'): ?>
                            <div class="cr-f w2"><label>Platform</label><select name="platform" class="form-select"><option value="ig">Instagram</option><option value="tiktok">TikTok</option></select></div>
                            <?php else: ?>
                            <input type="hidden" name="platform" value="<?= esc($item['platform'] ?? '', 'attr') ?>">
                            <?php endif; ?>
                            <div class="cr-f w4"><label><i class="bi bi-image"></i> Screenshot (opsional)</label><input type="file" name="screenshot" class="form-control" accept="image/*"></div>
                            <div class="cr-f"><label>Catatan</label><input type="text" name="catatan" class="form-control" placeholder="Opsional"></div>
                        </div>
                        <div class="cr-fgrid">
                            <?php foreach ($insightFields as $field => $fcfg): ?>
                            <div class="cr-f w2"><label><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i> <?= $fcfg['label'] ?></label><input type="number" name="<?= $field ?>" class="form-control" value="0" min="0"></div>
                            <?php endforeach; ?>
                            <div class="cr-f w12 cr-f-act" style="justify-content:flex-end"><button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-save"></i>Simpan Insight</button></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($nIns): ?>
                <div class="cr-tbl-wrap">
                <table class="cr-tbl">
                    <thead><tr>
                        <th>Tanggal</th>
                        <?php if (($item['platform'] ?? '') === 'keduanya'): ?><th>Platform</th><?php endif; ?>
                        <?php foreach ($insightFields as $field => $fcfg): ?><th class="r" title="<?= $fcfg['label'] ?>"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i> <?= $fcfg['label'] ?></th><?php endforeach; ?>
                        <th><i class="bi bi-image"></i></th>
                        <th>Catatan</th>
                        <?php if ($canEditI): ?><th style="width:48px"></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($iInsight['entries'] as $ins): ?>
                    <tr>
                        <td class="nw"><?= $fTgl($ins['tanggal']) ?></td>
                        <?php if (($item['platform'] ?? '') === 'keduanya'): ?><td><span class="cr-chip cr-chip-t"><?= esc($platformLabels[$ins['platform']] ?? $ins['platform']) ?></span></td><?php endif; ?>
                        <?php foreach ($insightFields as $field => $fcfg): ?>
                        <td class="r nw <?= $ins[$field] > 0 ? 'fw-semibold' : 'cr-c-mute' ?>"><?= $ins[$field] > 0 ? number_format($ins[$field], 0, ',', '.') : '—' ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ($ins['file_name']): $su = base_url('uploads/creative-standalone-insight/' . $iid . '/' . $ins['file_name']); ?>
                            <a href="<?= $su ?>" target="_blank"><img class="thumb" src="<?= $su ?>" alt="Screenshot insight" title="<?= esc($ins['original_name'] ?? '', 'attr') ?>"></a>
                            <?php else: ?><span class="cr-c-mute">—</span><?php endif; ?>
                        </td>
                        <td class="cr-note-cell"><?= esc($ins['catatan'] ?? '') ?></td>
                        <?php if ($canEditI): ?>
                        <td>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/' . $ins['id'] . '/delete') ?>" onsubmit="return confirm('Hapus entri insight ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="cr-iconbtn cr-iconbtn-sm" title="Hapus entri" aria-label="Hapus entri insight"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="cr-groupempty">Belum ada data insight.<?= $canEditI ? ' Klik “Input” untuk mencatat.' : '' ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
