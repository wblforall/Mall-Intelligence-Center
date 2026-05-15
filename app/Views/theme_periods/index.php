<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-stars me-2"></i>Tema Periode</h4>
        <small class="text-muted">Hari besar, liburan, long weekend — atur animasi & pesan untuk setiap periode</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Periode
    </button>
</div>

<style>
.card { overflow: hidden; }
tr.table-success { --bs-table-bg: rgba(34,197,94,.1) !important; }
tr.table-primary  { --bs-table-bg: rgba(99,102,241,.1) !important; }
.table-hover tr.table-success:hover > *,
.table-hover tr.table-primary:hover  > * { --bs-table-bg-state: rgba(255,255,255,.04) !important; }
.anim-badge { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .6rem;border-radius:99px;font-size:.72rem;font-weight:600 }
.anim-none      { background:rgba(100,116,139,.15);color:#94a3b8 }
.anim-confetti  { background:rgba(139,92,246,.15);color:#a78bfa }
.anim-balloons  { background:rgba(236,72,153,.15);color:#f472b6 }
.anim-snow      { background:rgba(56,189,248,.15);color:#38bdf8 }
.anim-fireworks { background:rgba(249,115,22,.15);color:#fb923c }
.anim-stars     { background:rgba(251,191,36,.15);color:#fbbf24 }
.emoji-grid { display:flex;flex-wrap:wrap;gap:4px;margin-top:6px }
.emoji-opt  { font-size:1.35rem;width:2.2rem;height:2.2rem;display:flex;align-items:center;justify-content:center;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:border-color .12s,background .12s }
.emoji-opt:hover  { background:rgba(var(--bs-emphasis-color-rgb),.08) }
.emoji-opt.selected { border-color:var(--bs-primary);background:rgba(var(--bs-primary-rgb),.1) }
</style>

<div class="card fade-up" style="animation-delay:.12s">
<div class="card-body p-0">
<?php if (empty($periods)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3"></i>
    <p class="mt-2 mb-0 small">Belum ada periode. Tambah yang pertama!</p>
</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:.84rem">
<thead class="table-active">
    <tr>
        <th class="ps-3">Nama Periode</th>
        <th>Mulai</th>
        <th>Selesai</th>
        <th class="text-center">Alert</th>
        <th>Animasi</th>
        <th>Pesan</th>
        <th class="text-center">Status</th>
        <th class="text-end pe-3">Aksi</th>
    </tr>
</thead>
<tbody>
<?php foreach ($periods as $i => $p): ?>
<?php
    $today    = date('Y-m-d');
    $isActive = $p['is_active'] && $p['end_date'] >= $today && $p['start_date'] <= $today;
    $isUpcoming = $p['is_active'] && $p['start_date'] > $today;
?>
<tr class="<?= $isActive ? 'table-success' : ($isUpcoming ? 'table-primary' : '') ?> fade-up" style="animation-delay:<?= .18 + $i * .05 ?>s">
    <td class="ps-3 fw-semibold">
        <span style="font-size:1.1rem;margin-right:.35rem"><?= esc($p['emoji'] ?: '🎉') ?></span><?= esc($p['nama']) ?>
    </td>
    <td><?= date('d M Y', strtotime($p['start_date'])) ?></td>
    <td><?= date('d M Y', strtotime($p['end_date'])) ?></td>
    <td class="text-center">
        <?php if ($p['alert_days'] > 0): ?>
        <span class="badge bg-secondary-subtle text-secondary"><?= $p['alert_days'] ?> hari</span>
        <?php else: ?>
        <span class="text-muted small">—</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="anim-badge anim-<?= esc($p['animation']) ?>">
            <?php $animIcon = ['none'=>'bi-slash-circle','confetti'=>'bi-balloon-heart-fill','balloons'=>'bi-balloon-fill','snow'=>'bi-snow','fireworks'=>'bi-stars','stars'=>'bi-star-fill']; ?>
            <i class="bi <?= $animIcon[$p['animation']] ?? 'bi-question' ?>"></i>
            <?= ucfirst($p['animation']) ?>
        </span>
    </td>
    <td class="text-muted" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        <?= $p['pesan'] ? esc($p['pesan']) : '<span class="opacity-35">—</span>' ?>
    </td>
    <td class="text-center">
        <?php if ($isActive): ?>
        <span class="badge bg-success-subtle text-success">Aktif Sekarang</span>
        <?php elseif ($isUpcoming): ?>
        <span class="badge bg-primary-subtle text-primary">Akan Datang</span>
        <?php elseif (!$p['is_active']): ?>
        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
        <?php else: ?>
        <span class="badge bg-secondary-subtle text-muted">Selesai</span>
        <?php endif; ?>
    </td>
    <td class="text-end pe-3">
        <div class="d-flex gap-1 justify-content-end">
            <button class="btn btn-xs btn-outline-secondary edit-btn" style="padding:.2rem .5rem;font-size:.75rem"
                data-id="<?= $p['id'] ?>"
                data-nama="<?= esc($p['nama']) ?>"
                data-start="<?= esc($p['start_date']) ?>"
                data-end="<?= esc($p['end_date']) ?>"
                data-alert="<?= (int)$p['alert_days'] ?>"
                data-animation="<?= esc($p['animation']) ?>"
                data-emoji="<?= esc($p['emoji']) ?>"
                data-pesan="<?= esc($p['pesan']) ?>"
                data-is_active="<?= (int)$p['is_active'] ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('theme-periods/'.$p['id'].'/toggle') ?>"
               class="btn btn-xs <?= $p['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
               style="padding:.2rem .5rem;font-size:.75rem"
               title="<?= $p['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                <i class="bi bi-<?= $p['is_active'] ? 'pause-fill' : 'play-fill' ?>"></i>
            </a>
            <a href="<?= base_url('theme-periods/'.$p['id'].'/delete') ?>"
               class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
               onclick="return confirm('Hapus periode <?= esc($p['nama']) ?>?')">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('theme-periods/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Tambah Periode</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold small">Nama Periode <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" placeholder="cth. Lebaran 2026, Long Weekend Mei, Natal & Tahun Baru" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Alert Mulai (hari sebelum)</label>
            <input type="number" name="alert_days" class="form-control" value="7" min="0" max="60">
            <div class="form-text">Animasi & banner muncul N hari sebelum tanggal mulai</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Animasi</label>
            <select name="animation" class="form-select">
                <option value="none">— Tidak ada —</option>
                <option value="confetti" selected>🎊 Confetti</option>
                <option value="balloons">🎈 Balon</option>
                <option value="snow">❄️ Salju</option>
                <option value="fireworks">🎆 Kembang Api</option>
                <option value="stars">⭐ Bintang</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Emoji</label>
            <input type="hidden" name="emoji" id="add_emoji_val" value="🎉">
            <div class="emoji-grid" id="add_emoji_grid"></div>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small">Pesan Banner</label>
            <input type="text" name="pesan" class="form-control" placeholder="cth. Selamat Hari Raya Idul Fitri 1447 H! Mohon maaf lahir dan batin." maxlength="255">
            <div class="form-text">Ditampilkan sebagai banner di atas halaman selama periode berlangsung</div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" id="editForm" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-pencil-square me-2"></i>Edit Periode</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold small">Nama Periode <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="edit_start" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="end_date" id="edit_end" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Alert Mulai (hari sebelum)</label>
            <input type="number" name="alert_days" id="edit_alert" class="form-control" min="0" max="60">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Animasi</label>
            <select name="animation" id="edit_animation" class="form-select">
                <option value="none">— Tidak ada —</option>
                <option value="confetti">🎊 Confetti</option>
                <option value="balloons">🎈 Balon</option>
                <option value="snow">❄️ Salju</option>
                <option value="fireworks">🎆 Kembang Api</option>
                <option value="stars">⭐ Bintang</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Emoji</label>
            <input type="hidden" name="emoji" id="edit_emoji_val" value="🎉">
            <div class="emoji-grid" id="edit_emoji_grid"></div>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small">Pesan Banner</label>
            <input type="text" name="pesan" id="edit_pesan" class="form-control" maxlength="255">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Status</label>
            <select name="is_active" id="edit_is_active" class="form-select">
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
</div>
</form>
</div></div></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const EMOJIS = [
    '🎉','🎊','🎈','🎁','🎄','🎃','🎆','🎇','✨','⭐',
    '🌟','💫','🌙','🌞','🌈','🌸','🌺','🌻','🏖️','🌊',
    '🔥','❄️','🕌','⛪','🙏','🥳','🎵','🎶','🏆','💥',
    '🌙','🎋','🎍','🎑','🧧','🪔','🕍','🎠','🎡','🎢',
];

function buildEmojiGrid(gridId, inputId, selected) {
    const grid  = document.getElementById(gridId);
    const input = document.getElementById(inputId);
    grid.innerHTML = '';
    EMOJIS.forEach(em => {
        const btn = document.createElement('span');
        btn.className = 'emoji-opt' + (em === selected ? ' selected' : '');
        btn.textContent = em;
        btn.title = em;
        btn.addEventListener('click', function () {
            grid.querySelectorAll('.emoji-opt').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            input.value = em;
        });
        grid.appendChild(btn);
    });
    input.value = selected || EMOJIS[0];
}

// Init add modal grid on open
document.getElementById('addModal').addEventListener('show.bs.modal', function () {
    buildEmojiGrid('add_emoji_grid', 'add_emoji_val', '🎉');
});

// Edit modal
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const d = this.dataset;
        document.getElementById('editForm').action    = '<?= base_url('theme-periods/') ?>' + d.id + '/edit';
        document.getElementById('edit_nama').value    = d.nama;
        document.getElementById('edit_start').value   = d.start;
        document.getElementById('edit_end').value     = d.end;
        document.getElementById('edit_alert').value   = d.alert;
        document.getElementById('edit_animation').value = d.animation;
        document.getElementById('edit_pesan').value   = d.pesan;
        document.getElementById('edit_is_active').value = d.is_active;
        buildEmojiGrid('edit_emoji_grid', 'edit_emoji_val', d.emoji || '🎉');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
