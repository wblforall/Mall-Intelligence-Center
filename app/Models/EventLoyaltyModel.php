<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLoyaltyModel extends Model
{
    protected $table         = 'event_loyalty_programs';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'nama_program', 'mekanisme', 'target_type', 'target_peserta', 'target_member_aktif', 'target_penyerapan', 'total_voucher', 'nilai_voucher', 'biaya_per_member', 'budget', 'catatan', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('id')->findAll();
    }

    // All event loyalty programs joined with event name (for standalone loyalty page)
    public function getAllWithEvent(): array
    {
        return $this->db->table('event_loyalty_programs elp')
            ->select('elp.*, e.name as event_name, e.start_date as event_start_date, ec.id as completion_id')
            ->join('events e', 'e.id = elp.event_id')
            ->join('event_completions ec', "ec.event_id = elp.event_id AND ec.module = 'loyalty'", 'left')
            ->orderBy('e.start_date', 'DESC')
            ->orderBy('elp.nama_program', 'ASC')
            ->get()->getResultArray();
    }
}
