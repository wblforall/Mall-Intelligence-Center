<?php

namespace App\Models;

use CodeIgniter\Model;

class PromoMediaUsageModel extends Model
{
    protected $table         = 'promo_media_usage';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'batch_id', 'spot_id', 'slot_number', 'dept', 'requested_by', 'event_id', 'nama_materi', 'deskripsi_materi',
        'tanggal_mulai', 'tanggal_selesai', 'status', 'catatan_pemohon', 'sumber', 'is_berbayar', 'catatan_approver',
        'rejection_reason', 'submitted_at', 'approved_by', 'approved_at', 'created_by',
    ];

    public function getWithSpot(int $id): ?array
    {
        return $this->db->table('promo_media_usage u')
            ->select('u.*, s.nama spot_nama, s.tipe spot_tipe, s.area spot_area, s.kode spot_kode')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->where('u.id', $id)
            ->get()->getRowArray() ?: null;
    }

    public function getPending(): array
    {
        return $this->db->table('promo_media_usage u')
            ->select('u.*, s.nama spot_nama, s.tipe spot_tipe, s.kode spot_kode, s.area spot_area, cr.name submitted_by_name')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->join('users cr', 'cr.id = u.created_by', 'left')
            ->where('u.status', 'pending')
            ->orderBy('u.submitted_at')
            ->get()->getResultArray();
    }

    public function getPendingGrouped(): array
    {
        $rows = $this->getPending();
        $groups = [];
        foreach ($rows as $row) {
            $key = $row['batch_id'] ?: 'solo_' . $row['id'];
            $groups[$key][] = $row;
        }
        return $groups;
    }

    public function getByCreator(int $userId): array
    {
        return $this->db->table('promo_media_usage u')
            ->select('u.*, s.nama spot_nama, s.tipe spot_tipe, s.kode spot_kode, s.area spot_area')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->where('u.created_by', $userId)
            ->orderBy('u.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getBySpot(int $spotId): array
    {
        return $this->db->table('promo_media_usage u')
            ->select('u.*, cr.name created_by_name')
            ->join('users cr', 'cr.id = u.created_by', 'left')
            ->where('u.spot_id', $spotId)
            ->orderBy('u.tanggal_mulai', 'DESC')
            ->get()->getResultArray();
    }

    public function getForGantt(string $tglMulai, string $tglSelesai, ?string $tipe = null, ?string $dept = null): array
    {
        $q = $this->db->table('promo_media_usage u')
            ->select('u.*, s.nama spot_nama, s.tipe spot_tipe, s.kode spot_kode, s.area spot_area')
            ->join('promo_media_spots s', 's.id = u.spot_id')
            ->whereIn('u.status', ['pending', 'approved', 'done'])
            ->where('u.tanggal_mulai <=', $tglSelesai)
            ->where('u.tanggal_selesai >=', $tglMulai)
            ->orderBy('s.tipe')->orderBy('s.kode')->orderBy('u.slot_number');

        if ($tipe)  $q->where('s.tipe', $tipe);
        if ($dept)  $q->like('u.dept', $dept, 'both');

        return $q->get()->getResultArray();
    }

    public function hasConflictCetak(int $spotId, string $tglMulai, string $tglSelesai, ?int $excludeId = null): bool
    {
        $q = $this->db->table('promo_media_usage')
            ->where('spot_id', $spotId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('tanggal_mulai <=', $tglSelesai)
            ->where('tanggal_selesai >=', $tglMulai);
        if ($excludeId) $q->where('id !=', $excludeId);
        return $q->countAllResults() > 0;
    }

    public function hasConflictDigital(int $spotId, int $slot, string $tglMulai, string $tglSelesai, ?int $excludeId = null): bool
    {
        $q = $this->db->table('promo_media_usage')
            ->where('spot_id', $spotId)
            ->where('slot_number', $slot)
            ->whereIn('status', ['pending', 'approved'])
            ->where('tanggal_mulai <=', $tglSelesai)
            ->where('tanggal_selesai >=', $tglMulai);
        if ($excludeId) $q->where('id !=', $excludeId);
        return $q->countAllResults() > 0;
    }

    public function markDoneExpired(): void
    {
        $this->db->table('promo_media_usage')
            ->where('status', 'approved')
            ->where('tanggal_selesai <', date('Y-m-d'))
            ->update(['status' => 'done']);
    }
}
