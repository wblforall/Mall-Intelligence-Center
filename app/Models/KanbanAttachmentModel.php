<?php

namespace App\Models;

use CodeIgniter\Model;

class KanbanAttachmentModel extends Model
{
    protected $table         = 'kanban_attachments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $updatedField  = ''; // tabel hanya punya created_at
    protected $allowedFields = ['card_id', 'filename', 'stored_name', 'mime', 'size', 'uploaded_by'];

    public function forCard(int $cardId): array
    {
        return db_connect()->table('kanban_attachments a')
            ->select('a.*, u.name AS uploader_name')
            ->join('users u', 'u.id = a.uploaded_by')
            ->where('a.card_id', $cardId)
            ->orderBy('a.id', 'DESC')
            ->get()->getResultArray();
    }
}
