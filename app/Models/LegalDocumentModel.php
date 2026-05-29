<?php

namespace App\Models;

use CodeIgniter\Model;

class LegalDocumentModel extends Model
{
    protected $table         = 'legal_documents';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'entity_type', 'entity_id', 'nama_dokumen', 'file_path', 'file_size',
        'uploaded_by', 'uploaded_at',
    ];

    public function getForEntity(string $type, int $id): array
    {
        return $this->db->table('legal_documents d')
            ->select('d.*, u.name as uploader_name')
            ->join('users u', 'u.id = d.uploaded_by', 'left')
            ->where('d.entity_type', $type)
            ->where('d.entity_id', $id)
            ->orderBy('d.uploaded_at', 'DESC')
            ->get()->getResultArray();
    }

    public function deleteWithFile(int $id): bool
    {
        $doc = $this->find($id);
        if (!$doc) return false;

        $this->delete($id);

        $path = FCPATH . $doc['file_path'];
        if (file_exists($path)) unlink($path);

        return true;
    }
}
