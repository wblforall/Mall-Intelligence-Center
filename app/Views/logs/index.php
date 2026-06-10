<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-journal-text me-2"></i>Activity Log</h4>
        <small class="text-muted">Riwayat seluruh aktivitas sistem</small>
    </div>
    <span class="badge bg-secondary"><?= number_format($total) ?> entri</span>
</div>

<!-- Filter -->
<div class="card mb-4 fade-up" style="animation-delay:.12s">
<div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Modul</label>
        <select name="module" class="form-select form-select-sm">
            <option value="">Semua modul</option>
            <?php foreach ($modules as $m): ?>
            <option value="<?= $m['module'] ?>" <?= $filters['module'] === $m['module'] ? 'selected' : '' ?>>
                <?= esc(\App\Libraries\ActivityLog::moduleLabel($m['module'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Aksi</label>
        <select name="action" class="form-select form-select-sm">
            <option value="">Semua aksi</option>
            <?php foreach (['login','logout','login_failed','create','update','delete'] as $a): ?>
            <option value="<?= $a ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">User</label>
        <select name="user_id" class="form-select form-select-sm">
            <option value="">Semua user</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= $u['user_id'] ?>" <?= $filters['user_id'] == $u['user_id'] ? 'selected' : '' ?>><?= esc($u['user_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $filters['from'] ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $filters['to'] ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Cari target</label>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="nama / label..." value="<?= esc($filters['q']) ?>">
    </div>
    <div class="col-auto d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="<?= base_url('logs') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>
</div>
</div>

<?php
$actionBadge = [
    'login'        => ['bg' => 'var(--c-action-login-bg)',  'color' => 'var(--c-action-login-fg)',  'icon' => 'box-arrow-in-right'],
    'logout'       => ['bg' => 'var(--c-action-logout-bg)', 'color' => 'var(--c-action-logout-fg)', 'icon' => 'box-arrow-right'],
    'login_failed' => ['bg' => 'var(--c-action-fail-bg)',   'color' => 'var(--c-action-fail-fg)',   'icon' => 'x-circle'],
    'create'       => ['bg' => 'var(--c-action-create-bg)', 'color' => 'var(--c-action-create-fg)', 'icon' => 'plus-circle'],
    'update'       => ['bg' => 'var(--c-action-update-bg)', 'color' => 'var(--c-action-update-fg)', 'icon' => 'pencil'],
    'delete'       => ['bg' => 'var(--c-action-delete-bg)', 'color' => 'var(--c-action-delete-fg)', 'icon' => 'trash'],
    'upload'       => ['bg' => 'var(--c-action-upload-bg)', 'color' => 'var(--c-action-upload-fg)', 'icon' => 'upload'],
];
$moduleBadge = [
    'auth'    => 'secondary',
    'traffic' => 'primary',
    'event'   => 'success',
    'user'    => 'info',
    'role'    => 'warning',
    'door'    => 'dark',
];
?>

<div class="card fade-up" style="animation-delay:.2s">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0 align-middle">
<thead class="table-light">
<tr>
    <th style="width:150px">Waktu</th>
    <th style="width:140px">User</th>
    <th style="width:130px">IP / Komputer</th>
    <th style="width:110px">Aksi</th>
    <th style="width:90px">Modul</th>
    <th>Target / Keterangan</th>
    <th style="width:60px"></th>
</tr>
</thead>
<tbody>
<?php if (empty($logs)): ?>
<tr><td colspan="7" class="text-center text-muted py-4">Belum ada log yang sesuai filter.</td></tr>
<?php endif; ?>
<?php foreach ($logs as $log):
    $ab = $actionBadge[$log['action']] ?? ['bg' => '#f1f5f9', 'color' => '#334155', 'icon' => 'circle'];
    $mbColor = $moduleBadge[$log['module']] ?? 'secondary';
?>
<tr>
    <td class="small text-muted">
        <?= date('d/m/y', strtotime($log['created_at'])) ?>
        <div style="font-size:.7rem"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
    </td>
    <td>
        <div class="small fw-medium"><?= esc($log['user_name']) ?></div>
        <div style="font-size:.7rem" class="text-muted"><?= esc($log['user_role']) ?></div>
    </td>
    <td class="small text-muted">
        <div><?= esc($log['ip_address'] ?? '—') ?></div>
        <?php if (!empty($log['computer_name']) && $log['computer_name'] !== $log['ip_address']): ?>
        <div style="font-size:.7rem"><?= esc($log['computer_name']) ?></div>
        <?php endif; ?>
    </td>
    <td>
        <span class="badge rounded-pill px-2 py-1" style="background:<?= $ab['bg'] ?>;color:<?= $ab['color'] ?>;font-size:.72rem">
            <i class="bi bi-<?= $ab['icon'] ?> me-1"></i><?= $log['action'] ?>
        </span>
    </td>
    <td>
        <span class="badge bg-<?= $mbColor ?>-subtle text-<?= $mbColor ?>"><?= esc(\App\Libraries\ActivityLog::moduleLabel($log['module'])) ?></span>
    </td>
    <td class="small">
        <?php if ($log['target_label']): ?>
        <span class="fw-medium"><?= esc($log['target_label']) ?></span>
        <?php endif; ?>
        <?php if ($log['target_id']): ?>
        <span class="text-muted">#<?= esc($log['target_id']) ?></span>
        <?php endif; ?>
        <?php if ($log['detail']): ?>
        <div class="text-muted" style="font-size:.7rem;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= esc(str_replace(["\n","  "], ' ', $log['detail'])) ?>
        </div>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($log['detail']): ?>
        <button class="btn btn-xs btn-outline-secondary py-0 px-1 detail-btn"
            style="font-size:.72rem"
            data-id="<?= $log['id'] ?>"
            data-label="<?= esc($log['target_label']) ?>"
            data-action="<?= $log['action'] ?>"
            data-module="<?= esc(\App\Libraries\ActivityLog::moduleLabel($log['module'])) ?>"
            data-time="<?= date('d M Y H:i:s', strtotime($log['created_at'])) ?>"
            data-ip="<?= esc($log['ip_address'] ?? '') ?>"
            data-computer="<?= esc($log['computer_name'] ?? '') ?>"
            data-detail="<?= esc($log['detail']) ?>">
            Detail
        </button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
<ul class="pagination pagination-sm justify-content-center flex-wrap">
    <?php
    $currentPage = (int)($filters['page'] ?? 1);
    $baseQuery   = http_build_query(array_merge($filters, ['page' => '']));
    for ($p = 1; $p <= $pages; $p++):
    ?>
    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
        <a class="page-link" href="?<?= $baseQuery ?><?= $p ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
</ul>
</nav>
<?php endif; ?>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h6 class="modal-title fw-semibold">
        <i class="bi bi-journal-text me-2"></i>
        Detail Log — <span id="modalLabel"></span>
    </h6>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-2 mb-3 small text-muted">
        <div class="col-auto"><i class="bi bi-clock me-1"></i><span id="modalTime"></span></div>
        <div class="col-auto"><i class="bi bi-tag me-1"></i>Modul: <strong id="modalModule"></strong></div>
        <div class="col-auto"><i class="bi bi-lightning me-1"></i>Aksi: <strong id="modalAction"></strong></div>
        <div class="col-auto"><i class="bi bi-hdd-network me-1"></i>IP: <strong id="modalIp"></strong></div>
        <div class="col-auto" id="modalComputerWrap"><i class="bi bi-pc-display me-1"></i>Komputer: <strong id="modalComputer"></strong></div>
    </div>
    <div id="modalDetail" class="p-3 rounded small" style="background:rgba(139,92,246,.08);max-height:400px;overflow:auto;border:1px solid rgba(139,92,246,.2)"></div>
</div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmtVal(v) {
    if (v === null || v === undefined || v === '') return '<em style="color:#6b7280;font-style:italic">kosong</em>';
    if (typeof v === 'object') return `<pre class="mb-0" style="font-size:.75rem;white-space:pre-wrap">${esc(JSON.stringify(v,null,2))}</pre>`;
    return esc(String(v));
}
function renderDetail(raw) {
    try {
        const parsed = JSON.parse(raw);
        if (typeof parsed !== 'object' || parsed === null) return `<p class="mb-0">${esc(parsed)}</p>`;

        const before = parsed['_before'];
        const after  = parsed['_after'];

        // Render diff if before or after present
        if (before || after) {
            const skip = ['id','created_at','updated_at','deleted_at'];
            const allKeys = new Set([
                ...(before ? Object.keys(before) : []),
                ...(after  ? Object.keys(after)  : []),
            ].filter(k => !skip.includes(k)));

            let changed = [], unchanged = [];
            allKeys.forEach(k => {
                const bv = before ? String(before[k] ?? '') : '';
                // Jika key tidak ada sama sekali di _after (partial update), jangan tampilkan sebagai perubahan
                const afterHasKey = after && (k in after);
                const av = afterHasKey ? String(after[k] ?? '') : null;
                if (av === null) unchanged.push(k);
                else if (bv !== av) changed.push(k);
                else unchanged.push(k);
            });

            let html = '';

            // Other detail keys (not _before/_after)
            const extraKeys = Object.keys(parsed).filter(k => k !== '_before' && k !== '_after');
            if (extraKeys.length) {
                html += '<table class="table table-sm table-bordered mb-2" style="font-size:.8rem">';
                extraKeys.forEach(k => {
                    const label = k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                    html += `<tr><th style="width:30%;background:var(--c-modal-th-bg)">${esc(label)}</th><td>${fmtVal(parsed[k])}</td></tr>`;
                });
                html += '</table>';
            }

            if (changed.length) {
                html += `<div class="fw-semibold small mb-1 text-danger"><i class="bi bi-arrow-left-right me-1"></i>Perubahan (${changed.length} field)</div>`;
                html += '<table class="table table-sm table-bordered mb-2" style="font-size:.8rem">';
                html += '<thead><tr><th style="width:25%;background:var(--c-modal-th-bg)">Field</th><th style="background:#991b1b;color:#fef2f2">Sebelum</th><th style="background:#166534;color:#f0fdf4">Sesudah</th></tr></thead><tbody>';
                changed.forEach(k => {
                    const label = k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                    const bRaw  = before ? before[k] : null;
                    const aRaw  = (after && (k in after)) ? after[k] : null;
                    const bTxt  = (bRaw === null || bRaw === undefined || bRaw === '') ? '<em style="opacity:.6;font-style:italic">kosong</em>' : esc(String(bRaw));
                    const aTxt  = (aRaw === null || aRaw === undefined || aRaw === '') ? '<em style="opacity:.6;font-style:italic">kosong</em>' : esc(String(aRaw));
                    html += `<tr>
                        <th style="background:var(--c-modal-th-bg);white-space:nowrap">${esc(label)}</th>
                        <td style="background:#991b1b;color:#fef2f2;font-weight:500">${bTxt}</td>
                        <td style="background:#166534;color:#f0fdf4;font-weight:500">${aTxt}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
            }

            if (unchanged.length) {
                html += `<details><summary class="small text-muted mb-1" style="cursor:pointer">${unchanged.length} field tidak berubah</summary>`;
                html += '<table class="table table-sm table-bordered mt-1 mb-0" style="font-size:.78rem">';
                unchanged.forEach(k => {
                    const label = k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                    html += `<tr><th style="width:30%;background:var(--c-modal-th-bg)">${esc(label)}</th><td>${fmtVal(before ? before[k] : after?.[k])}</td></tr>`;
                });
                html += '</table></details>';
            }

            return html || '<p class="text-muted small mb-0">Tidak ada perubahan tercatat.</p>';
        }

        // Regular detail table
        let html = '<table class="table table-sm table-bordered mb-0" style="font-size:.82rem">';
        for (const [k, v] of Object.entries(parsed)) {
            const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            html += `<tr><th style="width:35%;white-space:nowrap;background:var(--c-modal-th-bg)">${esc(label)}</th><td>${fmtVal(v)}</td></tr>`;
        }
        html += '</table>';
        return html;
    } catch(e) {
        return `<pre style="white-space:pre-wrap;word-break:break-word;margin:0">${esc(raw)}</pre>`;
    }
}

document.querySelectorAll('.detail-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modalLabel').textContent   = this.dataset.label || '—';
        document.getElementById('modalTime').textContent    = this.dataset.time;
        document.getElementById('modalModule').textContent  = this.dataset.module;
        document.getElementById('modalAction').textContent  = this.dataset.action;
        document.getElementById('modalIp').textContent      = this.dataset.ip || '—';
        const comp = this.dataset.computer;
        document.getElementById('modalComputer').textContent = comp || '—';
        document.getElementById('modalComputerWrap').style.display = comp ? '' : 'none';
        document.getElementById('modalDetail').innerHTML = renderDetail(this.dataset.detail);
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
