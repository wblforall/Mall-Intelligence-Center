<?php

namespace App\Controllers;

use App\Models\KanbanBoardModel;
use App\Models\KanbanBoardMemberModel;
use App\Models\KanbanListModel;
use App\Models\KanbanCardModel;
use App\Libraries\KanbanAccess;
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

        return view('kanban/board', [
            'user'    => $this->currentUser(),
            'board'   => $board,
            'role'    => $role,
            'canEdit' => KanbanAccess::canEdit($role),
            'canManage' => KanbanAccess::canManage($role),
            'lists'   => (new KanbanListModel())->forBoard($id),
            'cards'   => (new KanbanCardModel())->byListForBoard($id),
            'members' => (new KanbanBoardMemberModel())->membersWithName($id),
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
        ]);
    }
}
