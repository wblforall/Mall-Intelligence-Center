<?php

namespace App\Models;

use CodeIgniter\Model;

class CreativeItemModel extends Model
{
    protected $table         = 'creative_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tipe', 'nama', 'platform', 'tanggal_take', 'jam_take', 'pic',
        'deskripsi', 'tanggal', 'budget', 'target_reach', 'target_impressions', 'deadline', 'status', 'is_closed', 'closed_at', 'catatan', 'urutan', 'created_by',
        'versi', 'opsi_terakhir', 'review_submitted_at', 'approved_by', 'approved_at',
        'tema_id', 'event_id',
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


    public function getAll(): array
    {
        return $this->orderBy('tipe', 'ASC')
                    ->orderBy('urutan', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    public function getTotalBudget(): int
    {
        return (int)($this->selectSum('budget', 'total')
                         ->get()->getRow()->total ?? 0);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->update($id, ['status' => $status]);
    }
}
