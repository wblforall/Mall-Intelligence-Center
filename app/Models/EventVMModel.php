<?php

namespace App\Models;

use CodeIgniter\Model;

class EventVMModel extends Model
{
    protected $table         = 'event_vm_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'nama_item', 'deskripsi_referensi', 'budget', 'catatan', 'tanggal_deadline', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('id')->findAll();
    }

    public function getStandalone(): array
    {
        return $this->where('event_id IS NULL', null, false)->orderBy('id')->findAll();
    }

    public function getAllWithEvent(): array
    {
        return $this->db->table('event_vm_items vi')
            ->select('vi.*, e.name as event_name, e.mall as event_mall')
            ->join('events e', 'e.id = vi.event_id')
            ->where('vi.event_id IS NOT NULL')
            ->orderBy('e.start_date', 'DESC')
            ->orderBy('vi.id', 'ASC')
            ->get()->getResultArray();
    }

    public function getTotalBudget(int $eventId): int
    {
        return (int)($this->selectSum('budget', 'total')->where('event_id', $eventId)->get()->getRow()->total ?? 0);
    }
}
