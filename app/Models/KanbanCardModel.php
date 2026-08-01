<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanCardModel extends Model
{
    protected $table         = 'kanban_cards';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'board_id', 'list_id', 'judul', 'deskripsi', 'position',
        'due_date', 'due_done', 'cover_color', 'is_archived', 'created_by',
    ];

    /**
     * Semua kartu aktif satu board, dikelompok per list_id, DIPERKAYA:
     * label_ids, member_ids, comment_count, attachment_count, cl_done/cl_total.
     * Bulk query (5 query berapapun jumlah kartu) — untuk render & state.
     */
    public function byListForBoard(int $boardId): array
    {
        $rows = $this->where('board_id', $boardId)
            ->where('is_archived', 0)
            ->orderBy('position')->orderBy('id')
            ->findAll();
        if (! $rows) return [];

        $ids = array_column($rows, 'id');
        $db  = db_connect();

        $labelMap = [];
        foreach ($db->table('kanban_card_labels')->whereIn('card_id', $ids)->get()->getResultArray() as $r) {
            $labelMap[(int) $r['card_id']][] = (int) $r['label_id'];
        }
        $memberMap = [];
        foreach ($db->table('kanban_card_members')->whereIn('card_id', $ids)->get()->getResultArray() as $r) {
            $memberMap[(int) $r['card_id']][] = (int) $r['user_id'];
        }
        $commentMap = [];
        foreach ($db->table('kanban_comments')->select('card_id, COUNT(*) AS n')->whereIn('card_id', $ids)->groupBy('card_id')->get()->getResultArray() as $r) {
            $commentMap[(int) $r['card_id']] = (int) $r['n'];
        }
        $attMap = [];
        foreach ($db->table('kanban_attachments')->select('card_id, COUNT(*) AS n')->whereIn('card_id', $ids)->groupBy('card_id')->get()->getResultArray() as $r) {
            $attMap[(int) $r['card_id']] = (int) $r['n'];
        }
        $clMap = [];
        foreach ($db->table('kanban_checklist_items i')
            ->select('c.card_id, COUNT(*) AS total, SUM(i.is_done) AS done')
            ->join('kanban_checklists c', 'c.id = i.checklist_id')
            ->whereIn('c.card_id', $ids)->groupBy('c.card_id')->get()->getResultArray() as $r) {
            $clMap[(int) $r['card_id']] = ['done' => (int) $r['done'], 'total' => (int) $r['total']];
        }

        $grouped = [];
        foreach ($rows as $r) {
            $cid = (int) $r['id'];
            $r['label_ids']        = $labelMap[$cid]   ?? [];
            $r['member_ids']       = $memberMap[$cid]  ?? [];
            $r['comment_count']    = $commentMap[$cid] ?? 0;
            $r['attachment_count'] = $attMap[$cid]     ?? 0;
            $r['cl_done']          = $clMap[$cid]['done']  ?? 0;
            $r['cl_total']         = $clMap[$cid]['total'] ?? 0;
            $grouped[(int) $r['list_id']][] = $r;
        }
        return $grouped;
    }
}
