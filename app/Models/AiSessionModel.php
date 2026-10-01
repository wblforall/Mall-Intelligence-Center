<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sesi Claude Code — satu baris per (device_id, session_uuid).
 *
 * Angka agregat (jml_prompt, jml_alat, token_*) ikut disimpan di sini dan
 * dihitung ulang tiap kiriman dari tabel anak, supaya halaman rekap harian
 * tak perlu menjumlah ribuan baris entri (lihat migrasi §ai_sessions).
 */
class AiSessionModel extends Model
{
    protected $table         = 'ai_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'device_id', 'employee_id', 'session_uuid', 'judul', 'cwd', 'proyek',
        'git_branch', 'model', 'versi_cc', 'mulai_at', 'terakhir_at',
        'jml_prompt', 'jml_alat', 'token_masuk', 'token_keluar',
    ];

    // Tabel ini punya created_at & updated_at → timestamps dinyalakan.
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /**
     * Rekap jumlah sesi & prompt per karyawan dalam rentang tanggal entri.
     *
     * Disaring lewat DATE(e.waktu) (stempel ENTRI), bukan terakhir_at sesi,
     * karena satu sesi bisa melewati tengah malam — rekap harian menyaring
     * entri, bukan sesi (lihat migrasi §ai_entries).
     *
     * Return: [employee_id => ['sesi' => int, 'prompt' => int]]
     */
    public function rekapEntriPerKaryawan(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_entries e')
            ->select('s.employee_id,
                      COUNT(DISTINCT s.id) AS sesi,
                      SUM(CASE WHEN e.jenis = "prompt" THEN 1 ELSE 0 END) AS prompt', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('s.employee_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['employee_id']] = [
                'sesi'   => (int) $r['sesi'],
                'prompt' => (int) $r['prompt'],
            ];
        }
        return $map;
    }

    /**
     * Total token (masuk + keluar) per karyawan dalam rentang tanggal usage.
     * Return: [employee_id => int]
     */
    public function tokenPerKaryawan(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_usage u')
            ->select('s.employee_id, SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->join('ai_sessions s', 's.id = u.ai_session_id')
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai)
            ->groupBy('s.employee_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) $map[(int) $r['employee_id']] = (int) $r['tok'];
        return $map;
    }

    /**
     * Daftar sesi milik satu karyawan dalam rentang tanggal (berdasar
     * terakhir_at), terbaru dulu. Kolomnya persis yang dipakai view karyawan.
     */
    public function byKaryawan(int $employeeId, string $dari, string $sampai): array
    {
        return $this->select('id, judul, proyek, git_branch, model, mulai_at,
                              terakhir_at, jml_prompt, jml_alat, token_masuk, token_keluar')
            ->where('employee_id', $employeeId)
            ->where('DATE(terakhir_at) >=', $dari)
            ->where('DATE(terakhir_at) <=', $sampai)
            ->orderBy('terakhir_at', 'DESC')
            ->findAll();
    }

    /** Satu sesi + nama karyawan pemiliknya, untuk halaman transkrip. */
    public function detail(int $sessionId): ?array
    {
        return $this->db->table('ai_sessions s')
            ->select('s.*, emp.nama AS nama')
            ->join('employees emp', 'emp.id = s.employee_id', 'left')
            ->where('s.id', $sessionId)
            ->get()->getRowArray();
    }
}
