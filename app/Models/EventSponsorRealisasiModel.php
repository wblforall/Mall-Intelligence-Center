<?php

namespace App\Models;

use CodeIgniter\Model;

class EventSponsorRealisasiModel extends Model
{
    protected $table         = 'event_sponsor_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'sponsor_id', 'tanggal', 'nilai', 'catatan', 'file_foto', 'file_terima', 'created_by'];
    protected $useTimestamps = true;

    public function getGroupedByEvent(int $eventId): array
    {
        $rows   = $this->where('event_id', $eventId)->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['sponsor_id']][] = $r;
        }
        return $result;
    }

    public function getTotalByEvent(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('event_id', $eventId)->get()->getRow()->total ?? 0);
    }
}
