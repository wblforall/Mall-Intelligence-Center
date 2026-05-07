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
        'deskripsi', 'budget', 'status', 'catatan', 'urutan', 'created_by',
    ];

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
