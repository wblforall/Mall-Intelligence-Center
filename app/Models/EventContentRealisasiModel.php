<?php

namespace App\Models;

use CodeIgniter\Model;

class EventContentRealisasiModel extends Model
{
    protected $table         = 'event_content_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'content_item_id', 'tanggal', 'nilai', 'catatan', 'file_foto', 'file_terima', 'created_by'];
    protected $useTimestamps = true;

    public function getByItem(int $itemId): array
    {
        return $this->where('content_item_id', $itemId)->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    public function getGroupedByEvent(int $eventId): array
    {
        $rows   = $this->where('event_id', $eventId)->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['content_item_id']][] = $r;
        }
        return $result;
    }

    public function getTotalByItem(int $itemId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('content_item_id', $itemId)->get()->getRow()->total ?? 0);
    }

    public function getTotalByEvent(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('event_id', $eventId)->get()->getRow()->total ?? 0);
    }
}
