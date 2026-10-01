<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Entri transkrip — satu baris prompt / balasan / alat, dikunci (ai_session_id,
 * uuid). `waktu` per entri (bukan per sesi) karena satu sesi bisa melewati
 * tengah malam dan rekap harian menyaring entri, bukan sesi.
 *
 * Tabel ini TIDAK punya kolom created_at/updated_at (migrasi §ai_entries):
 * `waktu` adalah stempel aslinya dari transkrip laptop, jadi timestamps CI4
 * sengaja dimatikan agar tak ada kolom hantu yang coba diisi.
 */
class AiEntryModel extends Model
{
    protected $table         = 'ai_entries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'ai_session_id', 'uuid', 'jenis', 'waktu', 'alat', 'sasaran', 'isi',
    ];

    protected $useTimestamps = false;

    /**
     * Semua entri satu sesi, urut waktu naik — transkrip dibaca dari atas ke
     * bawah seperti percakapan aslinya. Kolomnya persis yang dipakai view sesi.
     */
    public function bySesi(int $sessionId): array
    {
        return $this->select('jenis, waktu, alat, sasaran, isi')
            ->where('ai_session_id', $sessionId)
            ->orderBy('waktu', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
