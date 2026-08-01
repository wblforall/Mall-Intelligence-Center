<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
    <a href="<?= base_url('kanban') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <span class="d-inline-block rounded" style="width:14px;height:14px;background:<?= esc($board['color'] ?: '#e8415a') ?>"></span>
    <div class="flex-grow-1 min-w-0">
        <h5 class="fw-bold mb-0 text-truncate"><?= esc($board['nama']) ?></h5>
        <?php if ($board['deskripsi']): ?><small class="text-muted"><?= esc($board['deskripsi']) ?></small><?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="kb-avatars" id="memberAvatars" title="Anggota board">
            <?php foreach (array_slice($members, 0, 5) as $m): ?>
            <span class="kb-av" title="<?= esc($m['name']) ?> (<?= $m['role'] ?>)"><?= esc(mb_strtoupper(mb_substr($m['name'], 0, 2))) ?></span>
            <?php endforeach; ?>
            <?php if (count($members) > 5): ?><span class="kb-av more">+<?= count($members) - 5 ?></span><?php endif; ?>
        </div>
        <?php if ($canManage): ?>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#membersModal"><i class="bi bi-people me-1"></i>Anggota</button>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#labelsModal"><i class="bi bi-tags me-1"></i>Label</button>
        <?php endif; ?>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= base_url('kanban/' . $board['id'] . '/arsip') ?>"><i class="bi bi-archive me-2"></i>Item terarsip</a></li>
                <?php if ($canManage): ?>
                <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#boardSettingModal"><i class="bi bi-pencil me-2"></i>Ubah board</button></li>
                <li>
                    <form method="POST" action="<?= base_url('kanban/' . $board['id'] . '/archive') ?>" onsubmit="return confirm('<?= $board['is_archived'] ? 'Pulihkan' : 'Arsipkan' ?> board ini?')">
                        <?= csrf_field() ?>
                        <button class="dropdown-item"><i class="bi bi-archive me-2"></i><?= $board['is_archived'] ? 'Pulihkan' : 'Arsipkan' ?> board</button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="<?= base_url('kanban/' . $board['id'] . '/delete') ?>" onsubmit="return confirm('HAPUS PERMANEN board beserta seluruh isinya? Tidak bisa dibatalkan.')">
                        <?= csrf_field() ?>
                        <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus permanen</button>
                    </form>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Filter bar (client-side) -->
<div class="kb-filter d-flex align-items-center gap-2 flex-wrap mb-3">
    <div class="input-group input-group-sm" style="max-width:220px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="fKeyword" placeholder="Cari kartu…">
    </div>
    <select class="form-select form-select-sm" id="fLabel" style="max-width:160px"><option value="">Semua label</option></select>
    <select class="form-select form-select-sm" id="fMember" style="max-width:170px"><option value="">Semua assignee</option></select>
    <select class="form-select form-select-sm" id="fDue" style="max-width:160px">
        <option value="">Semua tenggat</option>
        <option value="over">Terlambat</option>
        <option value="week">7 hari ke depan</option>
        <option value="none">Tanpa tenggat</option>
    </select>
    <button class="btn btn-sm btn-outline-secondary d-none" id="fReset"><i class="bi bi-x-lg me-1"></i>Reset</button>
</div>

<!-- Papan -->
<div class="kb-wrap" id="kbWrap">
    <div class="kb-lists" id="kbLists"></div>
    <?php if ($canEdit): ?>
    <div class="kb-addlist">
        <button class="btn btn-sm w-100 text-start kb-addlist-btn" id="addListBtn"><i class="bi bi-plus-lg me-1"></i>Tambah kolom</button>
        <form id="addListForm" class="d-none">
            <input type="text" class="form-control form-control-sm mb-2" id="addListName" maxlength="120" placeholder="Nama kolom">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Tambah</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addListCancel">Batal</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- ══ Modal kartu (penuh) ══ -->
<div class="modal fade" id="cardModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header py-2">
        <input type="text" id="cmJudul" class="form-control form-control-sm fw-semibold border-0 bg-transparent fs-6" maxlength="255" <?= $canEdit ? '' : 'readonly' ?>>
        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body pt-2">
        <input type="hidden" id="cmId">
        <div class="small text-muted mb-3" id="cmMeta"></div>

        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-text-left me-1"></i>Deskripsi</label>
                <textarea id="cmDesk" class="form-control form-control-sm" rows="3" placeholder="(opsional)" <?= $canEdit ? '' : 'readonly' ?>></textarea>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-clock me-1"></i>Tenggat</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="date" id="cmDue" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
                    <div class="form-check form-check-inline m-0">
                        <input type="checkbox" class="form-check-input" id="cmDueDone" <?= $canEdit ? '' : 'disabled' ?>>
                        <label class="form-check-label small" for="cmDueDone">Selesai</label>
                    </div>
                </div>
                <?php if ($canEdit): ?><button class="btn btn-sm btn-primary mt-2 w-100" id="cmSave"><i class="bi bi-save me-1"></i>Simpan perubahan</button><?php endif; ?>
            </div>
        </div>

        <!-- Label -->
        <div class="mt-3">
            <label class="form-label small fw-semibold mb-1"><i class="bi bi-tags me-1"></i>Label</label>
            <div class="d-flex gap-2 flex-wrap" id="cmLabels"></div>
        </div>

        <!-- Anggota kartu -->
        <div class="mt-3">
            <label class="form-label small fw-semibold mb-1"><i class="bi bi-people me-1"></i>Anggota kartu</label>
            <div class="d-flex gap-2 flex-wrap" id="cmMembers"></div>
        </div>

        <!-- Checklist -->
        <div class="mt-3 d-flex align-items-center justify-content-between">
            <label class="form-label small fw-semibold mb-0"><i class="bi bi-check2-square me-1"></i>Checklist</label>
            <?php if ($canEdit): ?><button class="btn btn-sm btn-outline-secondary" id="cmAddChecklist"><i class="bi bi-plus-lg me-1"></i>Checklist</button><?php endif; ?>
        </div>
        <div id="cmChecklists" class="mt-2"></div>

        <!-- Lampiran -->
        <div class="mt-3 d-flex align-items-center justify-content-between">
            <label class="form-label small fw-semibold mb-0"><i class="bi bi-paperclip me-1"></i>Lampiran</label>
            <?php if ($canEdit): ?>
            <label class="btn btn-sm btn-outline-secondary mb-0">
                <i class="bi bi-upload me-1"></i>Upload
                <input type="file" id="cmFile" class="d-none" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
            </label>
            <?php endif; ?>
        </div>
        <div id="cmAttachments" class="mt-2"></div>

        <!-- Komentar -->
        <div class="mt-3">
            <label class="form-label small fw-semibold mb-1"><i class="bi bi-chat-left-text me-1"></i>Komentar</label>
            <div class="d-flex gap-2">
                <textarea id="cmCommentBody" class="form-control form-control-sm" rows="2" placeholder="Tulis komentar…"></textarea>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">@ Sebut</button>
                    <ul class="dropdown-menu" id="cmMentionMenu" style="max-height:220px; overflow-y:auto"></ul>
                </div>
                <div class="small text-muted flex-grow-1" id="cmMentionInfo"></div>
                <button class="btn btn-sm btn-primary" id="cmCommentSend"><i class="bi bi-send me-1"></i>Kirim</button>
            </div>
            <div id="cmComments" class="mt-2"></div>
        </div>

        <!-- Aktivitas -->
        <div class="mt-3">
            <a class="small text-decoration-none" data-bs-toggle="collapse" href="#cmActivityWrap"><i class="bi bi-activity me-1"></i>Aktivitas kartu</a>
            <div class="collapse" id="cmActivityWrap"><div id="cmActivity" class="mt-2"></div></div>
        </div>
    </div>
    <div class="modal-footer py-2 <?= $canEdit ? '' : 'd-none' ?>">
        <button type="button" class="btn btn-sm btn-outline-danger me-auto" id="cmArchive"><i class="bi bi-archive me-1"></i>Arsipkan kartu</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
</div></div>
</div>

<?php if ($canManage): ?>
<!-- ══ Modal anggota ══ -->
<div class="modal fade" id="membersModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold"><i class="bi bi-people me-1"></i>Anggota Board</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="d-flex gap-2 mb-3">
            <select class="form-select form-select-sm" id="mmUser">
                <option value="">— pilih user —</option>
                <?php foreach ($userPick as $u): ?>
                <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> — <?= esc($u['email']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" id="mmRole" style="max-width:110px">
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
            </select>
            <button class="btn btn-sm btn-primary" id="mmAdd">Tambah</button>
        </div>
        <div class="small text-muted mb-2">Viewer = lihat + komentar. Editor = kelola kartu/kolom. Owner = kelola board.</div>
        <div id="mmList">
            <?php foreach ($members as $m): ?>
            <div class="d-flex align-items-center gap-2 py-2 border-bottom border-secondary-subtle">
                <span class="kb-av"><?= esc(mb_strtoupper(mb_substr($m['name'], 0, 2))) ?></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="small fw-semibold text-truncate"><?= esc($m['name']) ?></div>
                    <div class="text-muted" style="font-size:.72rem"><?= esc($m['email']) ?></div>
                </div>
                <?php if ($m['role'] === 'owner'): ?>
                <span class="badge bg-danger-subtle text-danger-emphasis">Owner</span>
                <?php else: ?>
                <select class="form-select form-select-sm mm-role" data-mid="<?= $m['id'] ?>" style="width:100px">
                    <option value="editor" <?= $m['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="viewer" <?= $m['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                </select>
                <button class="btn btn-sm btn-link text-danger p-0 mm-remove" data-mid="<?= $m['id'] ?>" title="Keluarkan"><i class="bi bi-x-lg"></i></button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div></div>
</div>

<!-- ══ Modal label ══ -->
<div class="modal fade" id="labelsModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold"><i class="bi bi-tags me-1"></i>Palet Label Board</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="lmRows"></div>
        <button class="btn btn-sm btn-outline-secondary mt-2" id="lmAdd"><i class="bi bi-plus-lg me-1"></i>Tambah label</button>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-sm btn-primary" id="lmSave"><i class="bi bi-save me-1"></i>Simpan Label</button>
    </div>
</div></div>
</div>

<!-- Modal setelan board -->
<div class="modal fade" id="boardSettingModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold">Ubah Board</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" id="bsNama" class="form-control" maxlength="150" value="<?= esc($board['nama']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Deskripsi</label>
            <textarea id="bsDesk" class="form-control" rows="2"><?= esc($board['deskripsi']) ?></textarea>
        </div>
        <div>
            <label class="form-label small fw-semibold">Warna</label>
            <div class="d-flex gap-2 flex-wrap" id="bsColors">
                <?php foreach (['#e8415a', '#22d3ee', '#f97316', '#10b981', '#3b82f6', '#a855f7', '#64748b'] as $c): ?>
                <label class="kb-color-opt">
                    <input type="radio" name="bs_color" value="<?= $c ?>" <?= ($board['color'] ?: '#e8415a') === $c ? 'checked' : '' ?> class="d-none">
                    <span style="background:<?= $c ?>"></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-sm btn-primary" id="bsSave"><i class="bi bi-save me-1"></i>Simpan</button>
    </div>
</div></div>
</div>
<?php endif; ?>

<style>
.kb-wrap { display:flex; gap:12px; align-items:flex-start; overflow-x:auto; padding-bottom:14px; min-height:60vh; }
.kb-lists { display:flex; gap:12px; align-items:flex-start; }
.kb-list { width:280px; flex:none; background:var(--card-bg, rgba(14,26,42,.92)); border:1px solid var(--card-border, rgba(255,255,255,.06)); border-radius:.9rem; padding:10px; }
.kb-list-head { display:flex; align-items:center; gap:6px; padding:2px 4px 8px; }
.kb-list-title { font-weight:600; font-size:.9rem; flex:1; min-width:0; cursor:text; border-radius:.35rem; padding:2px 6px; }
.kb-list-title:focus { outline:1px solid rgba(232,65,90,.5); background:rgba(255,255,255,.05); }
.kb-list-count { font-size:.72rem; color:var(--txt-muted, rgba(180,210,255,.42)); }
.kb-cards { display:flex; flex-direction:column; gap:8px; min-height:8px; }
.kb-card { position:relative; background:var(--c-inner-bg, rgba(255,255,255,.04)); border:1px solid var(--c-inner-border, rgba(255,255,255,.07)); border-radius:.6rem; padding:8px 10px; cursor:pointer; font-size:.86rem; }
.kb-card:hover { border-color:rgba(232,65,90,.35); }
.kb-card .kb-lstrip { display:flex; gap:4px; margin-bottom:6px; flex-wrap:wrap; }
.kb-card .kb-lchip { height:7px; width:32px; border-radius:99px; }
.kb-card .kb-badges { display:flex; gap:8px; align-items:center; margin-top:7px; flex-wrap:wrap; font-size:.7rem; color:var(--txt-muted, rgba(180,210,255,.5)); }
.kb-badges .b { display:inline-flex; align-items:center; gap:3px; }
.kb-due { font-size:.7rem; padding:1px 7px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; }
.kb-due.ok   { background:rgba(16,185,129,.2);  color:#6ee7b7; }
.kb-due.soon { background:rgba(249,115,22,.2);  color:#fdba74; }
.kb-due.over { background:rgba(239,68,68,.2);   color:#fca5a5; }
.kb-unread { position:absolute; top:-4px; right:-4px; width:11px; height:11px; border-radius:50%; background:#e8415a; box-shadow:0 0 0 2px rgba(9,21,40,.9); }
.kb-avatars { display:flex; }
.kb-av { width:28px; height:28px; border-radius:50%; background:rgba(232,65,90,.22); color:#f8829a; font-size:.62rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; border:2px solid rgba(9,21,40,.9); flex:none; }
.kb-avatars .kb-av + .kb-av { margin-left:-8px; }
.kb-av.sm { width:22px; height:22px; font-size:.55rem; border-width:1px; }
.kb-av.more { background:rgba(255,255,255,.1); color:var(--txt, #dde8f8); }
.kb-card .kb-cardavs { position:absolute; right:8px; bottom:7px; display:flex; }
.kb-addcard-btn, .kb-addlist-btn { color:var(--txt-muted, rgba(180,210,255,.5)); background:transparent; border:1px dashed rgba(255,255,255,.15); }
.kb-addcard-btn:hover, .kb-addlist-btn:hover { color:#fff; border-color:rgba(232,65,90,.5); }
.kb-addlist { width:280px; flex:none; }
.kb-composer textarea { font-size:.86rem; }
.sortable-ghost { opacity:.35; }
.sortable-drag { transform:rotate(2deg); }
.kb-list-grab { cursor:grab; color:var(--txt-muted, rgba(180,210,255,.35)); }
.kb-color-opt span { display:block; width:34px; height:34px; border-radius:9px; cursor:pointer; opacity:.55; border:2px solid transparent; }
.kb-color-opt input:checked + span { opacity:1; border-color:#fff; box-shadow:0 0 0 2px rgba(255,255,255,.25); }
/* modal kartu */
.cm-label { padding:4px 12px; border-radius:8px; font-size:.75rem; font-weight:600; cursor:pointer; opacity:.45; border:1px solid transparent; color:#fff; }
.cm-label.on { opacity:1; box-shadow:0 0 0 1.5px rgba(255,255,255,.4); }
.cm-label.readonly { cursor:default; }
.cm-member { display:inline-flex; align-items:center; gap:6px; padding:3px 10px 3px 4px; border-radius:99px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); font-size:.75rem; cursor:pointer; opacity:.5; }
.cm-member.on { opacity:1; border-color:rgba(232,65,90,.5); background:rgba(232,65,90,.12); }
.cm-member.readonly { cursor:default; }
.cm-checklist { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.07); border-radius:.6rem; padding:10px 12px; margin-bottom:8px; }
.cm-cl-item { display:flex; align-items:center; gap:8px; padding:3px 0; font-size:.84rem; }
.cm-cl-item.done label { text-decoration:line-through; opacity:.55; }
.cm-comment { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:.6rem; padding:8px 12px; margin-bottom:7px; font-size:.84rem; }
.cm-att { display:flex; align-items:center; gap:9px; padding:7px 10px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:.55rem; margin-bottom:6px; font-size:.82rem; }
.cm-act { font-size:.76rem; padding:4px 0; color:var(--txt-muted, rgba(180,210,255,.55)); border-bottom:1px dashed rgba(255,255,255,.06); }
#cmJudul:focus { background:rgba(255,255,255,.05) !important; }
</style>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/sortable.min.js') ?>"></script>
<script>
(function () {
'use strict';
const BOARD_ID  = <?= (int) $board['id'] ?>;
const CAN_EDIT  = <?= $canEdit ? 'true' : 'false' ?>;
const MY_ID     = <?= (int) $user['id'] ?>;
const BASE      = '<?= rtrim(base_url(), '/') ?>';
const csrfName  = '<?= csrf_token() ?>';
let   csrfHash  = '<?= csrf_hash() ?>';

let state = {
    rev:     <?= json_encode($board['updated_at']) ?>,
    lists:   <?= json_encode(array_values($lists)) ?>,
    cards:   <?= json_encode((object) $cards) ?>,
    labels:  <?= json_encode(array_values($labels)) ?>,
    members: <?= json_encode(array_values($members)) ?>,
    unread:  <?= json_encode(array_values($unreadCardIds)) ?>,
};
let dragging = false, modalOpen = false;
let filter = { q: '', label: '', member: '', due: '' };

// ── util ──────────────────────────────────────────────────────────────
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const initials = n => (n || '?').trim().substring(0, 2).toUpperCase();
function syncCsrf(h) { if (!h) return; csrfHash = h; document.querySelectorAll(`input[name="${csrfName}"]`).forEach(i => i.value = h); }
function post(url, data, isForm) {
    let body;
    if (isForm) { body = data; }
    else {
        body = new FormData();
        Object.entries(data || {}).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => body.append(k + '[]', x)) : body.append(k, v));
    }
    body.append(csrfName, csrfHash);
    return fetch(BASE + url, { method: 'POST', body })
        .then(r => r.json())
        .then(d => { syncCsrf(d.csrf); if (!d.success) alert(d.message || 'Gagal.'); return d; })
        .catch(() => { alert('Koneksi gagal.'); return { success: false }; });
}
function fmtTgl(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
function fmtWaktu(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + ' ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
}
const labelById  = id => state.labels.find(l => String(l.id) === String(id));
const memberById = id => state.members.find(m => String(m.user_id) === String(id));

function dueBadge(card) {
    if (!card.due_date) return '';
    let cls = 'soon';
    if (+card.due_done) cls = 'ok';
    else if (new Date(card.due_date.replace(' ', 'T')) < new Date()) cls = 'over';
    return `<span class="kb-due ${cls}"><i class="bi bi-clock"></i>${fmtTgl(card.due_date)}</span>`;
}

// ── filter ────────────────────────────────────────────────────────────
function cardVisible(c) {
    if (filter.q && !(c.judul + ' ' + (c.deskripsi || '')).toLowerCase().includes(filter.q)) return false;
    if (filter.label && !(c.label_ids || []).map(String).includes(filter.label)) return false;
    if (filter.member && !(c.member_ids || []).map(String).includes(filter.member)) return false;
    if (filter.due === 'over'  && !(c.due_date && !+c.due_done && new Date(c.due_date.replace(' ', 'T')) < new Date())) return false;
    if (filter.due === 'none'  && c.due_date) return false;
    if (filter.due === 'week') {
        if (!c.due_date || +c.due_done) return false;
        const d = new Date(c.due_date.replace(' ', 'T')), now = new Date(), week = new Date(Date.now() + 7 * 864e5);
        if (d < now || d > week) return false;
    }
    return true;
}
function filterActive() { return !!(filter.q || filter.label || filter.member || filter.due); }

['fKeyword', 'fLabel', 'fMember', 'fDue'].forEach(id => {
    document.getElementById(id).addEventListener(id === 'fKeyword' ? 'input' : 'change', () => {
        filter = {
            q: document.getElementById('fKeyword').value.trim().toLowerCase(),
            label: document.getElementById('fLabel').value,
            member: document.getElementById('fMember').value,
            due: document.getElementById('fDue').value,
        };
        document.getElementById('fReset').classList.toggle('d-none', !filterActive());
        render();
    });
});
document.getElementById('fReset').addEventListener('click', () => {
    document.getElementById('fKeyword').value = ''; document.getElementById('fLabel').value = '';
    document.getElementById('fMember').value = ''; document.getElementById('fDue').value = '';
    filter = { q: '', label: '', member: '', due: '' };
    document.getElementById('fReset').classList.add('d-none');
    render();
});
function fillFilterOptions() {
    const fl = document.getElementById('fLabel'), fm = document.getElementById('fMember');
    const keepL = fl.value, keepM = fm.value;
    fl.innerHTML = '<option value="">Semua label</option>' + state.labels.map(l => `<option value="${l.id}">${esc(l.nama || 'Label')}</option>`).join('');
    fm.innerHTML = '<option value="">Semua assignee</option>' + state.members.map(m => `<option value="${m.user_id}">${esc(m.name)}</option>`).join('');
    fl.value = keepL; fm.value = keepM;
}

// ── render board ──────────────────────────────────────────────────────
function render() {
    fillFilterOptions();
    const wrap = document.getElementById('kbLists');
    wrap.innerHTML = '';
    state.lists.forEach(list => {
        const all = state.cards[list.id] || [];
        const cards = all.filter(cardVisible);
        const el = document.createElement('div');
        el.className = 'kb-list';
        el.dataset.listId = list.id;
        el.innerHTML = `
            <div class="kb-list-head">
                ${CAN_EDIT ? '<i class="bi bi-grip-vertical kb-list-grab"></i>' : ''}
                <div class="kb-list-title" ${CAN_EDIT ? 'contenteditable="true" spellcheck="false"' : ''} data-list-id="${list.id}">${esc(list.nama)}</div>
                <span class="kb-list-count">${filterActive() ? cards.length + '/' + all.length : all.length}</span>
                ${CAN_EDIT ? `<button class="btn btn-sm btn-link text-muted p-0 kb-list-archive" data-list-id="${list.id}" title="Arsipkan kolom"><i class="bi bi-archive" style="font-size:.75rem"></i></button>` : ''}
            </div>
            <div class="kb-cards" data-list-id="${list.id}"></div>
            ${CAN_EDIT ? `
            <button class="btn btn-sm w-100 text-start mt-2 kb-addcard-btn" data-list-id="${list.id}"><i class="bi bi-plus-lg me-1"></i>Tambah kartu</button>
            <div class="kb-composer d-none mt-2" data-list-id="${list.id}">
                <textarea class="form-control form-control-sm mb-2" rows="2" maxlength="255" placeholder="Judul kartu…"></textarea>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary kb-composer-save">Tambah</button>
                    <button class="btn btn-sm btn-outline-secondary kb-composer-cancel">Batal</button>
                </div>
            </div>` : ''}`;
        const cardsEl = el.querySelector('.kb-cards');
        cards.forEach(c => cardsEl.appendChild(cardEl(c)));
        wrap.appendChild(el);
    });
    initSortables();
}

function cardEl(c) {
    const el = document.createElement('div');
    el.className = 'kb-card';
    el.dataset.cardId = c.id;
    const strips = (c.label_ids || []).map(id => { const l = labelById(id); return l ? `<span class="kb-lchip" style="background:${esc(l.color)}" title="${esc(l.nama || '')}"></span>` : ''; }).join('');
    const badges = [];
    if (c.due_date) badges.push(dueBadge(c));
    if (+c.cl_total > 0) badges.push(`<span class="b ${+c.cl_done === +c.cl_total ? 'text-success' : ''}"><i class="bi bi-check2-square"></i>${c.cl_done}/${c.cl_total}</span>`);
    if (+c.comment_count > 0) badges.push(`<span class="b"><i class="bi bi-chat-left"></i>${c.comment_count}</span>`);
    if (+c.attachment_count > 0) badges.push(`<span class="b"><i class="bi bi-paperclip"></i>${c.attachment_count}</span>`);
    const avs = (c.member_ids || []).slice(0, 3).map(id => { const m = memberById(id); return m ? `<span class="kb-av sm" title="${esc(m.name)}">${esc(initials(m.name))}</span>` : ''; }).join('');
    el.innerHTML = `
        ${state.unread.map(String).includes(String(c.id)) ? '<span class="kb-unread"></span>' : ''}
        ${strips ? `<div class="kb-lstrip">${strips}</div>` : ''}
        <div style="padding-right:${avs ? '30px' : '0'}">${esc(c.judul)}</div>
        ${badges.length ? `<div class="kb-badges">${badges.join('')}</div>` : ''}
        ${avs ? `<div class="kb-cardavs">${avs}</div>` : ''}`;
    return el;
}

// ── SortableJS ────────────────────────────────────────────────────────
let sortables = [];
function initSortables() {
    sortables.forEach(s => s.destroy());
    sortables = [];
    if (!CAN_EDIT || filterActive()) return; // drag dimatikan saat filter aktif (urutan parsial menyesatkan)

    sortables.push(new Sortable(document.getElementById('kbLists'), {
        animation: 150, handle: '.kb-list-grab', direction: 'horizontal',
        onStart: () => dragging = true,
        onEnd: () => {
            dragging = false;
            const ids = [...document.querySelectorAll('#kbLists .kb-list')].map(e => e.dataset.listId);
            state.lists.sort((a, b) => ids.indexOf(String(a.id)) - ids.indexOf(String(b.id)));
            post(`/kanban/${BOARD_ID}/lists/reorder`, { list_ids: ids });
        },
    }));
    document.querySelectorAll('.kb-cards').forEach(el => {
        sortables.push(new Sortable(el, {
            animation: 150, group: 'kb-cards',
            onStart: () => dragging = true,
            onEnd: evt => {
                dragging = false;
                const cardId = evt.item.dataset.cardId;
                const toList = evt.to.dataset.listId, fromList = evt.from.dataset.listId;
                const payload = { list_id: toList, card_ids: [...evt.to.querySelectorAll('.kb-card')].map(e => e.dataset.cardId) };
                if (fromList !== toList) payload.source_card_ids = [...evt.from.querySelectorAll('.kb-card')].map(e => e.dataset.cardId);
                post(`/kanban/cards/${cardId}/move`, payload).then(d => { if (d.success) refreshState(true); });
            },
        }));
    });
}

// ── interaksi list & kartu ────────────────────────────────────────────
document.getElementById('kbLists').addEventListener('click', e => {
    const addBtn = e.target.closest('.kb-addcard-btn');
    if (addBtn) {
        addBtn.classList.add('d-none');
        const comp = document.querySelector(`.kb-composer[data-list-id="${addBtn.dataset.listId}"]`);
        comp.classList.remove('d-none'); comp.querySelector('textarea').focus();
        return;
    }
    if (e.target.closest('.kb-composer-cancel')) {
        const comp = e.target.closest('.kb-composer');
        comp.classList.add('d-none'); comp.previousElementSibling.classList.remove('d-none');
        return;
    }
    if (e.target.closest('.kb-composer-save')) {
        const comp = e.target.closest('.kb-composer');
        const ta = comp.querySelector('textarea');
        const judul = ta.value.trim();
        if (!judul) { ta.focus(); return; }
        post(`/kanban/lists/${comp.dataset.listId}/cards/create`, { judul }).then(d => { if (d.success) { ta.value = ''; refreshState(true); } });
        return;
    }
    const archBtn = e.target.closest('.kb-list-archive');
    if (archBtn) {
        if (!confirm('Arsipkan kolom ini? Kartu di dalamnya ikut tersembunyi.')) return;
        post(`/kanban/lists/${archBtn.dataset.listId}/archive`, {}).then(d => { if (d.success) refreshState(true); });
        return;
    }
    const card = e.target.closest('.kb-card');
    if (card && !dragging) openCard(card.dataset.cardId);
});
document.getElementById('kbLists').addEventListener('blur', e => {
    const t = e.target.closest('.kb-list-title[contenteditable]');
    if (!t) return;
    const nama = t.textContent.trim();
    const list = state.lists.find(l => String(l.id) === t.dataset.listId);
    if (!nama) { t.textContent = list ? list.nama : ''; return; }
    if (list && nama !== list.nama) post(`/kanban/lists/${t.dataset.listId}/rename`, { nama }).then(d => { if (d.success) list.nama = nama; });
}, true);
document.getElementById('kbLists').addEventListener('keydown', e => {
    if (e.target.closest('.kb-list-title[contenteditable]') && e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
});

const addListBtn = document.getElementById('addListBtn');
if (addListBtn) {
    const form = document.getElementById('addListForm');
    addListBtn.addEventListener('click', () => { addListBtn.classList.add('d-none'); form.classList.remove('d-none'); document.getElementById('addListName').focus(); });
    document.getElementById('addListCancel').addEventListener('click', () => { form.classList.add('d-none'); addListBtn.classList.remove('d-none'); });
    form.addEventListener('submit', e => {
        e.preventDefault();
        const nama = document.getElementById('addListName').value.trim();
        if (!nama) return;
        post(`/kanban/${BOARD_ID}/lists/create`, { nama }).then(d => {
            if (d.success) { document.getElementById('addListName').value = ''; form.classList.add('d-none'); addListBtn.classList.remove('d-none'); refreshState(true); }
        });
    });
}

// ── modal kartu penuh ─────────────────────────────────────────────────
const cardModalEl = document.getElementById('cardModal');
const cardModal   = new bootstrap.Modal(cardModalEl);
cardModalEl.addEventListener('shown.bs.modal',  () => modalOpen = true);
cardModalEl.addEventListener('hidden.bs.modal', () => { modalOpen = false; refreshState(true); });
let curCard = null, mentionSet = new Set();

function openCard(id) {
    fetch(`${BASE}/kanban/cards/${id}`).then(r => r.json()).then(d => {
        if (!d.success) { alert(d.message || 'Gagal memuat kartu.'); return; }
        curCard = d;
        mentionSet = new Set();
        const c = d.card;
        document.getElementById('cmId').value = c.id;
        document.getElementById('cmJudul').value = c.judul;
        document.getElementById('cmDesk').value = c.deskripsi || '';
        document.getElementById('cmDue').value = c.due_date ? c.due_date.substring(0, 10) : '';
        document.getElementById('cmDueDone').checked = !!+c.due_done;
        document.getElementById('cmMeta').textContent = `Dibuat oleh ${d.creator} · ${fmtWaktu(c.created_at)}`;
        renderCmLabels(d); renderCmMembers(d); renderCmChecklists(d);
        renderCmComments(d.comments); renderCmAttachments(d.attachments); renderCmActivity(d.activity);
        document.getElementById('cmCommentBody').value = '';
        document.getElementById('cmMentionInfo').textContent = '';
        renderMentionMenu();
        // badge unread kartu ini hilang (sudah dibaca server-side)
        state.unread = state.unread.filter(x => String(x) !== String(c.id));
        cardModal.show();
    });
}
function renderCmLabels(d) {
    const box = document.getElementById('cmLabels');
    if (!state.labels.length) { box.innerHTML = '<span class="small text-muted">Belum ada palet label. ' + (<?= $canManage ? 'true' : 'false' ?> ? 'Buka menu Label untuk membuat.' : '') + '</span>'; return; }
    box.innerHTML = state.labels.map(l => {
        const on = d.label_ids.map(String).includes(String(l.id));
        return `<span class="cm-label ${on ? 'on' : ''} ${CAN_EDIT ? '' : 'readonly'}" data-label-id="${l.id}" style="background:${esc(l.color)}">${esc(l.nama || 'Label')}</span>`;
    }).join('');
}
document.getElementById('cmLabels').addEventListener('click', e => {
    const el = e.target.closest('.cm-label');
    if (!el || !CAN_EDIT) return;
    post(`/kanban/cards/${curCard.card.id}/labels`, { label_id: el.dataset.labelId }).then(d => {
        if (!d.success) return;
        el.classList.toggle('on', d.attached);
        if (d.attached) curCard.label_ids.push(parseInt(el.dataset.labelId));
        else curCard.label_ids = curCard.label_ids.filter(x => String(x) !== el.dataset.labelId);
    });
});
function renderCmMembers(d) {
    const box = document.getElementById('cmMembers');
    box.innerHTML = state.members.map(m => {
        const on = d.member_ids.map(String).includes(String(m.user_id));
        return `<span class="cm-member ${on ? 'on' : ''} ${CAN_EDIT ? '' : 'readonly'}" data-user-id="${m.user_id}"><span class="kb-av sm">${esc(initials(m.name))}</span>${esc(m.name)}</span>`;
    }).join('');
}
document.getElementById('cmMembers').addEventListener('click', e => {
    const el = e.target.closest('.cm-member');
    if (!el || !CAN_EDIT) return;
    post(`/kanban/cards/${curCard.card.id}/members`, { user_id: el.dataset.userId }).then(d => { if (d.success) el.classList.toggle('on', d.attached); });
});
function renderCmChecklists(d) {
    const box = document.getElementById('cmChecklists');
    if (!d.checklists.length) { box.innerHTML = '<div class="small text-muted">Belum ada checklist.</div>'; return; }
    box.innerHTML = d.checklists.map(cl => {
        const total = cl.items.length, done = cl.items.filter(i => +i.is_done).length;
        const pct = total ? Math.round(done / total * 100) : 0;
        return `<div class="cm-checklist" data-cl-id="${cl.id}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-semibold">${esc(cl.judul)}</span><span class="small text-muted">${done}/${total}</span>
            </div>
            <div class="progress mb-2" style="height:6px"><div class="progress-bar ${pct === 100 ? 'bg-success' : ''}" style="width:${pct}%"></div></div>
            ${cl.items.map(i => `
                <div class="cm-cl-item ${+i.is_done ? 'done' : ''}">
                    <input type="checkbox" class="form-check-input cm-cl-toggle" data-item-id="${i.id}" ${+i.is_done ? 'checked' : ''} ${CAN_EDIT ? '' : 'disabled'}>
                    <label class="flex-grow-1">${esc(i.teks)}</label>
                    ${CAN_EDIT ? `<button class="btn btn-sm btn-link text-danger p-0 cm-cl-del" data-item-id="${i.id}"><i class="bi bi-x"></i></button>` : ''}
                </div>`).join('')}
            ${CAN_EDIT ? `
            <div class="d-flex gap-2 mt-2">
                <input type="text" class="form-control form-control-sm cm-cl-newitem" maxlength="255" placeholder="Tambah item…">
                <button class="btn btn-sm btn-outline-secondary cm-cl-additem" data-cl-id="${cl.id}">+</button>
            </div>` : ''}
        </div>`;
    }).join('');
}
document.getElementById('cmChecklists').addEventListener('click', e => {
    const add = e.target.closest('.cm-cl-additem');
    if (add) {
        const inp = add.parentElement.querySelector('.cm-cl-newitem');
        const teks = inp.value.trim();
        if (!teks) { inp.focus(); return; }
        post(`/kanban/checklists/${add.dataset.clId}/items/create`, { teks }).then(d => { if (d.success) reloadCard(); });
        return;
    }
    const del = e.target.closest('.cm-cl-del');
    if (del) { post(`/kanban/checklist-items/${del.dataset.itemId}/delete`, {}).then(d => { if (d.success) reloadCard(); }); }
});
document.getElementById('cmChecklists').addEventListener('change', e => {
    const t = e.target.closest('.cm-cl-toggle');
    if (t) post(`/kanban/checklist-items/${t.dataset.itemId}/toggle`, {}).then(d => { if (d.success) reloadCard(); });
});
document.getElementById('cmChecklists').addEventListener('keydown', e => {
    if (e.target.closest('.cm-cl-newitem') && e.key === 'Enter') { e.preventDefault(); e.target.parentElement.querySelector('.cm-cl-additem').click(); }
});
document.getElementById('cmAddChecklist')?.addEventListener('click', () => {
    const judul = prompt('Judul checklist:', 'Checklist');
    if (judul === null) return;
    post(`/kanban/cards/${curCard.card.id}/checklists/create`, { judul: judul || 'Checklist' }).then(d => { if (d.success) reloadCard(); });
});
function renderCmComments(comments) {
    const box = document.getElementById('cmComments');
    box.innerHTML = comments.length ? comments.map(cm => `
        <div class="cm-comment">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small"><span class="kb-av sm me-1">${esc(initials(cm.user_name))}</span>${esc(cm.user_name)}</span>
                <span class="text-muted" style="font-size:.7rem">${fmtWaktu(cm.created_at)}
                ${+cm.user_id === MY_ID ? `<button class="btn btn-sm btn-link text-danger p-0 ms-1 cm-comment-del" data-id="${cm.id}"><i class="bi bi-trash" style="font-size:.7rem"></i></button>` : ''}</span>
            </div>
            <div>${esc(cm.body).replace(/\n/g, '<br>')}</div>
        </div>`).join('') : '<div class="small text-muted">Belum ada komentar.</div>';
}
document.getElementById('cmComments').addEventListener('click', e => {
    const del = e.target.closest('.cm-comment-del');
    if (del && confirm('Hapus komentar ini?')) {
        post(`/kanban/comments/${del.dataset.id}/delete`, {}).then(d => { if (d.success) renderCmComments(d.comments); });
    }
});
function renderMentionMenu() {
    document.getElementById('cmMentionMenu').innerHTML = state.members
        .filter(m => +m.user_id !== MY_ID)
        .map(m => `<li><button class="dropdown-item small cm-mention-pick" data-user-id="${m.user_id}" data-name="${esc(m.name)}">${esc(m.name)}</button></li>`)
        .join('') || '<li><span class="dropdown-item small text-muted">Tidak ada anggota lain</span></li>';
}
document.getElementById('cmMentionMenu').addEventListener('click', e => {
    const p = e.target.closest('.cm-mention-pick');
    if (!p) return;
    mentionSet.add(p.dataset.userId);
    const ta = document.getElementById('cmCommentBody');
    ta.value = (ta.value ? ta.value + ' ' : '') + '@' + p.dataset.name + ' ';
    ta.focus();
    document.getElementById('cmMentionInfo').textContent = 'Menyebut: ' + [...mentionSet].map(id => (memberById(id) || {}).name).filter(Boolean).join(', ');
});
document.getElementById('cmCommentSend').addEventListener('click', () => {
    const body = document.getElementById('cmCommentBody').value.trim();
    if (!body) return;
    post(`/kanban/cards/${curCard.card.id}/comments/create`, { body, mention_ids: [...mentionSet] }).then(d => {
        if (d.success) {
            document.getElementById('cmCommentBody').value = '';
            mentionSet = new Set();
            document.getElementById('cmMentionInfo').textContent = '';
            renderCmComments(d.comments);
        }
    });
});
function renderCmAttachments(atts) {
    const box = document.getElementById('cmAttachments');
    box.innerHTML = atts.length ? atts.map(a => `
        <div class="cm-att">
            <i class="bi bi-file-earmark"></i>
            <a class="flex-grow-1 text-truncate text-decoration-none" href="${BASE}/kanban/attachments/${a.id}/download">${esc(a.filename)}</a>
            <span class="text-muted" style="font-size:.7rem">${(a.size / 1048576).toFixed(1)} MB · ${esc(a.uploader_name)}</span>
            ${(+a.uploaded_by === MY_ID || <?= $canManage ? 'true' : 'false' ?>) ? `<button class="btn btn-sm btn-link text-danger p-0 cm-att-del" data-id="${a.id}"><i class="bi bi-trash" style="font-size:.75rem"></i></button>` : ''}
        </div>`).join('') : '<div class="small text-muted">Belum ada lampiran.</div>';
}
document.getElementById('cmAttachments').addEventListener('click', e => {
    const del = e.target.closest('.cm-att-del');
    if (del && confirm('Hapus lampiran ini?')) {
        post(`/kanban/attachments/${del.dataset.id}/delete`, {}).then(d => { if (d.success) renderCmAttachments(d.attachments); });
    }
});
document.getElementById('cmFile')?.addEventListener('change', e => {
    const f = e.target.files[0];
    if (!f) return;
    if (f.size > 10 * 1048576) { alert('Maksimal 10 MB.'); e.target.value = ''; return; }
    const fd = new FormData();
    fd.append('file', f);
    post(`/kanban/cards/${curCard.card.id}/attachments/upload`, fd, true).then(d => { if (d.success) renderCmAttachments(d.attachments); });
    e.target.value = '';
});
function renderCmActivity(acts) {
    document.getElementById('cmActivity').innerHTML = acts.length
        ? acts.map(a => `<div class="cm-act"><b>${esc(a.user_name)}</b> · ${esc(a.action)} — ${esc(a.detail || '')} <span style="font-size:.68rem">(${fmtWaktu(a.created_at)})</span></div>`).join('')
        : '<div class="small text-muted">Belum ada aktivitas.</div>';
}
function reloadCard() {
    if (!curCard) return;
    fetch(`${BASE}/kanban/cards/${curCard.card.id}`).then(r => r.json()).then(d => {
        if (!d.success) return;
        curCard = d;
        renderCmChecklists(d); renderCmLabels(d); renderCmMembers(d);
        renderCmComments(d.comments); renderCmAttachments(d.attachments); renderCmActivity(d.activity);
    });
}
document.getElementById('cmSave')?.addEventListener('click', () => {
    post(`/kanban/cards/${curCard.card.id}/update`, {
        judul: document.getElementById('cmJudul').value,
        deskripsi: document.getElementById('cmDesk').value,
        due_date: document.getElementById('cmDue').value,
        due_done: document.getElementById('cmDueDone').checked ? '1' : '0',
    }).then(d => { if (d.success) { refreshState(true); } });
});
document.getElementById('cmArchive')?.addEventListener('click', () => {
    if (!confirm('Arsipkan kartu ini?')) return;
    post(`/kanban/cards/${curCard.card.id}/archive`, {}).then(d => { if (d.success) { cardModal.hide(); refreshState(true); } });
});

// ── modal anggota & label (owner) ─────────────────────────────────────
document.getElementById('mmAdd')?.addEventListener('click', () => {
    const uid = document.getElementById('mmUser').value;
    if (!uid) return;
    post(`/kanban/${BOARD_ID}/members/add`, { user_id: uid, role: document.getElementById('mmRole').value })
        .then(d => { if (d.success) location.reload(); });
});
document.getElementById('mmList')?.addEventListener('change', e => {
    const s = e.target.closest('.mm-role');
    if (s) post(`/kanban/${BOARD_ID}/members/${s.dataset.mid}/role`, { role: s.value });
});
document.getElementById('mmList')?.addEventListener('click', e => {
    const r = e.target.closest('.mm-remove');
    if (r && confirm('Keluarkan anggota ini dari board?')) {
        post(`/kanban/${BOARD_ID}/members/${r.dataset.mid}/remove`, {}).then(d => { if (d.success) location.reload(); });
    }
});

const LABEL_COLORS = ['#e8415a', '#f97316', '#f59e0b', '#10b981', '#22d3ee', '#3b82f6', '#a855f7', '#64748b'];
function lmRow(l) {
    return `<div class="d-flex gap-2 align-items-center mb-2 lm-row" data-id="${l.id || ''}">
        <input type="color" class="form-control form-control-color form-control-sm lm-color" value="${l.color || LABEL_COLORS[Math.floor(Math.random() * 8)]}" style="width:44px">
        <input type="text" class="form-control form-control-sm lm-nama" maxlength="60" placeholder="Nama label (opsional)" value="${esc(l.nama || '')}">
        <button class="btn btn-sm btn-link text-danger p-0 lm-del"><i class="bi bi-trash"></i></button>
    </div>`;
}
function lmRender() {
    document.getElementById('lmRows').innerHTML = state.labels.map(lmRow).join('') || '<div class="small text-muted mb-2">Belum ada label — tambah di bawah.</div>';
}
document.getElementById('labelsModal')?.addEventListener('show.bs.modal', lmRender);
document.getElementById('lmAdd')?.addEventListener('click', () => {
    document.getElementById('lmRows').insertAdjacentHTML('beforeend', lmRow({}));
});
document.getElementById('lmRows')?.addEventListener('click', e => {
    const d = e.target.closest('.lm-del');
    if (!d) return;
    const row = d.closest('.lm-row');
    if (row.dataset.id) { row.classList.add('d-none'); row.dataset.del = '1'; }
    else row.remove();
});
document.getElementById('lmSave')?.addEventListener('click', () => {
    const rows = [...document.querySelectorAll('#lmRows .lm-row')].map(r => ({
        id: r.dataset.id ? parseInt(r.dataset.id) : 0,
        nama: r.querySelector('.lm-nama').value,
        color: r.querySelector('.lm-color').value,
        del: r.dataset.del === '1',
    }));
    post(`/kanban/${BOARD_ID}/labels/save`, { labels: JSON.stringify(rows) }).then(d => {
        if (d.success) {
            state.labels = d.labels;
            bootstrap.Modal.getInstance(document.getElementById('labelsModal')).hide();
            render();
        }
    });
});

// ── setelan board ─────────────────────────────────────────────────────
document.getElementById('bsSave')?.addEventListener('click', () => {
    post(`/kanban/${BOARD_ID}/update`, {
        nama: document.getElementById('bsNama').value,
        deskripsi: document.getElementById('bsDesk').value,
        color: document.querySelector('input[name="bs_color"]:checked')?.value || '',
    }).then(d => { if (d.success) location.reload(); });
});

// ── polling sync ──────────────────────────────────────────────────────
function refreshState(force) {
    if (!force && (dragging || modalOpen)) return;
    fetch(`${BASE}/kanban/${BOARD_ID}/state`).then(r => r.json()).then(d => {
        if (!d.success) return;
        if (force || d.rev !== state.rev) {
            state.rev = d.rev; state.lists = d.lists; state.cards = d.cards;
            state.labels = d.labels; state.members = d.members; state.unread = d.unread;
            if (!dragging && !modalOpen) render();
        }
    }).catch(() => {});
}
setInterval(() => refreshState(false), 12000);
window.addEventListener('focus', () => refreshState(false));

render();
})();
</script>
<?= $this->endSection() ?>
