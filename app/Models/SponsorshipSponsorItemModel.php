<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipSponsorItemModel extends Model
{
    protected $table         = 'sponsorship_sponsor_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'program_id', 'sponsor_id', 'deskripsi_barang', 'qty', 'nilai',
    ];

    // Keyed by sponsor_id → list of items
    public function getBySponsorIds(array $sponsorIds): array
    {
        if (empty($sponsorIds)) return [];
        $rows = $this->whereIn('sponsor_id', $sponsorIds)->orderBy('sponsor_id')->orderBy('id')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['sponsor_id']][] = $row;
        }
        return $grouped;
    }

    public function deleteBySponsor(int $sponsorId): void
    {
        $this->where('sponsor_id', $sponsorId)->delete();
    }
}
