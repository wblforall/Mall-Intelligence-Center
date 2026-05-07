<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLocationModel extends Model
{
    protected $table         = 'event_locations';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'mall', 'aktif'];
    protected $useTimestamps = false;

    public function getByMall(string $mall): array
    {
        // keduanya → tampilkan semua lokasi aktif
        if ($mall === 'keduanya') {
            return $this->where('aktif', 1)->orderBy('mall')->orderBy('nama')->findAll();
        }
        return $this->where('mall', $mall)->where('aktif', 1)->orderBy('nama')->findAll();
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('mall')->orderBy('nama')->findAll();
        $grouped = ['ewalk' => [], 'pentacity' => []];
        foreach ($rows as $r) {
            $grouped[$r['mall']][] = $r;
        }
        return $grouped;
    }

    // Simpan lokasi pilihan untuk satu event
    public function syncEventLocations(int $eventId, array $locationIds): void
    {
        $db = \Config\Database::connect();
        $db->table('event_location_map')->where('event_id', $eventId)->delete();
        foreach (array_unique($locationIds) as $locId) {
            $locId = (int)$locId;
            if ($locId > 0) {
                $db->table('event_location_map')->insert([
                    'event_id'    => $eventId,
                    'location_id' => $locId,
                ]);
            }
        }
    }

    public function getEventLocations(int $eventId): array
    {
        return \Config\Database::connect()
            ->table('event_location_map elm')
            ->select('el.id, el.nama, el.mall')
            ->join('event_locations el', 'el.id = elm.location_id')
            ->where('elm.event_id', $eventId)
            ->orderBy('el.mall')->orderBy('el.nama')
            ->get()->getResultArray();
    }

    public function getEventLocationIds(int $eventId): array
    {
        $rows = \Config\Database::connect()
            ->table('event_location_map')
            ->select('location_id')
            ->where('event_id', $eventId)
            ->get()->getResultArray();
        return array_column($rows, 'location_id');
    }
}
