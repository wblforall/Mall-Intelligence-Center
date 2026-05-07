<?php

namespace App\Models;

use CodeIgniter\Model;

class EventSponsorItemModel extends Model
{
    protected $table         = 'event_sponsor_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['sponsor_id', 'deskripsi_barang', 'qty', 'nilai'];
    protected $useTimestamps = true;

    public function getBySponsor(int $sponsorId): array
    {
        return $this->where('sponsor_id', $sponsorId)->findAll();
    }

    public function getBySponsorIds(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->whereIn('sponsor_id', $ids)->findAll();
    }

    public function deleteBySponsor(int $sponsorId): void
    {
        $this->where('sponsor_id', $sponsorId)->delete();
    }
}
