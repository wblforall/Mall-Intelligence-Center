<?php

namespace App\Controllers;

use App\Models\KanbanBoardModel;
use App\Models\KanbanCardModel;
use App\Models\KanbanListModel;
use App\Libraries\KanbanAccess;
use App\Libraries\ActivityLog;

/**
 * Boards/Kanban — card-level (KANBAN_DESIGN.md §4).
 * Fase 1: create, move (drag-drop reindex §5), detail ringkas, update, arsip.
 * Checklist/komentar/lampiran/label/assignee = Fase 2–3.
 */
class KanbanCard extends BaseController
{
    private function uid(): int
    {
        return (int) $this->currentUser()['id'];
    }

    private function role(int $boardId): ?string
    {
        return KanbanAccess::role($boardId, $this->uid(), $this->isAdmin());
    }

    private function jsonOk(array $extra = [])
    {
        return $this->response->setJSON(['success' => true, 'csrf' => csrf_hash()] + $extra);
    }

    private function jsonErr(string $msg, int $status = 400)
    {
        return $this->response->setStatusCode($status)
            ->setJSON(['success' => false, 'message' => $msg, 'csrf' => csrf_hash()]);
    }

    // ── Buat kartu ───────────────────────────────────────────────────────
    public function create(int $listId)
    {
        $list = (new KanbanListModel())->find($listId);
        if (! $list) return $this->jsonErr('Kolom tidak ditemukan.', 404);

        $boardId = (int) $list['board_id'];
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '') return $this->jsonErr('Judul kartu wajib diisi.');

        $cardModel = new KanbanCardModel();
        $max = db_connect()->table('kanban_cards')->selectMax('position')
            ->where('list_id', $listId)->where('is_archived', 0)->get()->getRowArray();

        $id = $cardModel->insert([
            'board_id'   => $boardId,
            'list_id'    => $listId,
            'judul'      => $judul,
            'position'   => (int) ($max['position'] ?? -1) + 1,
            'created_by' => $this->uid(),
        ]);

        (new KanbanBoardModel())->touch($boardId);
        ActivityLog::write('create', 'kanban_card', (string) $id, $judul, ['board_id' => $boardId, 'list' => $list['nama']]);
        return $this->jsonOk(['card' => $cardModel->find($id)]);
    }

    // ── Pindah / urut kartu (reindex deterministik §5) ───────────────────
    public function move(int $cardId)
    {
        $cardModel = new KanbanCardModel();
        $card = $cardModel->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);

        $boardId = (int) $card['board_id'];
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $targetListId = (int) $this->request->getPost('list_id');
        $orderedIds   = $this->request->getPost('card_ids') ?? [];      // urutan list tujuan
        $sourceIds    = $this->request->getPost('source_card_ids');     // urutan list asal (bila lintas kolom)

        $target = (new KanbanListModel())->find($targetListId);
        if (! $target || (int) $target['board_id'] !== $boardId) return $this->jsonErr('Kolom tujuan tidak valid.');
        if (! is_array($orderedIds) || ! $orderedIds) return $this->jsonErr('Urutan kartu kosong.');

        $db = db_connect();
        $db->transStart();

        // Kartu pindah kolom?
        $crossList = (int) $card['list_id'] !== $targetListId;
        if ($crossList) {
            $cardModel->update($cardId, ['list_id' => $targetListId]);
        }

        // Reindex list tujuan sesuai urutan yang dikirim klien
        foreach (array_values($orderedIds) as $i => $cid) {
            $db->table('kanban_cards')
                ->where('id', (int) $cid)->where('board_id', $boardId)
                ->update(['position' => $i, 'list_id' => $targetListId]);
        }
        // Reindex list asal (bila lintas kolom & dikirim)
        if ($crossList && is_array($sourceIds)) {
            foreach (array_values($sourceIds) as $i => $cid) {
                $db->table('kanban_cards')
                    ->where('id', (int) $cid)->where('board_id', $boardId)
                    ->update(['position' => $i]);
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) return $this->jsonErr('Gagal memindahkan kartu.');

        (new KanbanBoardModel())->touch($boardId);
        if ($crossList) {
            ActivityLog::write('update', 'kanban_card', (string) $cardId,
                'Pindah — ' . $card['judul'] . ' → ' . $target['nama'], ['board_id' => $boardId]);
        }
        return $this->jsonOk();
    }

    // ── Detail kartu (modal) ─────────────────────────────────────────────
    public function detail(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if ($this->role((int) $card['board_id']) === null) return $this->jsonErr('Bukan anggota board.', 403);

        $creator = db_connect()->table('users')->select('name')->where('id', $card['created_by'])->get()->getRowArray();
        return $this->response->setJSON([
            'success' => true,
            'card'    => $card,
            'creator' => $creator['name'] ?? '-',
        ]);
    }

    // ── Update kartu (judul/deskripsi/due) ───────────────────────────────
    public function update(int $cardId)
    {
        $cardModel = new KanbanCardModel();
        $card = $cardModel->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $judul = trim((string) $this->request->getPost('judul'));
        if ($judul === '') return $this->jsonErr('Judul kartu wajib diisi.');

        $due = trim((string) $this->request->getPost('due_date'));
        $cardModel->update($cardId, [
            'judul'     => $judul,
            'deskripsi' => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'due_date'  => $due !== '' ? date('Y-m-d H:i:s', strtotime($due)) : null,
            'due_done'  => (int) ((string) $this->request->getPost('due_done') === '1'),
        ]);

        (new KanbanBoardModel())->touch((int) $card['board_id']);
        ActivityLog::write('update', 'kanban_card', (string) $cardId, $judul, ['board_id' => $card['board_id']]);
        return $this->jsonOk(['card' => $cardModel->find($cardId)]);
    }

    // ── Arsip kartu ──────────────────────────────────────────────────────
    public function archive(int $cardId)
    {
        $cardModel = new KanbanCardModel();
        $card = $cardModel->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $cardModel->update($cardId, ['is_archived' => 1]);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        ActivityLog::write('update', 'kanban_card', (string) $cardId, 'Arsip — ' . $card['judul'], ['board_id' => $card['board_id']]);
        return $this->jsonOk();
    }
}
