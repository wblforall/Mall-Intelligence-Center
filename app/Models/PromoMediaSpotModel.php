<?php

namespace App\Models;

use CodeIgniter\Model;

class PromoMediaSpotModel extends Model
{
    protected $table         = 'promo_media_spots';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['kode', 'nama', 'tipe', 'area', 'ukuran', 'total_slots', 'is_active', 'catatan', 'created_by'];

    public function getAll(): array
    {
        return $this->where('is_active', 1)->orderBy('tipe')->orderBy('kode')->findAll();
    }

    public function getAllWithStatus(string $tanggal): array
    {
        $spots = $this->orderBy('tipe')->orderBy('kode')->findAll();
        $db    = db_connect();

        foreach ($spots as &$spot) {
            if ($spot['tipe'] === 'digital') {
                $usedSlots = $db->table('promo_media_usage')
                    ->select('slot_number')
                    ->where('spot_id', $spot['id'])
                    ->whereIn('status', ['approved', 'pending'])
                    ->where('tanggal_mulai <=', $tanggal)
                    ->where('tanggal_selesai >=', $tanggal)
                    ->get()->getResultArray();
                $spot['used_slots'] = array_column($usedSlots, 'slot_number');
                $spot['sisa_slots'] = $spot['total_slots'] - count($spot['used_slots']);
            } else {
                $active = $db->table('promo_media_usage u')
                    ->select('u.*')
                    ->where('u.spot_id', $spot['id'])
                    ->whereIn('u.status', ['approved', 'pending'])
                    ->where('u.tanggal_mulai <=', $tanggal)
                    ->where('u.tanggal_selesai >=', $tanggal)
                    ->get()->getRowArray();
                $spot['active_usage'] = $active ?: null;
            }
        }

        return $spots;
    }

    public function getAvailableSlots(int $spotId, string $tglMulai, string $tglSelesai, ?int $excludeUsageId = null): array
    {
        $spot = $this->find($spotId);
        if (!$spot || $spot['tipe'] !== 'digital') return [];

        $db = db_connect();
        $q  = $db->table('promo_media_usage')
            ->select('slot_number')
            ->where('spot_id', $spotId)
            ->whereIn('status', ['approved', 'pending'])
            ->groupStart()
                ->where('tanggal_mulai <=', $tglSelesai)
                ->where('tanggal_selesai >=', $tglMulai)
            ->groupEnd();

        if ($excludeUsageId) $q->where('id !=', $excludeUsageId);

        $used = array_column($q->get()->getResultArray(), 'slot_number');
        $all  = range(1, (int)$spot['total_slots']);

        return array_values(array_filter($all, fn($s) => !in_array($s, $used)));
    }

    public function isKodeUnique(string $kode, ?int $excludeId = null): bool
    {
        $q = $this->where('kode', $kode);
        if ($excludeId) $q->where('id !=', $excludeId);
        return $q->countAllResults() === 0;
    }
}
