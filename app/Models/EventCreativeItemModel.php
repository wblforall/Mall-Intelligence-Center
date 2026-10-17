<?php

namespace App\Models;

use CodeIgniter\Model;

class EventCreativeItemModel extends Model
{
    protected $table         = 'event_creative_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'event_id', 'tipe', 'nama', 'platform', 'tanggal_take', 'jam_take', 'pic',
        'deskripsi', 'budget', 'target_reach', 'target_impressions', 'deadline', 'status', 'catatan', 'urutan', 'created_by',
        'versi', 'opsi_terakhir', 'review_submitted_at', 'approved_by', 'approved_at',
        'dari_paket', 'jenis_media', 'ukuran', 'pasang_mulai', 'pasang_selesai', 'design_ref', 'status_konten',
    ];

    protected $beforeUpdate = ['bersihkanRencana'];

    /** Item "Rencana" dari paket awal berhenti jadi rencana begitu isinya diubah (bukan sekadar dipindah Tema). */
    protected function bersihkanRencana(array $d): array
    {
        $data = $d['data'] ?? [];
        if (array_key_exists('dari_paket', $data)) return $d;
        if (! array_diff(array_keys($data), ['tema_id', 'event_id', 'updated_at'])) return $d;
        $d['data']['dari_paket'] = 0;
        return $d;
    }


    /** @param bool $tanpaRencana true = lewati item "Rencana" dari paket awal (untuk ringkasan / statistik) */
    public function getByEvent(int $eventId, bool $tanpaRencana = false): array
    {
        $b = $this->where('event_id', $eventId);
        if ($tanpaRencana) $b->where('dari_paket', 0);
        return $b->orderBy('tipe')->orderBy('urutan')->orderBy('id')
                    ->findAll();
    }

    public function getTotalBudget(int $eventId): int
    {
        return (int)($this->selectSum('budget', 'total')
                         ->where('event_id', $eventId)
                         ->get()->getRow()->total ?? 0);
    }

    public function getAllWithEvents(): array
    {
        return $this->db->table('event_creative_items ci')
            ->select('ci.*, e.name AS event_name, e.mall AS event_mall, e.id AS event_id, e.start_date AS event_start, e.event_days, DATE_ADD(e.start_date, INTERVAL (e.event_days - 1) DAY) AS event_end, ec.id AS creative_locked')
            ->join('events e', 'e.id = ci.event_id', 'left')
            ->join('event_completions ec', "ec.event_id = ci.event_id AND ec.module = 'creative'", 'left')
            ->orderBy('ci.tipe')
            ->orderBy('e.start_date', 'DESC')
            ->orderBy('ci.urutan')
            ->orderBy('ci.id')
            ->get()->getResultArray();
    }
}
