<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="<?= base_url('kanban') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <span class="d-inline-block rounded" style="width:14px;height:14px;background:<?= esc($board['color'] ?: '#e8415a') ?>"></span>
    <div class="flex-grow-1">
        <h5 class="fw-bold mb-0"><?= esc($board['nama']) ?></h5>
        <?php if ($board['deskripsi']): ?><small class="text-muted"><?= esc($board['deskripsi']) ?></small><?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted"><i class="bi bi-people me-1"></i><?= count($members) ?> anggota · peran: <b><?= esc(ucfirst($role)) ?></b></span>
        <?php if ($canManage): ?>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#boardSettingModal"><i class="bi bi-pencil me-2"></i>Ubah board</button></li>
                <li>
                    <form method="POST" action="<?= base_url('kanban/' . $board['id'] . '/archive') ?>" onsubmit="return confirm('<?= $board['is_archived'] ? 'Pulihkan' : 'Arsipkan' ?> board ini?')">
                        <?= csrf_field() ?>
                        <button class="dropdown-item"><i class="bi bi-archive me-2"></i><?= $board['is_archived'] ? 'Pulihkan' : 'Arsipkan' ?> board</button>
                    </form>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Papan: kolom horizontal -->
<div class="kb-wrap" id="kbWrap">
    <div class="kb-lists" id="kbLists"><!-- dirender JS --></div>
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

<!-- Modal kartu -->
<div class="modal fade" id="cardModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title fw-semibold"><i class="bi bi-card-text me-1"></i>Detail Kartu</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="cmId">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
            <input type="text" id="cmJudul" class="form-control" maxlength="255" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Deskripsi</label>
            <textarea id="cmDesk" class="form-control" rows="4" placeholder="(opsional)" <?= $canEdit ? '' : 'readonly' ?>></textarea>
        </div>
        <div class="row g-2 align-items-end mb-2">
            <div class="col-7">
                <label class="form-label small fw-semibold">Tenggat (due date)</label>
                <input type="date" id="cmDue" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?>>
            </div>
            <div class="col-5">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="cmDueDone" <?= $canEdit ? '' : 'disabled' ?>>
                    <label class="form-check-label small" for="cmDueDone">Selesai</label>
                </div>
            </div>
        </div>
        <div class="small text-muted" id="cmMeta"></div>
    </div>
    <div class="modal-footer py-2 <?= $canEdit ? '' : 'd-none' ?>">
        <button type="button" class="btn btn-sm btn-outline-danger me-auto" id="cmArchive"><i class="bi bi-archive me-1"></i>Arsipkan</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-sm btn-primary" id="cmSave"><i class="bi bi-save me-1"></i>Simpan</button>
    </div>
</div></div>
</div>

<?php if ($canManage): ?>
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
/* Board: kolom horizontal scroll — lebar tetap 280px (gotcha iPad portrait) */
.kb-wrap { display:flex; gap:12px; align-items:flex-start; overflow-x:auto; padding-bottom:14px; min-height:60vh; }
.kb-lists { display:flex; gap:12px; align-items:flex-start; }
.kb-list { width:280px; flex:none; background:var(--card-bg, rgba(14,26,42,.92)); border:1px solid var(--card-border, rgba(255,255,255,.06));
    border-radius:.9rem; padding:10px; }
.kb-list-head { display:flex; align-items:center; gap:6px; padding:2px 4px 8px; }
.kb-list-title { font-weight:600; font-size:.9rem; flex:1; min-width:0; cursor:text; border-radius:.35rem; padding:2px 6px; }
.kb-list-title:focus { outline:1px solid rgba(232,65,90,.5); background:rgba(255,255,255,.05); }
.kb-list-count { font-size:.72rem; color:var(--txt-muted, rgba(180,210,255,.42)); }
.kb-cards { display:flex; flex-direction:column; gap:8px; min-height:8px; }
.kb-card { background:var(--c-inner-bg, rgba(255,255,255,.04)); border:1px solid var(--c-inner-border, rgba(255,255,255,.07));
    border-radius:.6rem; padding:8px 10px; cursor:pointer; font-size:.86rem; }
.kb-card:hover { border-color:rgba(232,65,90,.35); }
.kb-card .kb-due { font-size:.7rem; padding:1px 7px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; margin-top:6px; }
.kb-due.ok   { background:rgba(16,185,129,.2);  color:#6ee7b7; }
.kb-due.soon { background:rgba(249,115,22,.2);  color:#fdba74; }
.kb-due.over { background:rgba(239,68,68,.2);   color:#fca5a5; }
.kb-addcard-btn, .kb-addlist-btn { color:var(--txt-muted, rgba(180,210,255,.5)); background:transparent; border:1px dashed rgba(255,255,255,.15); }
.kb-addcard-btn:hover, .kb-addlist-btn:hover { color:#fff; border-color:rgba(232,65,90,.5); }
.kb-addlist { width:280px; flex:none; }
.kb-composer textarea { font-size:.86rem; }
.sortable-ghost { opacity:.35; }
.sortable-drag { transform:rotate(2deg); }
.kb-list-grab { cursor:grab; color:var(--txt-muted, rgba(180,210,255,.35)); }
.kb-color-opt span { display:block; width:34px; height:34px; border-radius:9px; cursor:pointer; opacity:.55; border:2px solid transparent; }
.kb-color-opt input:checked + span { opacity:1; border-color:#fff; box-shadow:0 0 0 2px rgba(255,255,255,.25); }
</style>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/sortable.min.js') ?>"></script>
<script>
(function () {
'use strict';
const BOARD_ID = <?= (int) $board['id'] ?>;
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
const BASE     = '<?= rtrim(base_url(), '/') ?>';
const csrfName = '<?= csrf_token() ?>';
let   csrfHash = '<?= csrf_hash() ?>';

let state = {
    rev:   <?= json_encode($board['updated_at']) ?>,
    lists: <?= json_encode(array_values($lists)) ?>,
    cards: <?= json_encode((object) $cards) ?>,
};
let dragging = false, modalOpen = false;

// ── util ──────────────────────────────────────────────────────────────
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function syncCsrf(hash) {
    if (!hash) return;
    csrfHash = hash;
    document.querySelectorAll(`input[name="${csrfName}"]`).forEach(i => { i.value = hash; });
}
function post(url, data) {
    const body = new FormData();
    Object.entries(data || {}).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach(x => body.append(k + '[]', x));
        else body.append(k, v);
    });
    body.append(csrfName, csrfHash);
    return fetch(BASE + url, { method: 'POST', body })
        .then(r => r.json())
        .then(d => { syncCsrf(d.csrf); if (!d.success) alert(d.message || 'Gagal.'); return d; })
        .catch(() => { alert('Koneksi gagal.'); return { success: false }; });
}

function dueBadge(card) {
    if (!card.due_date) return '';
    const d = new Date(card.due_date.replace(' ', 'T'));
    const label = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    let cls = 'soon';
    if (+card.due_done) cls = 'ok';
    else if (d < new Date()) cls = 'over';
    return `<span class="kb-due ${cls}"><i class="bi bi-clock"></i>${label}</span>`;
}

// ── render (satu jalur — dipakai load awal & polling) ─────────────────
function render() {
    const wrap = document.getElementById('kbLists');
    wrap.innerHTML = '';
    state.lists.forEach(list => {
        const cards = state.cards[list.id] || [];
        const el = document.createElement('div');
        el.className = 'kb-list';
        el.dataset.listId = list.id;
        el.innerHTML = `
            <div class="kb-list-head">
                ${CAN_EDIT ? '<i class="bi bi-grip-vertical kb-list-grab"></i>' : ''}
                <div class="kb-list-title" ${CAN_EDIT ? 'contenteditable="true" spellcheck="false"' : ''} data-list-id="${list.id}">${esc(list.nama)}</div>
                <span class="kb-list-count">${cards.length}</span>
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
    el.innerHTML = `<div>${esc(c.judul)}</div>${dueBadge(c)}`;
    return el;
}

// ── SortableJS ────────────────────────────────────────────────────────
let sortables = [];
function initSortables() {
    sortables.forEach(s => s.destroy());
    sortables = [];
    if (!CAN_EDIT) return;

    // kolom (horizontal)
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

    // kartu (dalam & antar kolom)
    document.querySelectorAll('.kb-cards').forEach(el => {
        sortables.push(new Sortable(el, {
            animation: 150, group: 'kb-cards',
            onStart: () => dragging = true,
            onEnd: evt => {
                dragging = false;
                const cardId   = evt.item.dataset.cardId;
                const toList   = evt.to.dataset.listId;
                const fromList = evt.from.dataset.listId;
                const targetIds = [...evt.to.querySelectorAll('.kb-card')].map(e => e.dataset.cardId);
                const payload = { list_id: toList, card_ids: targetIds };
                if (fromList !== toList) {
                    payload.source_card_ids = [...evt.from.querySelectorAll('.kb-card')].map(e => e.dataset.cardId);
                }
                post(`/kanban/cards/${cardId}/move`, payload).then(d => { if (d.success) refreshState(true); });
            },
        }));
    });
}

// ── interaksi list & kartu (event delegation) ─────────────────────────
document.getElementById('kbLists').addEventListener('click', e => {
    const addBtn = e.target.closest('.kb-addcard-btn');
    if (addBtn) {
        const lid = addBtn.dataset.listId;
        addBtn.classList.add('d-none');
        const comp = document.querySelector(`.kb-composer[data-list-id="${lid}"]`);
        comp.classList.remove('d-none');
        comp.querySelector('textarea').focus();
        return;
    }
    if (e.target.closest('.kb-composer-cancel')) {
        const comp = e.target.closest('.kb-composer');
        comp.classList.add('d-none');
        comp.previousElementSibling.classList.remove('d-none');
        return;
    }
    if (e.target.closest('.kb-composer-save')) {
        const comp = e.target.closest('.kb-composer');
        const ta = comp.querySelector('textarea');
        const judul = ta.value.trim();
        if (!judul) { ta.focus(); return; }
        post(`/kanban/lists/${comp.dataset.listId}/cards/create`, { judul }).then(d => {
            if (d.success) { ta.value = ''; refreshState(true); }
        });
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

// rename list (contenteditable blur / Enter)
document.getElementById('kbLists').addEventListener('blur', e => {
    const t = e.target.closest('.kb-list-title[contenteditable]');
    if (!t) return;
    const nama = t.textContent.trim();
    const list = state.lists.find(l => String(l.id) === t.dataset.listId);
    if (!nama) { t.textContent = list ? list.nama : ''; return; }
    if (list && nama !== list.nama) {
        post(`/kanban/lists/${t.dataset.listId}/rename`, { nama }).then(d => { if (d.success) list.nama = nama; });
    }
}, true);
document.getElementById('kbLists').addEventListener('keydown', e => {
    if (e.target.closest('.kb-list-title[contenteditable]') && e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
});

// tambah kolom
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
            if (d.success) {
                document.getElementById('addListName').value = '';
                form.classList.add('d-none');
                addListBtn.classList.remove('d-none');
                refreshState(true);
            }
        });
    });
}

// ── modal kartu ───────────────────────────────────────────────────────
const cardModalEl = document.getElementById('cardModal');
const cardModal   = new bootstrap.Modal(cardModalEl);
cardModalEl.addEventListener('shown.bs.modal',  () => modalOpen = true);
cardModalEl.addEventListener('hidden.bs.modal', () => modalOpen = false);

function openCard(id) {
    fetch(`${BASE}/kanban/cards/${id}`).then(r => r.json()).then(d => {
        if (!d.success) { alert(d.message || 'Gagal memuat kartu.'); return; }
        const c = d.card;
        document.getElementById('cmId').value = c.id;
        document.getElementById('cmJudul').value = c.judul;
        document.getElementById('cmDesk').value = c.deskripsi || '';
        document.getElementById('cmDue').value = c.due_date ? c.due_date.substring(0, 10) : '';
        document.getElementById('cmDueDone').checked = !!+c.due_done;
        document.getElementById('cmMeta').textContent = `Dibuat oleh ${d.creator} · ${new Date(c.created_at.replace(' ', 'T')).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`;
        cardModal.show();
    });
}
document.getElementById('cmSave')?.addEventListener('click', () => {
    const id = document.getElementById('cmId').value;
    post(`/kanban/cards/${id}/update`, {
        judul:     document.getElementById('cmJudul').value,
        deskripsi: document.getElementById('cmDesk').value,
        due_date:  document.getElementById('cmDue').value,
        due_done:  document.getElementById('cmDueDone').checked ? '1' : '0',
    }).then(d => { if (d.success) { cardModal.hide(); refreshState(true); } });
});
document.getElementById('cmArchive')?.addEventListener('click', () => {
    if (!confirm('Arsipkan kartu ini?')) return;
    const id = document.getElementById('cmId').value;
    post(`/kanban/cards/${id}/archive`, {}).then(d => { if (d.success) { cardModal.hide(); refreshState(true); } });
});

// ── setelan board ─────────────────────────────────────────────────────
document.getElementById('bsSave')?.addEventListener('click', () => {
    post(`/kanban/${BOARD_ID}/update`, {
        nama:      document.getElementById('bsNama').value,
        deskripsi: document.getElementById('bsDesk').value,
        color:     document.querySelector('input[name="bs_color"]:checked')?.value || '',
    }).then(d => { if (d.success) location.reload(); });
});

// ── polling sync (§5.1): ~12 dtk + saat window focus ─────────────────
function refreshState(force) {
    if (!force && (dragging || modalOpen)) return;
    fetch(`${BASE}/kanban/${BOARD_ID}/state`).then(r => r.json()).then(d => {
        if (!d.success) return;
        if (force || d.rev !== state.rev) {
            if (dragging || modalOpen) { state.rev = d.rev; state.lists = d.lists; state.cards = d.cards; return; }
            state.rev = d.rev; state.lists = d.lists; state.cards = d.cards;
            render();
        }
    }).catch(() => {});
}
setInterval(() => refreshState(false), 12000);
window.addEventListener('focus', () => refreshState(false));

render();
})();
</script>
<?= $this->endSection() ?>
