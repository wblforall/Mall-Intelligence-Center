<?php

namespace App\Controllers;

use App\Models\KanbanBoardModel;
use App\Models\KanbanCardModel;
use App\Models\KanbanListModel;
use App\Models\KanbanChecklistModel;
use App\Models\KanbanChecklistItemModel;
use App\Models\KanbanCommentModel;
use App\Models\KanbanAttachmentModel;
use App\Libraries\KanbanAccess;
use App\Libraries\KanbanFeed;
use App\Libraries\Notify;
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

        KanbanFeed::log($boardId, (int) $id, $this->uid(), 'create_card', $judul . ' → ' . $list['nama']);
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
            KanbanFeed::log($boardId, $cardId, $this->uid(), 'move_card', $card['judul'] . ' → ' . $target['nama']);
            ActivityLog::write('update', 'kanban_card', (string) $cardId,
                'Pindah — ' . $card['judul'] . ' → ' . $target['nama'], ['board_id' => $boardId]);
        }
        return $this->jsonOk();
    }

    // ── Detail kartu (modal penuh) ───────────────────────────────────────
    public function detail(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if ($this->role((int) $card['board_id']) === null) return $this->jsonErr('Bukan anggota board.', 403);

        $db = db_connect();
        $creator = $db->table('users')->select('name')->where('id', $card['created_by'])->get()->getRowArray();

        // Checklist + item
        $checklists = $db->table('kanban_checklists')->where('card_id', $cardId)->orderBy('position')->orderBy('id')->get()->getResultArray();
        $clIds = array_column($checklists, 'id');
        $items = $clIds
            ? $db->table('kanban_checklist_items')->whereIn('checklist_id', $clIds)->orderBy('position')->orderBy('id')->get()->getResultArray()
            : [];
        $itemMap = [];
        foreach ($items as $it) $itemMap[(int) $it['checklist_id']][] = $it;
        foreach ($checklists as &$cl) $cl['items'] = $itemMap[(int) $cl['id']] ?? [];
        unset($cl);

        // Label & assignee kartu
        $labelIds  = array_column($db->table('kanban_card_labels')->where('card_id', $cardId)->get()->getResultArray(), 'label_id');
        $memberIds = array_column($db->table('kanban_card_members')->where('card_id', $cardId)->get()->getResultArray(), 'user_id');

        // Buka kartu = notifikasi kartu ini terbaca (KANBAN_DESIGN §6.3)
        Notify::markReadForLink($this->uid(), 'kanban_card', $cardId);

        return $this->response->setJSON([
            'success'     => true,
            'card'        => $card,
            'creator'     => $creator['name'] ?? '-',
            'label_ids'   => array_map('intval', $labelIds),
            'member_ids'  => array_map('intval', $memberIds),
            'checklists'  => $checklists,
            'comments'    => (new KanbanCommentModel())->forCard($cardId),
            'attachments' => (new KanbanAttachmentModel())->forCard($cardId),
            'activity'    => KanbanFeed::forCard($cardId),
        ]);
    }

    // ── Label kartu: toggle ──────────────────────────────────────────────
    public function toggleLabel(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $labelId = (int) $this->request->getPost('label_id');
        $db = db_connect();
        $lbl = $db->table('kanban_labels')->where('id', $labelId)->where('board_id', $card['board_id'])->get()->getRowArray();
        if (! $lbl) return $this->jsonErr('Label tidak valid.');

        $exists = $db->table('kanban_card_labels')->where('card_id', $cardId)->where('label_id', $labelId)->countAllResults();
        if ($exists) {
            $db->table('kanban_card_labels')->where('card_id', $cardId)->where('label_id', $labelId)->delete();
        } else {
            $db->table('kanban_card_labels')->insert(['card_id' => $cardId, 'label_id' => $labelId]);
        }
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        return $this->jsonOk(['attached' => ! $exists]);
    }

    // ── Assignee kartu: toggle (wajib anggota board) ─────────────────────
    public function toggleMember(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        $boardId = (int) $card['board_id'];
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $userId = (int) $this->request->getPost('user_id');
        $db = db_connect();
        $isMember = $db->table('kanban_board_members')->where('board_id', $boardId)->where('user_id', $userId)->countAllResults();
        if (! $isMember) return $this->jsonErr('Assignee harus anggota board.');

        $exists = $db->table('kanban_card_members')->where('card_id', $cardId)->where('user_id', $userId)->countAllResults();
        if ($exists) {
            $db->table('kanban_card_members')->where('card_id', $cardId)->where('user_id', $userId)->delete();
        } else {
            $db->table('kanban_card_members')->insert(['card_id' => $cardId, 'user_id' => $userId]);
            KanbanFeed::log($boardId, $cardId, $this->uid(), 'assign', 'Assign ke user #' . $userId);
            Notify::send([$userId], $this->uid(), 'kanban', 'assigned',
                'Anda ditugaskan di kartu "' . $card['judul'] . '"', null,
                'kanban_card', $cardId, 'kanban/' . $boardId);
        }
        (new KanbanBoardModel())->touch($boardId);
        return $this->jsonOk(['attached' => ! $exists]);
    }

    // ── Checklist ────────────────────────────────────────────────────────
    public function createChecklist(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $judul = trim((string) $this->request->getPost('judul')) ?: 'Checklist';
        $id = (new KanbanChecklistModel())->insert(['card_id' => $cardId, 'judul' => $judul]);
        KanbanFeed::log((int) $card['board_id'], $cardId, $this->uid(), 'add_checklist', $judul);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        return $this->jsonOk(['id' => $id]);
    }

    public function createItem(int $checklistId)
    {
        $cl = (new KanbanChecklistModel())->find($checklistId);
        if (! $cl) return $this->jsonErr('Checklist tidak ditemukan.', 404);
        $card = (new KanbanCardModel())->find((int) $cl['card_id']);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $teks = trim((string) $this->request->getPost('teks'));
        if ($teks === '') return $this->jsonErr('Isi item wajib diisi.');

        $id = (new KanbanChecklistItemModel())->insert(['checklist_id' => $checklistId, 'teks' => $teks]);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        return $this->jsonOk(['id' => $id]);
    }

    public function toggleItem(int $itemId)
    {
        $im = new KanbanChecklistItemModel();
        $item = $im->find($itemId);
        if (! $item) return $this->jsonErr('Item tidak ditemukan.', 404);
        $cl   = (new KanbanChecklistModel())->find((int) $item['checklist_id']);
        $card = (new KanbanCardModel())->find((int) $cl['card_id']);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $to = (int) ! $item['is_done'];
        $im->update($itemId, [
            'is_done' => $to,
            'done_by' => $to ? $this->uid() : null,
            'done_at' => $to ? date('Y-m-d H:i:s') : null,
        ]);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        return $this->jsonOk(['is_done' => $to]);
    }

    public function deleteItem(int $itemId)
    {
        $im = new KanbanChecklistItemModel();
        $item = $im->find($itemId);
        if (! $item) return $this->jsonErr('Item tidak ditemukan.', 404);
        $cl   = (new KanbanChecklistModel())->find((int) $item['checklist_id']);
        $card = (new KanbanCardModel())->find((int) $cl['card_id']);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $im->delete($itemId);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        return $this->jsonOk();
    }

    // ── Komentar (+@mention) — viewer boleh berkomentar (§3) ────────────
    public function addComment(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        $boardId = (int) $card['board_id'];
        if ($this->role($boardId) === null) return $this->jsonErr('Bukan anggota board.', 403);

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '') return $this->jsonErr('Komentar kosong.');

        (new KanbanCommentModel())->insert(['card_id' => $cardId, 'user_id' => $this->uid(), 'body' => $body]);
        KanbanFeed::log($boardId, $cardId, $this->uid(), 'comment', mb_substr($body, 0, 120));

        // Notif → @mention (tipe mention) + assignee kartu (tipe comment); penulis dikecualikan
        $db = db_connect();
        $actor = $this->currentUser()['name'] ?? '';
        $excerpt = mb_substr($body, 0, 200);

        $mentioned = [];
        $mentionIds = $this->request->getPost('mention_ids') ?? [];
        if (is_array($mentionIds)) {
            foreach ($mentionIds as $mid) {
                // mention harus anggota board — jangan bocor ke luar
                $ok = $db->table('kanban_board_members')->where('board_id', $boardId)->where('user_id', (int) $mid)->countAllResults();
                if ($ok) $mentioned[] = (int) $mid;
            }
        }
        if ($mentioned) {
            Notify::send($mentioned, $this->uid(), 'kanban', 'mention',
                $actor . ' menyebut Anda di "' . $card['judul'] . '"',
                $excerpt, 'kanban_card', $cardId, 'kanban/' . $boardId);
        }
        $assignees = array_map('intval', array_column(
            $db->table('kanban_card_members')->where('card_id', $cardId)->get()->getResultArray(), 'user_id'));
        $assignees = array_diff($assignees, $mentioned); // jangan dobel notif
        if ($assignees) {
            Notify::send($assignees, $this->uid(), 'kanban', 'comment',
                $actor . ' berkomentar di "' . $card['judul'] . '"',
                $excerpt, 'kanban_card', $cardId, 'kanban/' . $boardId);
        }

        ActivityLog::write('create', 'kanban_card', (string) $cardId, 'Komentar — ' . $card['judul']);
        return $this->jsonOk(['comments' => (new KanbanCommentModel())->forCard($cardId)]);
    }

    public function deleteComment(int $commentId)
    {
        $cm = new KanbanCommentModel();
        $c = $cm->find($commentId);
        if (! $c) return $this->jsonErr('Komentar tidak ditemukan.', 404);

        $card = (new KanbanCardModel())->find((int) $c['card_id']);
        $role = $this->role((int) $card['board_id']);
        // penulis sendiri, atau owner/admin board
        if ((int) $c['user_id'] !== $this->uid() && ! KanbanAccess::canManage($role)) {
            return $this->jsonErr('Hanya penulis / owner yang bisa menghapus.', 403);
        }
        $cm->delete($commentId);
        return $this->jsonOk(['comments' => $cm->forCard((int) $c['card_id'])]);
    }

    // ── Lampiran (controller-guarded, dir non-public §7) ─────────────────
    private const ATT_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip', 'application/x-zip-compressed',
    ];

    public function upload(int $cardId)
    {
        $card = (new KanbanCardModel())->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        $boardId = (int) $card['board_id'];
        if (! KanbanAccess::canEdit($this->role($boardId))) return $this->jsonErr('Tidak ada izin.', 403);

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) return $this->jsonErr('File tidak valid.');
        if ($file->getSizeByUnit('mb') > 10) return $this->jsonErr('Maksimal 10 MB.');
        if (! in_array($file->getMimeType(), self::ATT_MIMES, true)) {
            return $this->jsonErr('Tipe file tidak diizinkan (jpg/png/webp, pdf, doc, xls, ppt, zip).');
        }

        // Ambil metadata SEBELUM move() — setelah move objek file terkunci (CI4)
        $origName = $file->getClientName();
        $mime     = $file->getClientMimeType();
        $size     = (int) $file->getSize();
        $ext      = strtolower($file->getClientExtension() ?: $file->guessExtension());

        $stored = 'k_' . $cardId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move(WRITEPATH . 'kanban_uploads', $stored);

        (new KanbanAttachmentModel())->insert([
            'card_id'     => $cardId,
            'filename'    => $origName,
            'stored_name' => $stored,
            'mime'        => $mime,
            'size'        => $size,
            'uploaded_by' => $this->uid(),
        ]);

        KanbanFeed::log($boardId, $cardId, $this->uid(), 'add_attachment', $origName);
        (new KanbanBoardModel())->touch($boardId);
        ActivityLog::write('create', 'kanban_card', (string) $cardId, 'Lampiran — ' . $file->getClientName());
        return $this->jsonOk(['attachments' => (new KanbanAttachmentModel())->forCard($cardId)]);
    }

    public function download(int $attachmentId)
    {
        $att = (new KanbanAttachmentModel())->find($attachmentId);
        if (! $att) return redirect()->to('kanban')->with('error', 'Lampiran tidak ditemukan.');

        $card = (new KanbanCardModel())->find((int) $att['card_id']);
        if ($this->role((int) $card['board_id']) === null) {
            return redirect()->to('kanban')->with('error', 'Bukan anggota board.');
        }

        $path = WRITEPATH . 'kanban_uploads/' . $att['stored_name'];
        if (! is_file($path)) return redirect()->back()->with('error', 'File fisik tidak ditemukan.');
        return $this->response->download($path, null)->setFileName($att['filename']);
    }

    public function deleteAttachment(int $attachmentId)
    {
        $am = new KanbanAttachmentModel();
        $att = $am->find($attachmentId);
        if (! $att) return $this->jsonErr('Lampiran tidak ditemukan.', 404);

        $card = (new KanbanCardModel())->find((int) $att['card_id']);
        $role = $this->role((int) $card['board_id']);
        if ((int) $att['uploaded_by'] !== $this->uid() && ! KanbanAccess::canManage($role)) {
            return $this->jsonErr('Hanya pengunggah / owner.', 403);
        }

        $am->delete($attachmentId);
        @unlink(WRITEPATH . 'kanban_uploads/' . $att['stored_name']); // setelah delete DB sukses
        ActivityLog::write('delete', 'kanban_card', (string) $att['card_id'], 'Hapus lampiran — ' . $att['filename']);
        return $this->jsonOk(['attachments' => $am->forCard((int) $att['card_id'])]);
    }

    // ── Pulihkan kartu terarsip ──────────────────────────────────────────
    public function restore(int $cardId)
    {
        $cardModel = new KanbanCardModel();
        $card = $cardModel->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canEdit($this->role((int) $card['board_id']))) return $this->jsonErr('Tidak ada izin.', 403);

        $cardModel->update($cardId, ['is_archived' => 0]);
        KanbanFeed::log((int) $card['board_id'], $cardId, $this->uid(), 'restore_card', $card['judul']);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        ActivityLog::write('update', 'kanban_card', (string) $cardId, 'Pulihkan — ' . $card['judul']);
        return $this->jsonOk();
    }

    /** Hapus permanen kartu (owner board saja; lampiran fisik ikut). */
    public function delete(int $cardId)
    {
        $cardModel = new KanbanCardModel();
        $card = $cardModel->find($cardId);
        if (! $card) return $this->jsonErr('Kartu tidak ditemukan.', 404);
        if (! KanbanAccess::canManage($this->role((int) $card['board_id']))) return $this->jsonErr('Hanya owner.', 403);

        $stored = array_column(
            db_connect()->table('kanban_attachments')->select('stored_name')->where('card_id', $cardId)->get()->getResultArray(),
            'stored_name'
        );
        $db = db_connect();
        $db->transStart();
        $db->table('kanban_cards')->where('id', $cardId)->delete(); // anak via FK cascade
        $db->transComplete();
        if ($db->transStatus() === false) return $this->jsonErr('Gagal menghapus kartu.');

        foreach ($stored as $f) @unlink(WRITEPATH . 'kanban_uploads/' . $f);
        ActivityLog::write('delete', 'kanban_card', (string) $cardId, $card['judul']);
        return $this->jsonOk();
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
        KanbanFeed::log((int) $card['board_id'], $cardId, $this->uid(), 'archive_card', $card['judul']);
        (new KanbanBoardModel())->touch((int) $card['board_id']);
        ActivityLog::write('update', 'kanban_card', (string) $cardId, 'Arsip — ' . $card['judul'], ['board_id' => $card['board_id']]);
        return $this->jsonOk();
    }
}
