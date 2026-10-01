<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pemakaian token per permintaan API — dikunci (ai_session_id, request_id).
 *
 * Tabel terpisah dari entri karena Claude Code menulis SATU baris per blok
 * jawaban (thinking, teks, alat) dan mengulang `usage` yang sama di tiap
 * baris; kunci unik request_id membuang ulangan itu (migrasi §ai_usage).
 *
 * Seperti ai_entries, tabel ini TIDAK punya created_at/updated_at → timestamps
 * dimatikan; `waktu` datang dari transkrip, bukan dari saat baris ditulis.
 */
class AiUsageModel extends Model
{
    protected $table         = 'ai_usage';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'ai_session_id', 'request_id', 'waktu', 'model', 'token_masuk', 'token_keluar',
    ];

    protected $useTimestamps = false;
}
