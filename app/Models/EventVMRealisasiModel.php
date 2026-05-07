<?php

namespace App\Models;

use CodeIgniter\Model;

class EventVMRealisasiModel extends Model
{
    protected $table         = 'event_vm_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['vm_item_id', 'event_id', 'tanggal', 'jumlah', 'catatan', 'foto_file_name', 'foto_original_name', 'created_by'];
    protected $useTimestamps = true;

    public function getByItem(int $itemId): array
    {
        return $this->where('vm_item_id', $itemId)->orderBy('tanggal', 'ASC')->findAll();
    }

    public function getTotalByItem(int $itemId): int
    {
        return (int)($this->selectSum('jumlah', 'total')->where('vm_item_id', $itemId)->get()->getRow()->total ?? 0);
    }

    public function getGroupedByEvent(int $eventId): array
    {
        $rows = $this->where('event_id', $eventId)->orderBy('tanggal', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $id = $r['vm_item_id'];
            if (! isset($result[$id])) {
                $result[$id] = ['total' => 0, 'entries' => []];
            }
            $result[$id]['total']     += (int)$r['jumlah'];
            $result[$id]['entries'][]  = $r;
        }
        return $result;
    }

    public function getGroupedByStandalone(): array
    {
        $rows = $this->where('event_id IS NULL', null, false)->orderBy('tanggal', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $id = $r['vm_item_id'];
            if (! isset($result[$id])) {
                $result[$id] = ['total' => 0, 'entries' => []];
            }
            $result[$id]['total']     += (int)$r['jumlah'];
            $result[$id]['entries'][]  = $r;
        }
        return $result;
    }

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('vm_item_id', $itemIds)->orderBy('tanggal', 'ASC')->findAll();
        $result = [];
        foreach ($rows as $r) {
            $id = $r['vm_item_id'];
            if (! isset($result[$id])) {
                $result[$id] = ['total' => 0, 'entries' => []];
            }
            $result[$id]['total']     += (int)$r['jumlah'];
            $result[$id]['entries'][]  = $r;
        }
        return $result;
    }

    public function getMonthlyGrouped(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        [$year, $month] = explode('-', $bulan);
        $rows = $this->whereIn('vm_item_id', $itemIds)
                     ->where('YEAR(tanggal)', (int)$year)
                     ->where('MONTH(tanggal)', (int)$month)
                     ->orderBy('tanggal', 'ASC')
                     ->findAll();
        $result = [];
        foreach ($rows as $r) {
            $id = $r['vm_item_id'];
            $result[$id]['entries'][] = $r;
            $result[$id]['total']     = ($result[$id]['total'] ?? 0) + (int)$r['jumlah'];
        }
        return $result;
    }
}
