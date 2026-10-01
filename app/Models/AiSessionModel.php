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
     * Rekap jumlah sesi & prompt per PERANGKAT — kembaran per-karyawan di atas,
     * tapi GROUP BY device_id. Dipakai halaman "Rekap per Komputer", yang
     * menghitung aktivitas per laptop (bukan per orang), sehingga perangkat
     * yang belum ditautkan ke karyawan pun tetap punya angka.
     *
     * Return: [device_id => ['sesi' => int, 'prompt' => int]]
     */
    public function rekapEntriPerPerangkat(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_entries e')
            ->select('s.device_id,
                      COUNT(DISTINCT s.id) AS sesi,
                      SUM(CASE WHEN e.jenis = "prompt" THEN 1 ELSE 0 END) AS prompt', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('s.device_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['device_id']] = [
                'sesi'   => (int) $r['sesi'],
                'prompt' => (int) $r['prompt'],
            ];
        }
        return $map;
    }

    /**
     * Total token (masuk + keluar) per PERANGKAT dalam rentang tanggal usage.
     * Return: [device_id => int]
     */
    public function tokenPerPerangkat(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_usage u')
            ->select('s.device_id, SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->join('ai_sessions s', 's.id = u.ai_session_id')
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai)
            ->groupBy('s.device_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) $map[(int) $r['device_id']] = (int) $r['tok'];
        return $map;
    }

    /**
     * Daftar sesi milik satu PERANGKAT dalam rentang tanggal (berdasar
     * terakhir_at), terbaru dulu. Bentuknya persis byKaryawan(), hanya WHERE-nya
     * berganti ke device_id.
     */
    public function byPerangkat(int $deviceId, string $dari, string $sampai): array
    {
        return $this->select('id, judul, proyek, git_branch, model, mulai_at,
                              terakhir_at, jml_prompt, jml_alat, token_masuk, token_keluar')
            ->where('device_id', $deviceId)
            ->where('DATE(terakhir_at) >=', $dari)
            ->where('DATE(terakhir_at) <=', $sampai)
            ->orderBy('terakhir_at', 'DESC')
            ->findAll();
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

    // ── Agregat untuk Dashboard Pemantauan AI ────────────────────────────

    /** Total sesi & prompt (semua perangkat) dalam rentang. ['sesi','prompt']. */
    public function totalEntriRentang(string $dari, string $sampai): array
    {
        $r = $this->db->table('ai_entries e')
            ->select('COUNT(DISTINCT e.ai_session_id) AS sesi,
                      SUM(CASE WHEN e.jenis = "prompt" THEN 1 ELSE 0 END) AS prompt', false)
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->get()->getRowArray();
        return ['sesi' => (int) ($r['sesi'] ?? 0), 'prompt' => (int) ($r['prompt'] ?? 0)];
    }

    /** Total token (masuk+keluar, semua perangkat) dalam rentang. */
    public function totalTokenRentang(string $dari, string $sampai): int
    {
        $r = $this->db->table('ai_usage u')
            ->select('SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai)
            ->get()->getRowArray();
        return (int) ($r['tok'] ?? 0);
    }

    /** Jumlah prompt per tanggal (semua perangkat). Map 'Y-m-d' => int. */
    public function promptHarian(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_entries e')
            ->select('DATE(e.waktu) AS tgl, COUNT(*) AS n', false)
            ->where('e.jenis', 'prompt')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('DATE(e.waktu)')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[$r['tgl']] = (int) $r['n'];
        return $map;
    }

    /** Total token per tanggal (semua perangkat). Map 'Y-m-d' => int. */
    public function tokenHarian(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_usage u')
            ->select('DATE(u.waktu) AS tgl, SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai)
            ->groupBy('DATE(u.waktu)')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[$r['tgl']] = (int) $r['tok'];
        return $map;
    }

    /** N komputer teratas berdasar jumlah prompt dalam rentang. [{label,prompt}]. */
    public function topKomputer(string $dari, string $sampai, int $limit = 5): array
    {
        $rows = $this->db->table('ai_entries e')
            ->select('dev.label AS label, COUNT(*) AS prompt', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->join('ai_devices dev', 'dev.id = s.device_id', 'left')
            ->where('e.jenis', 'prompt')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('s.device_id')
            ->orderBy('prompt', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
        return array_map(fn($r) => [
            'label'  => $r['label'] ?? '(tanpa label)',
            'prompt' => (int) $r['prompt'],
        ], $rows);
    }

    /**
     * N karyawan teratas berdasar jumlah prompt dalam rentang. [{nama,prompt}].
     * Sesi dari perangkat yang belum ditautkan dikelompokkan 'Tidak tertaut'.
     */
    public function topKaryawan(string $dari, string $sampai, int $limit = 5): array
    {
        $rows = $this->db->table('ai_entries e')
            ->select('s.employee_id AS eid, emp.nama AS nama, COUNT(*) AS prompt', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->join('employees emp', 'emp.id = s.employee_id', 'left')
            ->where('e.jenis', 'prompt')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('s.employee_id')
            ->orderBy('prompt', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
        return array_map(fn($r) => [
            'nama'   => empty($r['eid']) ? 'Tidak tertaut' : ($r['nama'] ?? '(karyawan tak dikenal)'),
            'prompt' => (int) $r['prompt'],
        ], $rows);
    }

    /**
     * N sesi terakhir (urut terakhir_at desc) untuk panel dashboard.
     * [{id,judul,komputer,nama,terakhir_at,jml_prompt}].
     */
    public function sesiTerbaru(int $limit = 10): array
    {
        $rows = $this->db->table('ai_sessions s')
            ->select('s.id, s.judul, s.terakhir_at, s.jml_prompt,
                      dev.label AS komputer, emp.nama AS nama')
            ->join('ai_devices dev', 'dev.id = s.device_id', 'left')
            ->join('employees emp', 'emp.id = s.employee_id', 'left')
            ->orderBy('s.terakhir_at IS NULL', 'ASC', false)
            ->orderBy('s.terakhir_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
        return array_map(fn($r) => [
            'id'          => (int) $r['id'],
            'judul'       => $r['judul'],
            'komputer'    => $r['komputer'] ?? '(tanpa label)',
            'nama'        => $r['nama'] ?: '—',
            'terakhir_at' => $r['terakhir_at'],
            'jml_prompt'  => (int) $r['jml_prompt'],
        ], $rows);
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
