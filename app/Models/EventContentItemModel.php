<?php

namespace App\Models;

use CodeIgniter\Model;

class EventContentItemModel extends Model
{
    protected $table         = 'event_content_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'nama', 'tipe', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'jenis', 'pic', 'lokasi', 'budget', 'keterangan', 'urutan', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('urutan')->orderBy('id')->findAll();
    }

    public function getTotalBudget(int $eventId): int
    {
        return (int)($this->selectSum('budget', 'total')->where('event_id', $eventId)->get()->getRow()->total ?? 0);
    }
}
