<?php

namespace App\Controllers;

use App\Models\KanbanBoardModel;
use App\Models\KanbanBoardMemberModel;
use App\Models\KanbanListModel;
use App\Models\KanbanCardModel;
use App\Models\KanbanLabelModel;
use App\Models\NotificationModel;
use App\Libraries\KanbanAccess;
use App\Libraries\KanbanFeed;
use App\Libraries\Notify;
use App\Libraries\ActivityLog;

/**
 * Boards/Kanban — board-level (KANBAN_DESIGN.md §4).
 * Fase 1: daftar board, render board, CRUD board, list CRUD + reorder,
 * state (polling sync). Anggota & label = Fase 2.
 */
class Kanban extends BaseController
{
    private function uid(): int
    {
        return (int) $this->currentUser()['id'];
    }

    /** Peran efektif user pada board (admin bypass). */
    private function role(int $boardId): ?string
    {
        return KanbanAccess::role($boardId, $this->uid(), $this->isAdmin());
    }

    /** Respons JSON standar + token CSRF baru (regenerate=true). */
    private function jsonOk(array $extra = [])
    {
        return $this->response->setJSON(['success' => true, 'csrf' => csrf_hash()] + $extra);
    }

    private function jsonErr(string $msg, int $status = 400)
    {
        return $this->response->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $msg, 'csrf' => csrf_hash()]);
    }

    // ── Daftar board ─────────────────────────────────────────────────────
    public function index()
    {
        if (! $this->canViewMenu('kanban')) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $arsip  = $this->request->getGet('arsip') === '1';
        $boards = (new KanbanBoardModel())->boardsForUser($this->uid(), $this->isAdmin(), $arsip);

        return view('kanban/index', [
            'user'   => $this->currentUser(),
            'boards' => $boards,
            'arsip'  => $arsip,
        ]);
    }

    // ── Buat board ───────────────────────────────────────────────────────
    public function create()
    {
        if (! $this->canViewMenu('kanban')) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return redirect()->back()->with('error', 'Nama board wajib diisi.');

        $db = db_connect();
        $db->transStart();

        $boardModel = new KanbanBoardModel();
        $id = $boardModel->insert([
            'nama'      => $nama,
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'color'     => $this->request->getPost('color') ?: null,
            'owner_id'  => $this->uid(),
        ]);

        // Owner juga tercatat sebagai anggota (role=owner) sesuai spec §2.2
        (new KanbanBoardMemberModel())->insert([
            'board_id' => $id, 'user_id' => $this->uid(), 'role' => 'owner',
        ]);

        // Kolom default agar board langsung bisa dipakai
        $listModel = new KanbanListModel();
        foreach (['To Do', 'Doing', 'Done'] as $i => $n) {
            $listModel->insert(['board_id' => $id, 'nama' => $n, 'position' => $i]);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal membuat board.');
        }

        ActivityLog::write('create', 'kanban_board', (string) $id, $nama);
        return redirect()->to('kanban/' . $id)->with('success', 'Board dibuat.');
    }

    // ── Render board ─────────────────────────────────────────────────────
    public function board(int $id)
    {
        if (! $this->canViewMenu('kanban')) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $board = (new KanbanBoardModel())->find($id);
        if (! $board) return redirect()->to('kanban')->with('error', 'Board tidak ditemukan.');

        $role = $this->role($id);
        if ($role === null) return redirect()->to('kanban')->with('error', 'Anda bukan anggota board ini.');

        // Kandidat anggota: user aktif, dept non-outsource, belum jadi anggota
        $memberIds = array_column((new KanbanBoardMemberModel())->where('board_id', $id)->findAll(), 'user_id');
        $userPick  = db_connect()->table('users u')
            ->select('u.id, u.name, u.email')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('u.is_active', 1)
            ->groupStart()->where('d.is_outsource', 0)->orWhere('u.department_id IS NULL', null, false)->groupEnd()
            ->orderBy('u.name')->get()->getResultArray();
        $userPick = array_values(array_filter($userPick, fn ($u) => ! in_array($u['id'], $memberIds)));

        return view('kanban/board', [
            'user'    => $this->currentUser(),
            'board'   => $board,
            'role'    => $role,
            'canEdit' => KanbanAccess::canEdit($role),
            'canManage' => KanbanAccess::canManage($role),
            'lists'   => (new KanbanListModel())->forBoard($id),
            'cards'   => (new KanbanCardModel())->byListForBoard($id),
            'members' => (new KanbanBoardMemberModel())->membersWithName($id),
            'labels'  => (new KanbanLabelModel())->forBoard($id),
            'unreadCardIds' => (new NotificationModel())->unreadCardIds($this->uid()),
            'userPick' => $userPick,
        ]);
    }

    // ── Anggota: tambah / ubah peran / keluarkan (owner only) ───────────
    public function addMember(int $boardId)
    {
        if (! KanbanAccess::canManage($this->role($boardId))) return $this->jsonErr('Hanya owner yang bisa kelola anggota.', 403);

        $userId = (int) $this->request->getPost('user_id');
        $roleIn = $this->request->getPost('role');
        if (! in_array($roleIn, ['editor', 'viewer'], true)) $roleIn = 'editor';

        $u = db_connect()->table('users')->select('id, name')->where('id', $userId)->where('is_active', 1)->get()->getRowArray();
        if (! $u) return $this->jsonErr('User tidak ditemukan.');

        $mm = new KanbanBoardMemberModel();
        if ($mm->where('board_id', $boardId)->where('user_id', $userId)->first()) {
            return $this->jsonErr('Sudah menjadi anggota.');
        }
        $mm->insert(['board_id' => $boardId, 'user_id' => $userId, 'role' => $roleIn]);

        $board = (new KanbanBoardModel())->find($boardId);
        KanbanFeed::log($boardId, null, $this->uid(), 'add_member', $u['name'] . ' (' . $roleIn . ')');
        Notify::send([$userId], $this->uid(), 'kanban', 'assigned',
            'Anda ditambahkan ke board "' . $board['nama'] . '"',
            'Peran: ' . $roleIn, 'kanban_board', $boardId, 'kanban/' . $boardId);
        ActivityLog::write('update', 'kanban_board', (string) $boardId, 'Tambah anggota — ' . $u['name']);
        return $this->jsonOk();
    }

    public function setMemberRole(int $boardId, int $memberId)
    {
        if (! KanbanAccess::canManage($this->role($boardId))) return $this->jsonErr('Hanya owner.', 403);

        $roleIn = $this->request->getPost('role');
        if (! in_array($roleIn, ['editor', 'viewer'], true)) return $this->jsonErr('Peran tidak valid.');

        $mm = new KanbanBoardMemberModel();
        $m = $mm->find($memberId);
        if (! $m || (int) $m['board_id'] !== $boardId) return $this->jsonErr('Anggota tidak ditemukan.', 404);
        if ($m['role'] === 'owner') return $this->jsonErr('Peran owner tidak bisa diubah.');

        $mm->update($memberId, ['role' => $roleIn]);
        ActivityLog::write('update', 'kanban_board', (string) $boardId, 'Ubah peran anggota #' . $m['user_id'] . ' → ' . $roleIn);
        return $this->jsonOk();
    }

    public function removeMember(int $boardId, int $memberId)
    {
        if (! KanbanAccess::canManage($this->role($boardId))) return $this->jsonErr('Hanya owner.', 403);

        $mm = new KanbanBoardMemberModel();
        $m = $mm->find($memberId);
        if (! $m || (int) $m['board_id'] !== $boardId) return $this->jsonErr('Anggota tidak ditemukan.', 404);
        if ($m['role'] === 'owner') return $this->jsonErr('Owner tidak bisa dikeluarkan.');

        $mm->delete($memberId);
        // lepaskan juga dari assignee kartu board ini
        db_connect()->query(
            'DELETE cm FROM kanban_card_members cm JOIN kanban_cards c ON c.id = cm.card_id WHERE c.board_id = ? AND cm.user_id = ?',
            [$boardId, $m['user_id']]
        );
        ActivityLog::write('update', 'kanban_board', (string) $boardId, 'Keluarkan anggota #' . $m['user_id']);
        return $this->jsonOk();
    }

    // ── Palet label (owner only): simpan batch [{id?,nama,color,del?}] ──
    public function saveLabels(int $boardId)
    {
        if (! KanbanAccess::canManage($this->role($boardId))) return $this->jsonErr('Hanya owner.', 403);

        $rows = json_decode((string) $this->request->getPost('labels'), true);
        if (! is_array($rows)) return $this->jsonErr('Payload label tidak valid.');

        $lm = new KanbanLabelModel();
        $db = db_connect();
        $db->transStart();
        foreach ($rows as $r) {
            $id    = (int) ($r['id'] ?? 0);
            $del   = ! empty($r['del']);
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', $r['color'] ?? '') ? $r['color'] : '#64748b';
            $nama  = trim((string) ($r['nama'] ?? '')) ?: null;

            if ($id && $del) {
                $own = $lm->find($id);
                if ($own && (int) $own['board_id'] === $boardId) $lm->delete($id); // card_labels ikut via FK cascade
            } elseif ($id) {
                $own = $lm->find($id);
                if ($own && (int) $own['board_id'] === $boardId) $lm->update($id, ['nama' => $nama, 'color' => $color]);
            } elseif (! $del) {
                $lm->insert(['board_id' => $boardId, 'nama' => $nama, 'color' => $color]);
            }
        }
        $db->transComplete();
        if ($db->transStatus() === false) return $this->jsonErr('Gagal menyimpan label.');

        (new KanbanBoardModel())->touch($boardId);
        ActivityLog::write('update', 'kanban_board', (string) $boardId, 'Kelola palet label');
        return $this->jsonOk(['labels' => $lm->forBoard($boardId)]);
    }

    // ── Halaman arsip per board (list & kartu terarsip; restore/hapus) ──
    public function arsip(int $id)
    {
        $role = $this->role($id);
        if ($role === null) return redirect()->to('kanban')->with('error', 'Bukan anggota board.');

        $board = (new KanbanBoardModel())->find($id);
        if (! $board) return redirect()->to('kanban')->with('error', 'Board tidak ditemukan.');

        $db = db_connect();
        return view('kanban/arsip', [
            'user'  => $this->currentUser(),
            'board' => $board,
            'role'  => $role,
            'canEdit'   => KanbanAccess::canEdit($role),
            'canManage' => KanbanAccess::canManage($role),
            'lists' => (new KanbanListModel())->forBoard($id, true),
            'cards' => $db->table('kanban_cards c')
                ->select('c.*, l.nama AS list_nama, l.is_archived AS list_archived')
                ->join('kanban_lists l', 'l.id = c.list_id')
                ->where('c.board_id', $id)->where('c.is_archived', 1)
                ->orderBy('c.updated_at', 'DESC')->get()->getResultArray(),
        ]);
    }

    public function restoreList(int $listId)
    {
        $listModel = new KanbanListModel();
        $list = $listModel->find($listId);
        if (! $list) return $this->jsonErr('Kolom tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $list['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $listModel->update($listId, ['is_archived' => 0]);
        (new KanbanBoardModel())->touch((int) $list['board_id']);
        ActivityLog::write('update', 'kanban_list', (string) $listId, 'Pulihkan — ' . $list['nama']);
        return $this->jsonOk();
    }

    /** Hapus permanen board (owner only; cascade FK + transaksi; file lampiran setelah commit). */
    public function delete(int $id)
    {
        $role = $this->role($id);
        if (! KanbanAccess::canManage($role)) return redirect()->back()->with('error', 'Hanya owner yang bisa menghapus board.');

        $board = (new KanbanBoardModel())->find($id);
        if (! $board) return redirect()->to('kanban')->with('error', 'Board tidak ditemukan.');

        // kumpulkan file lampiran dulu (dihapus fisik setelah commit)
        $stored = array_column(
            db_connect()->table('kanban_attachments a')
                ->select('a.stored_name')
                ->join('kanban_cards c', 'c.id = a.card_id')
                ->where('c.board_id', $id)->get()->getResultArray(),
            'stored_name'
        );

        $db = db_connect();
        $db->transStart();
        $db->table('kanban_boards')->where('id', $id)->delete(); // anak-anak ikut via FK cascade
        $db->transComplete();
        if ($db->transStatus() === false) return redirect()->back()->with('error', 'Gagal menghapus board.');

        foreach ($stored as $f) @unlink(WRITEPATH . 'kanban_uploads/' . $f);
        ActivityLog::write('delete', 'kanban_board', (string) $id, $board['nama']);
        return redirect()->to('kanban')->with('success', 'Board dihapus permanen.');
    }

    // ── Kartu Saya: kartu ber-assignee user, lintas board, urut due ─────
    public function myCards()
    {
        if (! $this->canViewMenu('kanban')) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $rows = db_connect()->table('kanban_cards c')
            ->select('c.*, l.nama AS list_nama, b.nama AS board_nama, b.color AS board_color, b.id AS bid')
            ->join('kanban_card_members cm', 'cm.card_id = c.id')
            ->join('kanban_lists l', 'l.id = c.list_id')
            ->join('kanban_boards b', 'b.id = c.board_id')
            ->where('cm.user_id', $this->uid())
            ->where('c.is_archived', 0)->where('l.is_archived', 0)->where('b.is_archived', 0)
            ->orderBy('c.due_done', 'ASC')
            ->orderBy('c.due_date IS NULL ASC, c.due_date ASC', '', false)
            ->get()->getResultArray();

        return view('kanban/my_cards', [
            'user'  => $this->currentUser(),
            'cards' => $rows,
        ]);
    }

    // ── Update board (nama/deskripsi/color) ─────────────────────────────
    public function update(int $id)
    {
        if (! KanbanAccess::canManage($this->role($id))) return $this->jsonErr('Hanya owner yang bisa mengubah board.', 403);

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return $this->jsonErr('Nama board wajib diisi.');

        (new KanbanBoardModel())->update($id, [
            'nama'      => $nama,
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'color'     => $this->request->getPost('color') ?: null,
        ]);

        ActivityLog::write('update', 'kanban_board', (string) $id, $nama);
        return $this->jsonOk();
    }

    // ── Arsip / pulihkan board ───────────────────────────────────────────
    public function archive(int $id)
    {
        $role = $this->role($id);
        if (! KanbanAccess::canManage($role)) return redirect()->back()->with('error', 'Hanya owner yang bisa mengarsip board.');

        $m = new KanbanBoardModel();
        $board = $m->find($id);
        if (! $board) return redirect()->to('kanban')->with('error', 'Board tidak ditemukan.');

        $to = (int) ! $board['is_archived'];
        $m->update($id, ['is_archived' => $to]);
        ActivityLog::write('update', 'kanban_board', (string) $id, ($to ? 'Arsip — ' : 'Pulihkan — ') . $board['nama']);
        return redirect()->to('kanban' . ($to ? '' : '/' . $id))->with('success', $to ? 'Board diarsipkan.' : 'Board dipulihkan.');
    }

    // ── List: create / rename / archive / reorder ───────────────────────
    public function createList(int $boardId)
    {
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return $this->jsonErr('Nama kolom wajib diisi.');

        $listModel = new KanbanListModel();
        $max = db_connect()->table('kanban_lists')->selectMax('position')
            ->where('board_id', $boardId)->get()->getRowArray();

        $id = $listModel->insert([
            'board_id' => $boardId,
            'nama'     => $nama,
            'position' => (int) ($max['position'] ?? -1) + 1,
        ]);

        (new KanbanBoardModel())->touch($boardId);
        ActivityLog::write('create', 'kanban_list', (string) $id, $nama, ['board_id' => $boardId]);
        return $this->jsonOk(['id' => $id, 'nama' => $nama]);
    }

    public function renameList(int $listId)
    {
        $listModel = new KanbanListModel();
        $list = $listModel->find($listId);
        if (! $list) return $this->jsonErr('Kolom tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $list['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') return $this->jsonErr('Nama kolom wajib diisi.');

        $listModel->update($listId, ['nama' => $nama]);
        (new KanbanBoardModel())->touch((int) $list['board_id']);
        ActivityLog::write('update', 'kanban_list', (string) $listId, $list['nama'] . ' → ' . $nama);
        return $this->jsonOk();
    }

    public function archiveList(int $listId)
    {
        $listModel = new KanbanListModel();
        $list = $listModel->find($listId);
        if (! $list) return $this->jsonErr('Kolom tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $list['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        // Arsip list → kartunya ikut tersembunyi (tetap ada; §12.1)
        $listModel->update($listId, ['is_archived' => 1]);
        (new KanbanBoardModel())->touch((int) $list['board_id']);
        ActivityLog::write('update', 'kanban_list', (string) $listId, 'Arsip — ' . $list['nama']);
        return $this->jsonOk();
    }

    public function reorderLists(int $boardId)
    {
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $ids = $this->request->getPost('list_ids') ?? [];
        if (! is_array($ids) || ! $ids) return $this->jsonErr('Urutan kolom kosong.');

        $db = db_connect();
        $db->transStart();
        foreach (array_values($ids) as $i => $listId) {
            $db->table('kanban_lists')
                ->where('id', (int) $listId)->where('board_id', $boardId)
                ->update(['position' => $i]);
        }
        $db->transComplete();
        if ($db->transStatus() === false) return $this->jsonErr('Gagal menyimpan urutan.');

        (new KanbanBoardModel())->touch($boardId);
        return $this->jsonOk();
    }

    // ── State (polling sync §5.1) ────────────────────────────────────────
    public function state(int $id)
    {
        if ($this->role($id) === null) return $this->jsonErr('Bukan anggota board.', 403);

        $board = (new KanbanBoardModel())->find($id);
        if (! $board) return $this->jsonErr('Board tidak ditemukan.', 404);

        return $this->response->setJSON([
            'success' => true,
            'rev'     => $board['updated_at'],
            'lists'   => (new KanbanListModel())->forBoard($id),
            'cards'   => (new KanbanCardModel())->byListForBoard($id),
            'labels'  => (new KanbanLabelModel())->forBoard($id),
            'members' => (new KanbanBoardMemberModel())->membersWithName($id),
            'unread'  => (new NotificationModel())->unreadCardIds($this->uid()),
        ]);
    }
}
