<?php

namespace App\Models;

use CodeIgniter\Model;

class IdpItemModel extends Model
{
    protected $table         = 'idp_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'idp_id','competency_id','judul','level_saat_ini','level_target',
        'langkah_aksi','sumber_daya','deadline','status','catatan_progres','urutan',
    ];

    public function getByIdp(int $idpId): array
    {
        $db = db_connect();
        return $db->table('idp_items i')
            ->select('i.*, c.nama as competency_nama, c.kategori as competency_kategori,
                      cl.nama as cluster_nama')
            ->join('competencies c', 'c.id = i.competency_id', 'left')
            ->join('competency_clusters cl', 'cl.id = c.cluster_id', 'left')
            ->where('i.idp_id', $idpId)
            ->orderBy('i.urutan', 'ASC')
            ->orderBy('i.id', 'ASC')
            ->get()->getResultArray();
    }
}
