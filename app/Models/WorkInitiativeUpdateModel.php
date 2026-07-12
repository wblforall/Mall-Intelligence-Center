<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeUpdateModel extends Model
{
    protected $table         = 'work_initiative_updates';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'initiative_id', 'status', 'progress_pct', 'catatan', 'hambatan', 'updated_by', 'created_at',
    ];

    public function historyFor(int $initiativeId): array
    {
        return $this->historiesForMany([$initiativeId])[$initiativeId] ?? [];
    }

    /**
     * History semua update utk banyak inisiatif sekaligus — 2 query total
     * (bukan 2 query per inisiatif). Return: [initiative_id => [update, ...]]
     * terurut created_at DESC, tiap update punya key 'images' (selalu ada).
     */
    public function historiesForMany(array $initiativeIds): array
    {
        if (! $initiativeIds) return [];

        $rows = $this->select('work_initiative_updates.*, e.nama AS updated_by_name')
            ->join('employees e', 'e.id = work_initiative_updates.updated_by', 'left')
            ->whereIn('initiative_id', $initiativeIds)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Lampirkan foto bukti per update.
        $imgMap = [];
        if ($rows) {
            $imgs = \Config\Database::connect()->table('work_initiative_update_images')
                ->whereIn('update_id', array_column($rows, 'id'))
                ->orderBy('id')
                ->get()->getResultArray();
            foreach ($imgs as $im) $imgMap[$im['update_id']][] = $im;
        }

        $map = [];
        foreach ($rows as $r) {
            $r['images'] = $imgMap[$r['id']] ?? [];
            $map[$r['initiative_id']][] = $r;
        }
        return $map;
    }

    public function latestFor(int $initiativeId): ?array
    {
        return $this->where('initiative_id', $initiativeId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
