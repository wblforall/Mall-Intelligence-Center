<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>IDP — <?= esc($plan['employee_nama']) ?> · <?= esc($plan['periode_label']) ?></title>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI',Arial,sans-serif; font-size:9.5pt; color:#1e293b; background:#fff; }
.page { max-width:210mm; margin:0 auto; padding:14mm 16mm 16mm; }
h1 { font-size:14pt; font-weight:700; color:#1a4e8a; margin-bottom:2px; }
h2 { font-size:10pt; font-weight:700; color:#1a4e8a; background:#e8f0fb; padding:4px 8px;
     border-left:4px solid #1a4e8a; margin:18px 0 8px; }
table { width:100%; border-collapse:collapse; font-size:8.5pt; margin-bottom:10px; }
th { background:#1a4e8a; color:#fff; padding:5px 8px; text-align:left; font-size:8pt; }
td { padding:4px 8px; border:1px solid #cbd5e1; vertical-align:top; }
tr:nth-child(even) td { background:#f8fafc; }
.meta { display:flex; gap:24px; font-size:8pt; color:#64748b; margin-top:4px; }
.badge { display:inline-block; padding:1px 7px; border-radius:3px; font-size:7.5pt; font-weight:600; }
.badge-primary { background:#dbeafe; color:#1e40af; }
.badge-success { background:#dcfce7; color:#166534; }
.badge-secondary { background:#f1f5f9; color:#475569; }
.badge-danger  { background:#fee2e2; color:#b91c1c; }
.progress-wrap { background:#e2e8f0; border-radius:999px; height:7px; }
.progress-bar  { background:#16a34a; border-radius:999px; height:7px; }
.divider { border:none; border-top:1px solid #e2e8f0; margin:12px 0; }
@media print { @page { size:A4; margin:12mm 14mm; } .page { padding:0; } }
</style>
</head>
<body>
<div class="page">
    <div style="border-bottom:3px solid #1a4e8a; padding-bottom:10px; margin-bottom:18px;">
        <h1>Individual Development Plan</h1>
        <div class="meta">
            <span>PT. Wulandari Bangun Laksana Tbk. — IT Department</span>
            <span>Dicetak: <?= date('d M Y') ?></span>
        </div>
    </div>

    <h2>Informasi IDP</h2>
    <table>
        <tr><td style="width:30%;color:#64748b">Karyawan</td><td><strong><?= esc($plan['employee_nama']) ?></strong></td>
            <td style="width:25%;color:#64748b">Departemen</td><td><?= esc($plan['dept_name'] ?? '-') ?></td></tr>
        <tr><td style="color:#64748b">Jabatan</td><td><?= esc($plan['jabatan'] ?? '-') ?></td>
            <td style="color:#64748b">Atasan</td><td><?= esc($plan['atasan_nama'] ?? '-') ?></td></tr>
        <tr><td style="color:#64748b">Periode</td><td><?= esc($plan['periode_label']) ?></td>
            <td style="color:#64748b">Tahun</td><td><?= $plan['tahun'] ?></td></tr>
        <tr><td style="color:#64748b">Status</td>
            <td><?php
                $sl = ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
                echo '<span class="badge badge-primary">' . ($sl[$plan['status']] ?? $plan['status']) . '</span>';
            ?></td>
            <td style="color:#64748b">Persetujuan Atasan</td>
            <td><?php
                $al = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
                echo $al[$plan['persetujuan_atasan']] ?? '-';
                if ($plan['approved_at']) echo ' · ' . date('d M Y', strtotime($plan['approved_at']));
            ?></td></tr>
        <?php if ($plan['tujuan_karir']): ?>
        <tr><td style="color:#64748b">Tujuan Karir</td><td colspan="3"><?= nl2br(esc($plan['tujuan_karir'])) ?></td></tr>
        <?php endif; ?>
    </table>

    <h2>Goal Pengembangan</h2>
    <?php if (empty($items)): ?>
    <p style="color:#64748b">Belum ada goal.</p>
    <?php else: ?>
    <?php
    $totalItems   = count($items);
    $selesaiItems = count(array_filter($items, fn($i) => $i['status'] === 'selesai'));
    $pct          = $totalItems > 0 ? round($selesaiItems / $totalItems * 100) : 0;
    $isl = ['belum_mulai'=>'Belum Mulai','dalam_proses'=>'Dalam Proses','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
    ?>
    <div style="margin-bottom:10px;">
        <div style="font-size:8pt;color:#64748b;margin-bottom:3px">Progress: <?= $selesaiItems ?>/<?= $totalItems ?> goal selesai (<?= $pct ?>%)</div>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:22%">Goal / Kompetensi</th>
                <th style="width:8%">Level</th>
                <th style="width:28%">Langkah Aksi</th>
                <th style="width:10%">Deadline</th>
                <th style="width:13%">Status</th>
                <th>Progres</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td>
                <strong><?= esc($item['judul']) ?></strong>
                <?php if ($item['competency_nama']): ?>
                <br><small style="color:#64748b"><?= esc($item['competency_nama']) ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align:center">
                <?= $item['level_saat_ini'] ? number_format((float)$item['level_saat_ini'], 1) : '-' ?>
                <?= ($item['level_saat_ini'] && $item['level_target']) ? ' → ' . $item['level_target'] : '' ?>
            </td>
            <td><?= nl2br(esc($item['langkah_aksi'] ?? '-')) ?></td>
            <td><?= $item['deadline'] ? date('d M Y', strtotime($item['deadline'])) : '-' ?></td>
            <td><?= $isl[$item['status']] ?? $item['status'] ?></td>
            <td><?= nl2br(esc($item['catatan_progres'] ?? '-')) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <hr class="divider">
    <div style="display:flex;justify-content:space-between;font-size:8pt;color:#64748b;margin-top:8px;">
        <div>Mall Intelligence Center v1.9 — IT Dept PT. Wulandari Bangun Laksana Tbk.</div>
        <div>Individual Development Plan</div>
    </div>
</div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
