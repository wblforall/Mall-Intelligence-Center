<?php

namespace App\Models;

use CodeIgniter\Model;

class PestItemModel extends Model
{
    protected $table         = 'pest_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'urutan', 'aktif', 'created_at'];
    protected $useTimestamps = false;

    /** Item aktif, berurutan — dipakai sebagai baris form input & kolom rekap. */
    public function aktif(): array
    {
        return $this->where('aktif', 1)->orderBy('urutan')->orderBy('id')->findAll();
    }

    /** Semua item termasuk yang nonaktif — untuk halaman master. */
    public function semua(): array
    {
        return $this->orderBy('urutan')->orderBy('id')->findAll();
    }

    /**
     * Item yang sudah pernah punya temuan tidak boleh dihapus — angka historis
     * akan ikut hilang (FK-nya memang RESTRICT, ini untuk pesan yang ramah).
     */
    public function dipakai(int $id): bool
    {
        return (bool) $this->db->table('pest_findings')->where('item_id', $id)->countAllResults();
    }
}
