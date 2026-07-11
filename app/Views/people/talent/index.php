<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Metadata presentasi 9 sel: label + warna. (Data sudah dikelompokkan di controller.)
$cellMeta = [
    '3_1' => ['Trusted Professional', '#e7f6ec', '#1c7a44'],
    '3_2' => ['High Performer',       '#e7f6ec', '#1c7a44'],
    '3_3' => ['Future Leader',         '#e2eefc', '#1c5fa8'],
    '2_1' => ['Effective',             '#fdf6e3', '#8a6d1a'],
    '2_2' => ['Core Player',           '#e7f6ec', '#1c7a44'],
    '2_3' => ['Emerging Potential',    '#e2eefc', '#1c5fa8'],
    '1_1' => ['Exit / Up-or-Out',      '#fdeaea', '#a12a2a'],
    '1_2' => ['Inconsistent',          '#fdf0e3', '#9a5a1a'],
    '1_3' => ['Enigma',                '#fdf0e3', '#9a5a1a'],
];
$quadMeta = [ // 4-box
    'H_H' => ['Future Leader', '#e2eefc', '#1c5fa8'],
    'H_L' => ['Solid Performer', '#e7f6ec', '#1c7a44'],
    'L_H' => ['Need Coaching', '#e2eefc', '#1c5fa8'],
    'L_L' => ['Exit Decision', '#fdeaea', '#a12a2a'],
];
$chip = function ($g) {
    $ini      = strtoupper(mb_substr($g['nama'], 0, 1));
    $verified = ($g['status'] ?? '') === 'verified';
    $cls      = $verified ? 'tal-chip' : 'tal-chip tal-draft';
    $title    = esc(($g['jabatan'] ?? ''), 'attr') . ($verified ? '' : ' — BELUM diverifikasi (draft)');
    $mark     = $verified ? '<span class="tal-ok">✓</span>' : '';
    return '<span class="' . $cls . '" title="' . $title . '"><span class="tal-ini">' . esc($ini) . '</span>' . esc($g['nama']) . $mark . '</span>';
};
?>

<style>
.tal-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.tal-cell { border-radius:10px; padding:8px 10px; min-height:120px; border:1px solid rgba(0,0,0,.06); }
.tal-cell .cell-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px; display:flex; justify-content:space-between; }
.tal-chip { display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,.85); border:1px solid rgba(0,0,0,.08); border-radius:20px; padding:1px 9px 1px 2px; font-size:.72rem; margin:2px 3px 2px 0; color:#222; }
.tal-chip.tal-draft { opacity:.55; border-style:dashed; }
.tal-ok { color:#1c7a44; font-weight:700; }
.tal-ini { width:18px; height:18px; border-radius:50%; background:#16324f; color:#fff; font-size:.62rem; display:inline-flex; align-items:center; justify-content:center; font-weight:700; }
.axis-y { writing-mode:vertical-rl; transform:rotate(180deg); font-weight:700; color:#16324f; font-size:.8rem; text-align:center; letter-spacing:.05em; }
.axis-x { text-align:center; font-weight:700; color:#16324f; font-size:.8rem; letter-spacing:.05em; margin-top:6px; }
.col-lbl { text-align:center; font-size:.68rem; font-weight:600; color:#64748b; text-transform:uppercase; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Talent Portfolio — 9-Box</h4>
        <small class="text-muted">Performance × Potential. Pilih periode &amp; unit untuk menampilkan peta.</small>
    </div>
    <div class="d-flex gap-2">
        <?php if ($isHr): ?>
        <a href="<?= base_url('people/talent/input') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-clipboard-check me-1"></i>Penilaian</a>
        <a href="<?= base_url('people/talent/periods') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar2-range me-1"></i>Periode</a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <a href="<?= base_url('people/talent/viewers') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye me-1"></i>Viewer</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter (wajib pilih dulu) -->
<form method="GET" class="card card-body mb-3 py-2">
<div class="row g-2 align-items-end">
    <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Periode</label>
        <select name="period" class="form-select form-select-sm">
            <?php foreach ($periods as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $periodId == $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?> (<?= $p['status'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Filter berdasarkan</label>
        <select name="ftype" class="form-select form-select-sm" id="ftype" onchange="toggleF()">
            <option value="dept" <?= $ftype === 'dept' ? 'selected' : '' ?>>Departemen</option>
            <option value="jabatan" <?= $ftype === 'jabatan' ? 'selected' : '' ?>>Jabatan</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Unit</label>
        <select name="fid" class="form-select form-select-sm" id="fid_dept" style="<?= $ftype==='jabatan'?'display:none':'' ?>" <?= $ftype==='jabatan'?'disabled':'' ?>>
            <option value="">— pilih departemen —</option>
            <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= $ftype==='dept'&&$fid==$d['id']?'selected':'' ?>><?= esc($d['name']) ?></option><?php endforeach; ?>
        </select>
        <select name="fid" class="form-select form-select-sm" id="fid_jab" style="<?= $ftype==='dept'?'display:none':'' ?>" <?= $ftype==='dept'?'disabled':'' ?>>
            <option value="">— pilih jabatan —</option>
            <?php foreach ($jabatans as $j): ?><option value="<?= $j['id'] ?>" <?= $ftype==='jabatan'&&$fid==$j['id']?'selected':'' ?>><?= esc($j['nama']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
    </div>
</div>
</form>
<script>
function toggleF(){var t=document.getElementById('ftype').value;
 var d=document.getElementById('fid_dept'),j=document.getElementById('fid_jab');
 if(t==='jabatan'){d.style.display='none';d.disabled=true;j.style.display='';j.disabled=false;}
 else{j.style.display='none';j.disabled=true;d.style.display='';d.disabled=false;}}
</script>

<?php if (! $chosen): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-arrow-up-circle fs-1 d-block mb-2"></i>
    Pilih <strong>periode</strong> dan <strong>unit (departemen/jabatan)</strong> di atas, lalu klik <em>Tampilkan</em>.
</div>
<?php else: ?>

<!-- Distribusi + toggle -->
<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div class="small text-muted"><strong><?= $placed ?></strong> dari <strong><?= $total ?></strong> karyawan sudah dinilai
        <?php if (count($unplaced)): ?>· <span class="text-warning"><?= count($unplaced) ?> belum ditempatkan</span><?php endif; ?>
        · <span class="tal-chip" style="vertical-align:middle"><span class="tal-ini">A</span>Terverifikasi <span class="tal-ok">✓</span></span>
        <span class="tal-chip tal-draft" style="vertical-align:middle"><span class="tal-ini">B</span>Draft</span>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <input type="radio" class="btn-check" name="viewmode" id="vm9" checked onchange="setMode(9)">
        <label class="btn btn-outline-secondary" for="vm9">9-Box</label>
        <input type="radio" class="btn-check" name="viewmode" id="vm4" onchange="setMode(4)">
        <label class="btn btn-outline-secondary" for="vm4">4-Box</label>
    </div>
</div>

<!-- ── 9-BOX ── -->
<div id="grid9">
<div style="display:grid; grid-template-columns:28px 1fr; gap:8px;">
    <div class="axis-y d-flex align-items-center justify-content-center">PERFORMANCE →</div>
    <div>
        <div class="tal-grid mb-1"><div class="col-lbl">Potensi Rendah</div><div class="col-lbl">Potensi Sedang</div><div class="col-lbl">Potensi Tinggi</div></div>
        <div class="tal-grid">
        <?php foreach ([3,2,1] as $perf): foreach ([1,2,3] as $pot):
            $m = $cellMeta["{$perf}_{$pot}"]; $list = $cells[$perf][$pot] ?? []; ?>
            <div class="tal-cell" style="background:<?= $m[1] ?>">
                <div class="cell-title" style="color:<?= $m[2] ?>"><span><?= $m[0] ?></span><span><?= count($list) ?></span></div>
                <div><?php foreach ($list as $g) echo $chip($g); ?></div>
            </div>
        <?php endforeach; endforeach; ?>
        </div>
        <div class="axis-x">POTENTIAL →</div>
    </div>
</div>
</div>

<!-- ── 4-BOX ── -->
<div id="grid4" style="display:none">
<div style="display:grid; grid-template-columns:28px 1fr; gap:8px;">
    <div class="axis-y d-flex align-items-center justify-content-center">PERFORMANCE →</div>
    <div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <?php foreach ([['H_L','H_H'],['L_L','L_H']] as $rowq): foreach ($rowq as $qk):
            $m = $quadMeta[$qk]; $list = $quadCells[$qk] ?? []; ?>
            <div class="tal-cell" style="background:<?= $m[1] ?>;min-height:150px">
                <div class="cell-title" style="color:<?= $m[2] ?>"><span><?= $m[0] ?></span><span><?= count($list) ?></span></div>
                <div><?php foreach ($list as $g) echo $chip($g); ?></div>
            </div>
        <?php endforeach; endforeach; ?>
        </div>
        <div class="axis-x">POTENTIAL →</div>
    </div>
</div>
</div>
<script>function setMode(m){document.getElementById('grid9').style.display=m===9?'':'none';document.getElementById('grid4').style.display=m===4?'':'none';}</script>

<?php if (count($unplaced)): ?>
<div class="mt-3">
    <h6 class="fw-semibold text-muted small"><i class="bi bi-hourglass-split me-1"></i>Belum Ditempatkan (<?= count($unplaced) ?>)</h6>
    <div><?php foreach ($unplaced as $g) echo $chip($g); ?></div>
</div>
<?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
