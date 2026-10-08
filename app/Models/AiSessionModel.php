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
    /**
     * Ambang pemakaian untuk kantor. Bila porsi sesi berklasifikasi "kantor"
     * dalam satu periode turun di bawah angka ini (persen), analisa & laporan
     * memberi penanda peringatan. Dipakai bersama oleh controller & view.
     */
    public const AMBANG_KANTOR = 70;

    protected $table         = 'ai_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'device_id', 'employee_id', 'session_uuid', 'judul', 'cwd', 'proyek',
        'git_branch', 'model', 'versi_cc', 'mulai_at', 'terakhir_at',
        'jml_prompt', 'jml_alat', 'token_masuk', 'token_keluar',
        // Klasifikasi sesi (migrasi 2026-10-02-000004).
        'klasifikasi_jenis', 'klasifikasi_tema', 'klasifikasi_kantor',
        'klasifikasi_metode', 'klasifikasi_at',
        // Ringkasan sesi (migrasi 2026-10-03-000001).
        'ringkasan',
        // Skor mutu prompt (migrasi 2026-10-09-000001).
        'skor_prompt', 'skor_rincian', 'skor_saran', 'skor_metode', 'skor_at', 'skor_model',
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
        return $this->select('id, judul, ringkasan, klasifikasi_jenis, klasifikasi_tema, klasifikasi_kantor,
                              proyek, git_branch, model, mulai_at,
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
        return $this->select('id, judul, ringkasan, klasifikasi_jenis, klasifikasi_tema, klasifikasi_kantor,
                              skor_prompt, skor_rincian, skor_saran, skor_metode, skor_model,
                              proyek, git_branch, model, mulai_at,
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
            ->select('s.id, s.judul, s.ringkasan, s.klasifikasi_tema, s.terakhir_at, s.jml_prompt,
                      s.klasifikasi_jenis, s.klasifikasi_kantor,
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
            'ringkasan'   => $r['ringkasan'] ?? null,
            'tema'        => $r['klasifikasi_tema'] ?? null,
            'komputer'    => $r['komputer'] ?? '(tanpa label)',
            'nama'        => $r['nama'] ?: '—',
            'terakhir_at' => $r['terakhir_at'],
            'jml_prompt'  => (int) $r['jml_prompt'],
            'jenis'       => $r['klasifikasi_jenis'],
            'kantor'      => $r['klasifikasi_kantor'],
        ], $rows);
    }

    /**
     * Jumlah sesi per jenis klasifikasi dalam rentang (DATE(terakhir_at)).
     * Sesi yang belum terklasifikasi dikelompokkan 'Belum'. Map jenis => int.
     */
    public function jenisCounts(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_sessions')
            ->select('COALESCE(klasifikasi_jenis, "Belum") AS jenis, COUNT(*) AS n', false)
            ->where('DATE(terakhir_at) >=', $dari)
            ->where('DATE(terakhir_at) <=', $sampai)
            ->groupBy('klasifikasi_jenis')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[$r['jenis']] = (int) $r['n'];
        return $map;
    }

    /** N tema teratas berdasar jumlah sesi dalam rentang. [{tema,jumlah}]. */
    public function temaTop(string $dari, string $sampai, int $limit = 5): array
    {
        $rows = $this->db->table('ai_sessions')
            ->select('klasifikasi_tema AS tema, COUNT(*) AS jumlah', false)
            ->where('DATE(terakhir_at) >=', $dari)
            ->where('DATE(terakhir_at) <=', $sampai)
            ->where('klasifikasi_tema IS NOT NULL', null, false)
            ->groupBy('klasifikasi_tema')
            ->orderBy('jumlah', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
        return array_map(fn($r) => ['tema' => $r['tema'], 'jumlah' => (int) $r['jumlah']], $rows);
    }

    /**
     * Jumlah sesi per kategori kantor/pribadi dalam rentang (DATE(terakhir_at)).
     * Sesi belum terklasifikasi → 'Belum'. Map kategori => int.
     */
    public function kantorCounts(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_sessions')
            ->select('COALESCE(klasifikasi_kantor, "Belum") AS kat, COUNT(*) AS n', false)
            ->where('DATE(terakhir_at) >=', $dari)
            ->where('DATE(terakhir_at) <=', $sampai)
            ->groupBy('klasifikasi_kantor')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[$r['kat']] = (int) $r['n'];
        return $map;
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

    // ── Agregat BER-SCOPE + periode (per karyawan / per komputer / global) ─
    //
    // $scope: []  → global (semua perangkat/karyawan)
    //         ['employee_id' => N] → hanya sesi milik karyawan itu
    //         ['device_id'   => N] → hanya sesi dari perangkat itu
    //
    // Klasifikasi (jenis/tema/kantor) disaring DATE(terakhir_at) seperti
    // panel dashboard; total sesi/prompt dari ai_entries (DATE waktu entri)
    // dan token dari ai_usage (DATE waktu usage), konsisten metode lama.

    /** Terjemahkan $scope → [kolom, nilai] pada alias tabel ai_sessions, atau [null,null]. */
    private function scopeKolom(array $scope): array
    {
        if (isset($scope['employee_id'])) return ['employee_id', (int) $scope['employee_id']];
        if (isset($scope['device_id']))   return ['device_id',   (int) $scope['device_id']];
        return [null, null];
    }

    /**
     * Semua agregat yang dibutuhkan satu panel analisa / laporan untuk satu
     * scope dalam rentang [dari,sampai]. Return:
     *   jenis  => map jenis => int  (null → 'Belum')
     *   tema   => [{tema,jumlah}]   (klasifikasi_tema not null, 5 teratas)
     *   kantor => map kategori => int (null → 'Belum')
     *   total  => ['sesi','prompt','token']
     *   tren   => [{tgl,label(DD/MM),prompt,token}] untuk TIAP hari dlm rentang
     *   pct_kantor => int|null  (porsi sesi "kantor"; null bila tak ada sesi)
     */
    public function analisa(string $dari, string $sampai, array $scope = []): array
    {
        [$col, $val] = $this->scopeKolom($scope);

        // ── Jenis (ai_sessions, DATE(terakhir_at)) ──
        $b = $this->db->table('ai_sessions s')
            ->select('COALESCE(s.klasifikasi_jenis, "Belum") AS jenis, COUNT(*) AS n', false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->groupBy('s.klasifikasi_jenis');
        if ($col) $b->where('s.' . $col, $val);
        $jenis = [];
        foreach ($b->get()->getResultArray() as $r) $jenis[$r['jenis']] = (int) $r['n'];

        // ── Kantor vs pribadi ──
        $b = $this->db->table('ai_sessions s')
            ->select('COALESCE(s.klasifikasi_kantor, "Belum") AS kat, COUNT(*) AS n', false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->groupBy('s.klasifikasi_kantor');
        if ($col) $b->where('s.' . $col, $val);
        $kantor = [];
        foreach ($b->get()->getResultArray() as $r) $kantor[$r['kat']] = (int) $r['n'];

        // ── Tema (5 teratas, not null) ──
        $b = $this->db->table('ai_sessions s')
            ->select('s.klasifikasi_tema AS tema, COUNT(*) AS jumlah', false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->where('s.klasifikasi_tema IS NOT NULL', null, false)
            ->groupBy('s.klasifikasi_tema')
            ->orderBy('jumlah', 'DESC')
            ->limit(5);
        if ($col) $b->where('s.' . $col, $val);
        $tema = array_map(fn($r) => ['tema' => $r['tema'], 'jumlah' => (int) $r['jumlah']],
            $b->get()->getResultArray());

        // ── Total sesi & prompt (ai_entries, DATE(waktu)) ──
        $b = $this->db->table('ai_entries e')
            ->select('COUNT(DISTINCT e.ai_session_id) AS sesi,
                      SUM(CASE WHEN e.jenis = "prompt" THEN 1 ELSE 0 END) AS prompt', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai);
        if ($col) $b->where('s.' . $col, $val);
        $rt = $b->get()->getRowArray();

        // ── Total token (ai_usage, DATE(waktu)) ──
        $b = $this->db->table('ai_usage u')
            ->select('SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->join('ai_sessions s', 's.id = u.ai_session_id')
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai);
        if ($col) $b->where('s.' . $col, $val);
        $tok = (int) ($b->get()->getRowArray()['tok'] ?? 0);

        // ── Tren harian: prompt per hari + token per hari, isi celah dgn 0 ──
        $b = $this->db->table('ai_entries e')
            ->select('DATE(e.waktu) AS tgl, COUNT(*) AS n', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->where('e.jenis', 'prompt')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->groupBy('DATE(e.waktu)');
        if ($col) $b->where('s.' . $col, $val);
        $pmap = [];
        foreach ($b->get()->getResultArray() as $r) $pmap[$r['tgl']] = (int) $r['n'];

        $b = $this->db->table('ai_usage u')
            ->select('DATE(u.waktu) AS tgl, SUM(u.token_masuk + u.token_keluar) AS tok', false)
            ->join('ai_sessions s', 's.id = u.ai_session_id')
            ->where('DATE(u.waktu) >=', $dari)
            ->where('DATE(u.waktu) <=', $sampai)
            ->groupBy('DATE(u.waktu)');
        if ($col) $b->where('s.' . $col, $val);
        $tmap = [];
        foreach ($b->get()->getResultArray() as $r) $tmap[$r['tgl']] = (int) $r['tok'];

        $tren = [];
        $t = $dari;
        $guard = 0;
        while ($t <= $sampai && $guard++ < 400) {
            $tren[] = [
                'tgl'    => $t,
                'label'  => date('d/m', strtotime($t)),
                'prompt' => $pmap[$t] ?? 0,
                'token'  => $tmap[$t] ?? 0,
            ];
            $t = date('Y-m-d', strtotime($t . ' +1 day'));
        }

        // %kantor dihitung dari sesi yang BISA DIPASTIKAN saja (kantor + pribadi);
        // 'tak_jelas' & 'Belum' dikeluarkan agar persentase tak tertarik turun
        // oleh sesi yang sekadar belum jelas — bukan karena non-kerja.
        $kDasar    = ($kantor['kantor'] ?? 0) + ($kantor['pribadi'] ?? 0);
        $pctKantor = $kDasar > 0 ? (int) round(($kantor['kantor'] ?? 0) / $kDasar * 100) : null;

        return [
            'jenis'  => $jenis,
            'tema'   => $tema,
            'kantor' => $kantor,
            'total'  => [
                'sesi'   => (int) ($rt['sesi'] ?? 0),
                'prompt' => (int) ($rt['prompt'] ?? 0),
                'token'  => $tok,
            ],
            'tren'       => $tren,
            'pct_kantor' => $pctKantor,
        ];
    }

    /** Jumlah komputer (perangkat) berbeda yang beraktivitas dalam rentang. */
    public function jmlKomputerAktif(string $dari, string $sampai): int
    {
        $r = $this->db->table('ai_entries e')
            ->select('COUNT(DISTINCT s.device_id) AS n', false)
            ->join('ai_sessions s', 's.id = e.ai_session_id')
            ->where('DATE(e.waktu) >=', $dari)
            ->where('DATE(e.waktu) <=', $sampai)
            ->get()->getRowArray();
        return (int) ($r['n'] ?? 0);
    }

    /**
     * Daftar sesi (dengan klasifikasi) milik satu scope dalam rentang, untuk
     * tabel daftar sesi di laporan cetak individu. Urut terbaru dulu.
     * [{id,judul,proyek,klasifikasi_jenis,klasifikasi_tema,klasifikasi_kantor,
     *   terakhir_at,jml_prompt,token_masuk,token_keluar}]
     */
    public function sesiRentangScope(string $dari, string $sampai, array $scope = []): array
    {
        [$col, $val] = $this->scopeKolom($scope);
        $b = $this->db->table('ai_sessions s')
            ->select('s.id, s.judul, s.ringkasan, s.proyek, s.klasifikasi_jenis, s.klasifikasi_tema,
                      s.klasifikasi_kantor, s.mulai_at, s.terakhir_at, s.jml_prompt,
                      s.token_masuk, s.token_keluar')
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->orderBy('s.terakhir_at', 'DESC');
        if ($col) $b->where('s.' . $col, $val);
        return $b->get()->getResultArray();
    }

    /**
     * Rekap per karyawan untuk laporan bulanan scope global. Satu baris per
     * karyawan yang punya aktivitas dalam rentang:
     *   [{employee_id,nama,dept,sesi,prompt,token,jenis_dominan,pct_kantor}]
     * Urut jumlah prompt menurun. Sesi tanpa karyawan (perangkat belum
     * ditautkan) dikelompokkan sebagai "Tidak tertaut".
     */
    public function rekapKaryawanRentang(string $dari, string $sampai): array
    {
        $entri = $this->rekapEntriPerKaryawan($dari, $sampai); // [eid => sesi,prompt]
        $token = $this->tokenPerKaryawan($dari, $sampai);      // [eid => token]

        // Jenis dominan per karyawan (abaikan yang belum terklasifikasi).
        $rows = $this->db->table('ai_sessions s')
            ->select('s.employee_id AS eid, s.klasifikasi_jenis AS jenis, COUNT(*) AS n', false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->where('s.klasifikasi_jenis IS NOT NULL', null, false)
            ->groupBy('s.employee_id, s.klasifikasi_jenis')
            ->get()->getResultArray();
        $dominan = [];
        foreach ($rows as $r) {
            $eid = (int) $r['eid'];
            if (! isset($dominan[$eid]) || $r['n'] > $dominan[$eid]['n']) {
                $dominan[$eid] = ['jenis' => $r['jenis'], 'n' => (int) $r['n']];
            }
        }

        // Porsi kantor per karyawan.
        $rows = $this->db->table('ai_sessions s')
            ->select('s.employee_id AS eid, COUNT(*) AS tot,
                      SUM(CASE WHEN s.klasifikasi_kantor = "kantor" THEN 1 ELSE 0 END) AS kantor', false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->groupBy('s.employee_id')
            ->get()->getResultArray();
        $pct = [];
        foreach ($rows as $r) {
            $eid = (int) $r['eid'];
            $tot = (int) $r['tot'];
            $pct[$eid] = $tot > 0 ? (int) round((int) $r['kantor'] / $tot * 100) : null;
        }

        // Nama & departemen.
        $ids = array_values(array_unique(array_filter(array_keys($entri))));
        $nama = [];
        if ($ids) {
            $erows = $this->db->table('employees e')
                ->select('e.id, e.nama, d.name AS dept')
                ->join('departments d', 'd.id = e.dept_id', 'left')
                ->whereIn('e.id', $ids)
                ->get()->getResultArray();
            foreach ($erows as $e) $nama[(int) $e['id']] = $e;
        }

        $out = [];
        foreach ($entri as $eid => $v) {
            $eid = (int) $eid;
            $out[] = [
                'employee_id'   => $eid,
                'nama'          => $eid === 0 ? 'Tidak tertaut' : ($nama[$eid]['nama'] ?? '(karyawan tak dikenal)'),
                'dept'          => $eid === 0 ? '—' : ($nama[$eid]['dept'] ?? '—'),
                'sesi'          => (int) $v['sesi'],
                'prompt'        => (int) $v['prompt'],
                'token'         => (int) ($token[$eid] ?? 0),
                'jenis_dominan' => $dominan[$eid]['jenis'] ?? null,
                'pct_kantor'    => $pct[$eid] ?? null,
            ];
        }
        usort($out, fn($a, $b) => $b['prompt'] <=> $a['prompt']);
        return $out;
    }

    // ── Skor mutu prompt ─────────────────────────────────────────────────
    //
    // Hanya sesi dengan skor_metode='llm' (dinilai AI) yang dihitung. Sesi
    // pribadi tidak pernah dihitung. Sesi layak yang belum dinilai dilaporkan
    // terpisah sebagai "belum dinilai" — tidak masuk rata-rata/tren.

    /** Kondisi SQL sesi TERNILAI (alias s). */
    private const SQL_TERNILAI = "s.skor_metode = 'llm' AND s.skor_prompt IS NOT NULL AND COALESCE(s.klasifikasi_kantor,'') <> 'pribadi'";

    /** Kondisi SQL sesi LAYAK tapi BELUM dinilai AI (alias s). */
    private const SQL_BELUM = "s.skor_metode IS NULL AND s.klasifikasi_kantor IS NOT NULL AND s.klasifikasi_kantor <> 'pribadi' AND s.jml_prompt >= 2";

    /**
     * Ringkasan skor satu scope dalam rentang.
     * Return: n (sesi ternilai), rata (float|null), dimensi (rata per dimensi),
     * tingkat (kunci => jumlah), tak_jelas (sesi ternilai bertanda tak_jelas),
     * belum (sesi layak belum dinilai AI).
     */
    public function skorRingkas(string $dari, string $sampai, array $scope = []): array
    {
        [$col, $val] = $this->scopeKolom($scope);

        $b = $this->db->table('ai_sessions s')
            ->select('s.skor_prompt, s.skor_rincian, s.klasifikasi_kantor')
            ->where(self::SQL_TERNILAI, null, false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai);
        if ($col) $b->where('s.' . $col, $val);
        $rows = $b->get()->getResultArray();

        $dimJml = array_fill_keys(array_keys(\App\Libraries\AiSkorPrompt::DIMENSI), 0);
        $tingkat = ['perlu_dilatih' => 0, 'cukup' => 0, 'baik' => 0, 'sangat_baik' => 0];
        $total = 0; $tak = 0;
        foreach ($rows as $r) {
            $sk = (int) $r['skor_prompt'];
            $total += $sk;
            $tingkat[\App\Libraries\AiSkorPrompt::tingkat($sk)[0]]++;
            if ($r['klasifikasi_kantor'] === 'tak_jelas') $tak++;
            $rin = json_decode((string) $r['skor_rincian'], true) ?: [];
            foreach ($dimJml as $k => $_) $dimJml[$k] += (int) ($rin[$k] ?? 0);
        }
        $n = count($rows);

        $b = $this->db->table('ai_sessions s')
            ->select('COUNT(*) AS n', false)
            ->where(self::SQL_BELUM, null, false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai);
        if ($col) $b->where('s.' . $col, $val);
        $belum = (int) ($b->get()->getRowArray()['n'] ?? 0);

        return [
            'n'         => $n,
            'rata'      => $n ? round($total / $n, 1) : null,
            'dimensi'   => array_map(fn($v) => $n ? round($v / $n, 1) : null, $dimJml),
            'tingkat'   => $tingkat,
            'tak_jelas' => $tak,
            'belum'     => $belum,
        ];
    }

    /**
     * Tren skor rata-rata per minggu ISO (Senin) selama $minggu minggu terakhir
     * sampai $sampai. Minggu tanpa sesi ternilai = rata null (celah di grafik).
     * [{awal:'Y-m-d', label:'dd/mm', n:int, rata:float|null}]
     */
    public function skorTrenMingguan(string $sampai, int $minggu = 12, array $scope = []): array
    {
        [$col, $val] = $this->scopeKolom($scope);
        $senin = date('Y-m-d', strtotime('monday this week', strtotime($sampai)));
        $awal  = date('Y-m-d', strtotime($senin . ' -' . ($minggu - 1) . ' weeks'));

        $b = $this->db->table('ai_sessions s')
            ->select('s.skor_prompt AS sk, DATE(s.terakhir_at) AS tgl', false)
            ->where(self::SQL_TERNILAI, null, false)
            ->where('DATE(s.terakhir_at) >=', $awal)
            ->where('DATE(s.terakhir_at) <=', $sampai);
        if ($col) $b->where('s.' . $col, $val);

        $bucket = [];
        for ($i = 0; $i < $minggu; $i++) {
            $k = date('Y-m-d', strtotime($awal . " +{$i} weeks"));
            $bucket[$k] = [0, 0];
        }
        foreach ($b->get()->getResultArray() as $r) {
            $k = date('Y-m-d', strtotime('monday this week', strtotime($r['tgl'])));
            if (isset($bucket[$k])) { $bucket[$k][0]++; $bucket[$k][1] += (int) $r['sk']; }
        }
        $out = [];
        foreach ($bucket as $k => [$n, $sum]) {
            $out[] = ['awal' => $k, 'label' => date('d/m', strtotime($k)), 'n' => $n,
                      'rata' => $n ? round($sum / $n, 1) : null];
        }
        return $out;
    }

    /**
     * Skor per karyawan untuk dashboard: periode ini vs periode sebelumnya
     * (panjang sama, tepat sebelum $dari). Karyawan tampil dengan rata hanya
     * bila n >= MIN_SESI_TAMPIL; selain itu rata = null ("Data belum cukup").
     * [{employee_id,nama,dept,n,rata,rata_lalu,selisih,belum}]
     */
    public function skorPerKaryawan(string $dari, string $sampai): array
    {
        $hari   = (int) round((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
        $dariL  = date('Y-m-d', strtotime($dari . " -{$hari} days"));
        $sampaiL = date('Y-m-d', strtotime($dari . ' -1 day'));
        $min    = \App\Libraries\AiSkorPrompt::MIN_SESI_TAMPIL;

        $hitung = function (string $a, string $z): array {
            $rows = $this->db->table('ai_sessions s')
                ->select('s.employee_id, COUNT(*) AS n, AVG(s.skor_prompt) AS rata', false)
                ->where(self::SQL_TERNILAI, null, false)
                ->where('s.employee_id IS NOT NULL', null, false)
                ->where('DATE(s.terakhir_at) >=', $a)
                ->where('DATE(s.terakhir_at) <=', $z)
                ->groupBy('s.employee_id')->get()->getResultArray();
            $m = [];
            foreach ($rows as $r) $m[(int) $r['employee_id']] = ['n' => (int) $r['n'], 'rata' => (float) $r['rata']];
            return $m;
        };
        $kini = $hitung($dari, $sampai);
        $lalu = $hitung($dariL, $sampaiL);

        $belum = [];
        foreach ($this->db->table('ai_sessions s')
            ->select('s.employee_id, COUNT(*) AS n', false)
            ->where(self::SQL_BELUM, null, false)
            ->where('s.employee_id IS NOT NULL', null, false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->groupBy('s.employee_id')->get()->getResultArray() as $r) {
            $belum[(int) $r['employee_id']] = (int) $r['n'];
        }

        $ids = array_unique(array_merge(array_keys($kini), array_keys($belum)));
        if (! $ids) return [];
        $emp = [];
        foreach ($this->db->table('employees e')->select('e.id, e.nama, d.name AS dept')
            ->join('departments d', 'd.id = e.dept_id', 'left')->whereIn('e.id', $ids)->get()->getResultArray() as $r) {
            $emp[(int) $r['id']] = $r;
        }

        $out = [];
        foreach ($ids as $id) {
            $n    = $kini[$id]['n'] ?? 0;
            $rata = ($n >= $min) ? round($kini[$id]['rata'], 1) : null;
            $nl   = $lalu[$id]['n'] ?? 0;
            $rl   = ($nl >= $min) ? round($lalu[$id]['rata'], 1) : null;
            $out[] = [
                'employee_id' => $id,
                'nama'        => $emp[$id]['nama'] ?? '(karyawan tak dikenal)',
                'dept'        => $emp[$id]['dept'] ?? '-',
                'n'           => $n,
                'rata'        => $rata,
                'rata_lalu'   => $rl,
                'selisih'     => ($rata !== null && $rl !== null) ? round($rata - $rl, 1) : null,
                'belum'       => $belum[$id] ?? 0,
            ];
        }
        usort($out, fn($a, $b) => strcasecmp($a['nama'], $b['nama'])); // urut abjad, bukan peringkat
        return $out;
    }

    /**
     * Skor per MODEL pemberi skor (sesi dinilai AI, bukan pribadi) dalam rentang.
     * Untuk memeriksa konsistensi antar-model. Sesi lama tanpa skor_model
     * dikelompokkan sebagai "(tak tercatat)".
     * [{model, n, rata, dimensi:{...}}] urut jumlah sesi menurun.
     */
    public function skorPerModel(string $dari, string $sampai): array
    {
        $rows = $this->db->table('ai_sessions s')
            ->select('COALESCE(s.skor_model, "(tak tercatat)") AS model, s.skor_prompt, s.skor_rincian', false)
            ->where(self::SQL_TERNILAI, null, false)
            ->where('DATE(s.terakhir_at) >=', $dari)
            ->where('DATE(s.terakhir_at) <=', $sampai)
            ->get()->getResultArray();

        $dimKunci = array_keys(\App\Libraries\AiSkorPrompt::DIMENSI);
        $g = [];
        foreach ($rows as $r) {
            $m = $r['model'];
            $g[$m] ??= ['model' => $m, 'n' => 0, 'jml' => 0, 'dim' => array_fill_keys($dimKunci, 0)];
            $g[$m]['n']++;
            $g[$m]['jml'] += (int) $r['skor_prompt'];
            $rin = json_decode((string) $r['skor_rincian'], true) ?: [];
            foreach ($dimKunci as $k) $g[$m]['dim'][$k] += (int) ($rin[$k] ?? 0);
        }
        $out = [];
        foreach ($g as $m) {
            $out[] = [
                'model'   => $m['model'],
                'n'       => $m['n'],
                'rata'    => round($m['jml'] / $m['n'], 1),
                'dimensi' => array_map(fn($v) => round($v / $m['n'], 1), $m['dim']),
            ];
        }
        usort($out, fn($a, $b) => $b['n'] <=> $a['n']);
        return $out;
    }
}
