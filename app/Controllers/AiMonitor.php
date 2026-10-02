<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\AiCopotPassword;
use App\Libraries\AiEnrollKey;
use App\Models\AiDeviceModel;
use App\Models\AiEntryModel;
use App\Models\AiSessionModel;

/**
 * Pemantauan AI — halaman LIHAT pemakaian Claude Code tim.
 *
 * Pemantauan ini TERBUKA: tim diberi tahu, skrip laptop tak disembunyikan dan
 * bisa dimatikan pemakainya (lihat AiLog & public/ai-monitor/). Siapa yang
 * boleh membuka halaman ini diatur lewat hak akses menu 'ai_monitor', sama
 * seperti menu standalone lain.
 *
 * Membuka transkrip = membaca percakapan orang, maka method sesi() mencatat
 * SIAPA yang melihat lewat ActivityLog — pemantau pun ikut terpantau.
 */
class AiMonitor extends BaseController
{
    private const MENU = 'ai_monitor';

    /** Zona waktu operasional MIC — "hari ini" mengikuti jam Balikpapan. */
    private const TZ = 'Asia/Makassar';

    private AiSessionModel $sessions;

    public function __construct()
    {
        $this->sessions = new AiSessionModel();
    }

    /** Tanggal hari ini menurut zona waktu operasional. */
    private function hariIni(): string
    {
        return (new \DateTime('now', new \DateTimeZone(self::TZ)))->format('Y-m-d');
    }

    /** YYYY-MM yang sah atau null (kosong/ngawur → null → pakai default). */
    private function bulanSah(string $bulan): ?string
    {
        if ($bulan === '') return null;
        $d = \DateTime::createFromFormat('Y-m', $bulan);
        return ($d && $d->format('Y-m') === $bulan) ? $bulan : null;
    }

    /**
     * Resolusi pemilih PERIODE dari query string:
     *   ?periode=7h|30h|bulan  (+ ?bulan=YYYY-MM saat periode=bulan)
     * Kembalikan ['dari','sampai','label','periode','bulan'] — 'bulan' selalu
     * terisi (dipakai tombol cetak), default periode '7h'. Bulan ngawur →
     * bulan berjalan (menurut Asia/Makassar). Rentang bulan = tgl 1 s/d akhir.
     */
    private function resolvePeriode(): array
    {
        helper('tanggal');
        $today     = $this->hariIni();
        $periode   = (string) $this->request->getGet('periode');
        $bulan     = $this->bulanSah((string) $this->request->getGet('bulan'))
            ?? substr($today, 0, 7);

        if ($periode === 'bulan') {
            $dari   = $bulan . '-01';
            $sampai = date('Y-m-t', strtotime($dari));
            $label  = bulan_indo((int) substr($bulan, 5, 2)) . ' ' . substr($bulan, 0, 4);
            return compact('dari', 'sampai', 'label', 'periode', 'bulan');
        }
        if ($periode === '30h') {
            return [
                'dari'    => date('Y-m-d', strtotime($today . ' -29 days')),
                'sampai'  => $today,
                'label'   => '30 hari terakhir',
                'periode' => '30h',
                'bulan'   => $bulan,
            ];
        }
        return [
            'dari'    => date('Y-m-d', strtotime($today . ' -6 days')),
            'sampai'  => $today,
            'label'   => '7 hari terakhir',
            'periode' => '7h',
            'bulan'   => $bulan,
        ];
    }

    /**
     * RINGKASAN KESELURUHAN (agregat) untuk satu scope pada periode aktif.
     *
     * Mensintesis seluruh ringkasan per-sesi menjadi SATU paragraf lewat
     * AiKlasifikasi::ringkasanPeriode(). Dibungkus cache CI4 (TTL 1 jam, key
     * memuat rentang) agar refresh halaman tak memanggil AI berulang; null
     * (AI gagal/tak dikonfigurasi) ikut di-cache sebagai '' supaya tak dicoba
     * terus. Pemanggil yang menerima null menampilkan fallback rule-based.
     *
     * @param array $sessions Daftar sesi (byKaryawan/byPerangkat/sesiRentangScope):
     *                        tiap baris punya judul, ringkasan, klasifikasi_*.
     * @param array $analisa  Hasil AiSessionModel::analisa() (total, jenis, pct_kantor).
     */
    private function ringkasanPeriode(array $sessions, array $analisa, string $nama, string $labelPeriode, string $cacheKey): ?string
    {
        $cache = \Config\Services::cache();
        $hit   = $cache->get($cacheKey);
        if ($hit !== null) {
            return $hit === '' ? null : $hit;   // '' = sudah dicoba, hasil kosong
        }

        $items = [];
        foreach ($sessions as $s) {
            $items[] = [
                'judul'     => $s['judul'] ?? '',
                'ringkasan' => $s['ringkasan'] ?? '',
                'jenis'     => $s['klasifikasi_jenis'] ?? '',
                'tema'      => $s['klasifikasi_tema'] ?? '',
                'kantor'    => $s['klasifikasi_kantor'] ?? '',
            ];
        }

        $meta = [
            'nama'          => $nama,
            'label_periode' => $labelPeriode,
            'total_sesi'    => $analisa['total']['sesi'] ?? 0,
            'total_prompt'  => $analisa['total']['prompt'] ?? 0,
            'jenis_dominan' => $this->jenisDominan($analisa['jenis'] ?? []),
            'pct_kantor'    => $analisa['pct_kantor'] ?? null,
        ];

        $hasil = \App\Libraries\AiKlasifikasi::ringkasanPeriode($items, $meta);
        $cache->save($cacheKey, $hasil ?? '', 3600);
        return $hasil;
    }

    /** Jenis dengan jumlah sesi tertinggi (abaikan 'Belum'); null bila kosong. */
    private function jenisDominan(array $jenis): ?string
    {
        unset($jenis['Belum']);
        if ($jenis === []) return null;
        arsort($jenis);
        return (string) array_key_first($jenis);
    }

    // ── Dashboard overview ───────────────────────────────────────────────

    public function dashboard()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $today = $this->hariIni();
        $p     = $this->resolvePeriode();            // pemilih periode (default 7h)
        $dari  = $p['dari'];
        $sampai = $p['sampai'];

        $sess = $this->sessions;

        // ── Status perangkat (satu aturan, lihat statusPerangkat) ────────
        $devices = db_connect()->table('ai_devices')
            ->select('aktif, disetujui_at, diblokir')->get()->getResultArray();
        $statusCounts = ['aktif' => 0, 'pending' => 0, 'diblokir' => 0, 'nonaktif' => 0];
        foreach ($devices as $d) {
            $statusCounts[$this->statusPerangkat($d)]++;
        }
        $totalKomputer = count($devices);

        // ── Agregat global untuk periode terpilih (KPI, tren, klasifikasi) ─
        $agg     = $sess->analisa($dari, $sampai);   // scope global
        $hariIni = $sess->totalEntriRentang($today, $today);

        $data = [
            'tanggal'  => $today,
            'periode'  => $p,
            'kpi' => [
                'total_komputer'    => $totalKomputer,
                'komputer_aktif'    => $statusCounts['aktif'],
                'komputer_pending'  => $statusCounts['pending'],
                'komputer_diblokir' => $statusCounts['diblokir'],
                'prompt_hari_ini'   => $hariIni['prompt'],
                'prompt_periode'    => $agg['total']['prompt'],
                'sesi_periode'      => $agg['total']['sesi'],
                'token_periode'     => $agg['total']['token'],
            ],
            'deret'         => $agg['tren'],
            'pct_kantor'    => $agg['pct_kantor'],
            'ambang_kantor' => AiSessionModel::AMBANG_KANTOR,
            'status_counts' => $statusCounts,
            'top_komputer'  => $sess->topKomputer($dari, $sampai, 5),
            'top_karyawan'  => $sess->topKaryawan($dari, $sampai, 5),
            'sesi_terbaru'  => $sess->sesiTerbaru(10),
            // Klasifikasi sesi pada periode terpilih.
            'klas_jenis'  => $agg['jenis'],
            'klas_tema'   => $agg['tema'],
            'klas_kantor' => $agg['kantor'],
        ];

        return view('ai_monitor/dashboard', $data);
    }

    // ── Rekap per karyawan ───────────────────────────────────────────────

    public function index()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $today     = $this->hariIni();
        $weekStart = date('Y-m-d', strtotime($today . ' -6 days')); // 7 hari inklusif

        // Satu baris per karyawan pemilik perangkat. Perangkat sudah terurut
        // "paling baru melapor dulu", jadi baris pertama tiap karyawan adalah
        // laptop aktifnya — laptop lama yang senyap tak menggeser labelnya.
        $perKaryawan = [];
        foreach ((new AiDeviceModel())->rekapKaryawan() as $d) {
            $eid = (int) $d['employee_id'];
            if (isset($perKaryawan[$eid])) continue;
            $perKaryawan[$eid] = [
                'employee_id'  => $eid,
                'nama'         => $d['nama'] ?? '(karyawan tak dikenal)',
                'dept'         => $d['dept'] ?? '-',
                'device_label' => $d['label'] ?? '-',
                'lapor_at'     => $d['lapor_at'],
            ];
        }

        $hariIni = $this->sessions->rekapEntriPerKaryawan($today, $today);
        $tujuh   = $this->sessions->rekapEntriPerKaryawan($weekStart, $today);
        $tokHari = $this->sessions->tokenPerKaryawan($today, $today);

        $rows = [];
        foreach ($perKaryawan as $eid => $r) {
            $r['sesi_hari_ini']   = $hariIni[$eid]['sesi']   ?? 0;
            $r['prompt_hari_ini'] = $hariIni[$eid]['prompt'] ?? 0;
            $r['token_hari_ini']  = $tokHari[$eid]           ?? 0;
            $r['sesi_7hari']      = $tujuh[$eid]['sesi']     ?? 0;
            $r['prompt_7hari']    = $tujuh[$eid]['prompt']   ?? 0;
            $rows[] = $r;
        }

        return view('ai_monitor/index', [
            'tanggal' => $today,
            'rows'    => $rows,
        ]);
    }

    // ── Satu karyawan: daftar sesinya dalam rentang ──────────────────────

    public function karyawan($employeeId)
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $employeeId = (int) $employeeId;
        $emp = db_connect()->table('employees e')
            ->select('e.nama AS nama, d.name AS dept')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('e.id', $employeeId)
            ->get()->getRowArray() ?? ['nama' => '(tidak ditemukan)', 'dept' => '-'];

        // Pemilih periode sama seperti dashboard (7h / 30h / bulan).
        $p        = $this->resolvePeriode();
        $agg      = $this->sessions->analisa($p['dari'], $p['sampai'], ['employee_id' => $employeeId]);
        $sessions = $this->sessions->byKaryawan($employeeId, $p['dari'], $p['sampai']);

        $ringkasanPeriode = $this->ringkasanPeriode(
            $sessions, $agg, $emp['nama'] ?? '', $p['label'],
            "ai_ringkasanperiode_emp{$employeeId}_{$p['dari']}_{$p['sampai']}"
        );

        return view('ai_monitor/karyawan', [
            'emp'              => $emp,
            'periode'          => $p,
            'analisa'          => $agg,
            'ambang_kantor'    => AiSessionModel::AMBANG_KANTOR,
            'scope_id'         => $employeeId,
            'printScope'       => '&employee_id=' . $employeeId,
            'sessions'         => $sessions,
            'ringkasan_periode' => $ringkasanPeriode,
        ]);
    }

    // ── Satu sesi: transkrip ─────────────────────────────────────────────

    public function sesi($sessionId)
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $sessionId = (int) $sessionId;
        $sesi = $this->sessions->detail($sessionId);
        if (! $sesi) {
            return redirect()->to('/ai-monitor')->with('error', 'Sesi tidak ditemukan.');
        }

        // Membuka isi percakapan orang HARUS terekam siapa yang melihat —
        // itulah yang menjaga pemantauan tetap terbuka dua arah.
        ActivityLog::write('view', 'ai_monitor', (string) $sessionId, 'Lihat transkrip sesi AI');

        return view('ai_monitor/sesi', [
            'sesi'    => $sesi,
            'entries' => (new AiEntryModel())->bySesi($sessionId),
        ]);
    }

    /**
     * Status perangkat untuk ditampilkan — satu aturan dipakai bersama oleh
     * halaman rekap komputer (urutan: diblokir menang, lalu menunggu, aktif,
     * baru nonaktif). Sama dengan logika badge di view perangkat.
     */
    private function statusPerangkat(array $d): string
    {
        if (! empty($d['diblokir']))      return 'diblokir';
        if (empty($d['disetujui_at']))    return 'pending';
        if (! empty($d['aktif']))         return 'aktif';
        return 'nonaktif';
    }

    // ── Rekap per komputer (per perangkat) ───────────────────────────────
    //
    // Pelengkap rekap per karyawan: menghitung aktivitas per LAPTOP. Berguna
    // saat satu karyawan punya beberapa laptop, atau saat perangkat belum
    // ditautkan ke karyawan (hasil enroll yang menunggu persetujuan).

    public function komputer()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $today     = $this->hariIni();
        $weekStart = date('Y-m-d', strtotime($today . ' -6 days')); // 7 hari inklusif

        $sessModel = $this->sessions;
        $hariIni   = $sessModel->rekapEntriPerPerangkat($today, $today);
        $tujuh     = $sessModel->rekapEntriPerPerangkat($weekStart, $today);
        $tokHari   = $sessModel->tokenPerPerangkat($today, $today);

        $devices = db_connect()->table('ai_devices d')
            ->select('d.id, d.label, d.aktif, d.disetujui_at, d.diblokir,
                      d.host_terakhir, d.lapor_at, emp.nama AS nama, dept.name AS dept')
            ->join('employees emp', 'emp.id = d.employee_id', 'left')
            ->join('departments dept', 'dept.id = emp.dept_id', 'left')
            ->orderBy('d.lapor_at IS NULL', 'ASC', false)
            ->orderBy('d.lapor_at', 'DESC')
            ->orderBy('d.label', 'ASC')
            ->get()->getResultArray();

        $rows = [];
        foreach ($devices as $d) {
            $did    = (int) $d['id'];
            $rows[] = [
                'device_id'     => $did,
                'label'         => $d['label'],
                'pemilik'       => $d['nama'] ?: '—',
                'dept'          => $d['dept'] ?? '',
                'status'        => $this->statusPerangkat($d),
                'host_terakhir' => $d['host_terakhir'],
                'lapor_at'      => $d['lapor_at'],
                'sesi_hari_ini'   => $hariIni[$did]['sesi']   ?? 0,
                'prompt_hari_ini' => $hariIni[$did]['prompt'] ?? 0,
                'token_hari_ini'  => $tokHari[$did]           ?? 0,
                'sesi_7hari'      => $tujuh[$did]['sesi']     ?? 0,
                'prompt_7hari'    => $tujuh[$did]['prompt']   ?? 0,
            ];
        }

        return view('ai_monitor/komputer', [
            'tanggal' => $today,
            'rows'    => $rows,
        ]);
    }

    public function komputerDetail($deviceId)
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $deviceId;
        $dev = db_connect()->table('ai_devices d')
            ->select('d.label, d.aktif, d.disetujui_at, d.diblokir, d.host_terakhir,
                      emp.nama AS nama, dept.name AS dept')
            ->join('employees emp', 'emp.id = d.employee_id', 'left')
            ->join('departments dept', 'dept.id = emp.dept_id', 'left')
            ->where('d.id', $deviceId)
            ->get()->getRowArray();
        if (! $dev) {
            return redirect()->to('/ai-monitor/komputer')->with('error', 'Perangkat tidak ditemukan.');
        }

        // Pemilih periode sama seperti dashboard (7h / 30h / bulan).
        $p        = $this->resolvePeriode();
        $agg      = $this->sessions->analisa($p['dari'], $p['sampai'], ['device_id' => $deviceId]);
        $sessions = $this->sessions->byPerangkat($deviceId, $p['dari'], $p['sampai']);

        $namaRingkas = ($dev['nama'] ?: '') !== '' ? $dev['nama'] : ('Komputer: ' . $dev['label']);
        $ringkasanPeriode = $this->ringkasanPeriode(
            $sessions, $agg, $namaRingkas, $p['label'],
            "ai_ringkasanperiode_dev{$deviceId}_{$p['dari']}_{$p['sampai']}"
        );

        return view('ai_monitor/komputer_detail', [
            'dev' => [
                'label'         => $dev['label'],
                'pemilik'       => $dev['nama'] ?: '—',
                'dept'          => $dev['dept'] ?? '',
                'status'        => $this->statusPerangkat($dev),
                'host_terakhir' => $dev['host_terakhir'],
            ],
            'periode'          => $p,
            'analisa'          => $agg,
            'ambang_kantor'    => AiSessionModel::AMBANG_KANTOR,
            'scope_id'         => $deviceId,
            'printScope'       => '&device_id=' . $deviceId,
            'sessions'         => $sessions,
            'ringkasan_periode' => $ringkasanPeriode,
        ]);
    }

    // ── Laporan bulanan (cetak A4, TANPA tanda tangan) ───────────────────
    //
    // GET /ai-monitor/laporan?bulan=YYYY-MM[&employee_id=N|&device_id=N]
    // Scope global (default), per karyawan, atau per komputer. Memakai style
    // cetak _laporan/_style.php; sengaja TIDAK memakai _laporan/_ttd.

    public function laporan()
    {
        if (! $this->canViewMenu(self::MENU)) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $deviceId   = (int) $this->request->getGet('device_id');

        // Laporan bulanan dicetak HANYA untuk user/komputer yang sedang dibuka.
        // Tanpa scope (mis. dari dashboard) tak ada yang bisa dicetak → arahkan
        // kembali untuk membuka data karyawan/komputer dulu.
        if (! $employeeId && ! $deviceId) {
            return redirect()->to('/ai-monitor')
                ->with('warning', 'Buka data karyawan atau komputer dulu untuk mencetak laporan bulanannya.');
        }

        helper('tanggal');
        $today  = $this->hariIni();
        $bulan  = $this->bulanSah((string) $this->request->getGet('bulan')) ?? substr($today, 0, 7);
        $dari   = $bulan . '-01';
        $sampai = date('Y-m-t', strtotime($dari));
        $label  = bulan_indo((int) substr($bulan, 5, 2)) . ' ' . substr($bulan, 0, 4);

        if ($employeeId) {
            $scope = ['employee_id' => $employeeId];
            $emp = db_connect()->table('employees e')
                ->select('e.nama AS nama, d.name AS dept')
                ->join('departments d', 'd.id = e.dept_id', 'left')
                ->where('e.id', $employeeId)
                ->get()->getRowArray();
            $scopeLabel = 'Karyawan: ' . ($emp['nama'] ?? '(tidak ditemukan)')
                . (! empty($emp['dept']) ? ' — ' . $emp['dept'] : '');
        } else {
            $scope = ['device_id' => $deviceId];
            $dev = db_connect()->table('ai_devices d')
                ->select('d.label AS label, emp.nama AS nama')
                ->join('employees emp', 'emp.id = d.employee_id', 'left')
                ->where('d.id', $deviceId)
                ->get()->getRowArray();
            $scopeLabel = 'Komputer: ' . ($dev['label'] ?? '(tidak ditemukan)')
                . (! empty($dev['nama']) ? ' — ' . $dev['nama'] : '');
        }

        $analisa  = $this->sessions->analisa($dari, $sampai, $scope);
        $sesiList = $this->sessions->sesiRentangScope($dari, $sampai, $scope);

        // Nama scope untuk prompt ringkasan (tanpa prefiks "Karyawan:/Komputer:").
        $namaScope = $employeeId
            ? ($emp['nama'] ?? '')
            : (($dev['nama'] ?? '') !== '' ? $dev['nama'] : ('Komputer: ' . ($dev['label'] ?? '')));
        $cacheKey  = $employeeId
            ? "ai_ringkasanperiode_emp{$employeeId}_{$dari}_{$sampai}"
            : "ai_ringkasanperiode_dev{$deviceId}_{$dari}_{$sampai}";

        return view('ai_monitor/laporan', [
            'bulan'            => $bulan,
            'bulanLabel'       => $label,
            'scopeLabel'       => $scopeLabel,
            'analisa'          => $analisa,
            'sesiList'         => $sesiList,
            'ambang_kantor'    => AiSessionModel::AMBANG_KANTOR,
            'printedBy'        => $this->currentUser()['name'] ?? '',
            'printedAt'        => date('d M Y H:i'),
            'ringkasan_periode' => $this->ringkasanPeriode($sesiList, $analisa, $namaScope, $label, $cacheKey),
        ]);
    }

    // ── Perangkat (token per laptop) ─────────────────────────────────────
    //
    // Mendaftarkan laptop dan menerbitkan token adalah hak yang lebih tinggi
    // dari sekadar melihat: pemegangnya menentukan laptop siapa yang dipantau.
    // Maka dipagari canEditMenu (admin tetap bypass), bukan canViewMenu.

    public function perangkat()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $devices = db_connect()->table('ai_devices d')
            ->select('d.id, d.label, d.aktif, d.host_terakhir, d.akun_terakhir,
                      d.lapor_at, d.employee_id, d.machine_id, d.enrolled_at,
                      d.disetujui_at, d.diblokir, d.alasan_blokir, d.blokir_at,
                      emp.nama AS nama, dept.name AS dept')
            ->join('employees emp', 'emp.id = d.employee_id', 'left')
            ->join('departments dept', 'dept.id = emp.dept_id', 'left')
            ->orderBy('d.aktif', 'DESC')->orderBy('d.label', 'ASC')
            ->get()->getResultArray();

        // Karyawan aktif untuk dropdown pemilik perangkat.
        $karyawan = db_connect()->table('employees')
            ->select('id, nama')->where('status', 'aktif')
            ->orderBy('nama', 'ASC')->get()->getResultArray();

        return view('ai_monitor/perangkat', [
            'devices'  => $devices,
            'karyawan' => $karyawan,
            // Kunci enrollment saat ini — ditampilkan agar bisa disalin ke
            // pemasang laptop; regenerasi lewat regenEnrollKey() (admin).
            'enrollKey' => AiEnrollKey::current(),
            // Status password copot — form pengaturannya hanya untuk admin.
            // Hash/nilainya TIDAK dikirim ke view, cukup "sudah/belum diset".
            'copotSudahDiset' => AiCopotPassword::hashNow() !== null,
            // Token mentah hanya hidup satu kali, lewat flashdata sesudah dibuat.
            'tokenBaru' => session()->getFlashdata('token_baru'),
        ]);
    }

    // ── Setujui perangkat hasil enroll ──────────────────────────────────
    //
    // Perangkat yang mendaftar sendiri masuk sebagai "menunggu persetujuan"
    // (aktif=0, disetujui_at=NULL) dan belum menyimpan kiriman apa pun. IT
    // menekan Setujui di sini untuk mengaktifkannya.

    public function setujuiPerangkat()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $this->request->getPost('device_id');
        $model    = new AiDeviceModel();
        $dev      = $model->find($deviceId);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }

        // disetujui_at diisi SEKALI (saat pertama disetujui); aktif=1 di sini
        // juga berfungsi mengaktifkan ulang perangkat yang pernah disetujui.
        $upd = ['aktif' => 1];
        if (empty($dev['disetujui_at'])) {
            $upd['disetujui_at'] = date('Y-m-d H:i:s');
        }
        $model->update($deviceId, $upd);

        ActivityLog::write('approve', 'ai_monitor', (string) $deviceId,
            'Setujui perangkat: ' . $dev['label']);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Perangkat "' . $dev['label'] . '" disetujui dan diaktifkan.');
    }

    // ── Tautkan pemilik perangkat hasil enroll ──────────────────────────
    //
    // Perangkat yang mendaftar sendiri (enroll) masuk tanpa pemilik. IT
    // menautkannya ke karyawan di sini agar rekap per karyawan terisi.

    public function tautkanPemilik()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId   = (int) $this->request->getPost('device_id');
        $employeeId = (int) $this->request->getPost('employee_id');
        $model      = new AiDeviceModel();
        $dev        = $model->find($deviceId);
        if (! $dev || ! $employeeId) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat atau karyawan tidak sah.');
        }

        $model->update($deviceId, ['employee_id' => $employeeId]);

        $nama = db_connect()->table('employees')->select('nama')
            ->where('id', $employeeId)->get()->getRowArray()['nama'] ?? '(karyawan)';
        ActivityLog::write('update', 'ai_monitor', (string) $deviceId,
            'Tautkan perangkat ' . $dev['label'] . ' ke ' . $nama);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Perangkat "' . $dev['label'] . '" ditautkan ke ' . $nama . '.');
    }

    // ── Blokir / buka blokir (stop sementara) ───────────────────────────
    //
    // Blokir menghentikan laptop sementara: agen membaca "blokir":true dari
    // respons ingest dan berhenti mengirim, tanpa kehilangan tokennya.
    // Berbeda dari nonaktif (token ditolak) — blokir bisa langsung dicabut.

    public function blokir()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $this->request->getPost('device_id');
        $alasan   = trim((string) $this->request->getPost('alasan'));
        $model    = new AiDeviceModel();
        $dev      = $model->find($deviceId);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }
        if ($alasan === '') {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Alasan blokir wajib diisi.');
        }

        $model->update($deviceId, [
            'diblokir'      => 1,
            'alasan_blokir' => substr($alasan, 0, 255),
            'blokir_oleh'   => (int) session()->get('user_id'),
            'blokir_at'     => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('block', 'ai_monitor', (string) $deviceId,
            'Hentikan akses AI: ' . $dev['label'] . ' — alasan: ' . $alasan);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Akses AI untuk "' . $dev['label'] . '" dihentikan sementara.');
    }

    public function bukaBlokir()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $this->request->getPost('device_id');
        $model    = new AiDeviceModel();
        $dev      = $model->find($deviceId);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }

        $model->update($deviceId, [
            'diblokir'      => 0,
            'alasan_blokir' => null,
            'blokir_oleh'   => null,
            'blokir_at'     => null,
        ]);

        ActivityLog::write('unblock', 'ai_monitor', (string) $deviceId,
            'Pulihkan akses AI: ' . $dev['label']);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Akses AI untuk "' . $dev['label'] . '" dipulihkan.');
    }

    // ── Hapus perangkat ─────────────────────────────────────────────────
    //
    // Menghapus HANYA entri perangkatnya; RIWAYAT pemakaian (sesi, entri,
    // token pemakaian) SENGAJA DIPERTAHANKAN untuk audit — sesi tetap tertaut
    // ke karyawannya. Akses AI di laptop tetap MATI: tokennya tak lagi dikenal
    // server, sehingga agen menguncinya lalu enroll ulang sebagai "menunggu
    // persetujuan". Untuk benar-benar melepas agen dari laptop, pakai copot.
    public function hapusPerangkat()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $this->request->getPost('device_id');
        $model    = new AiDeviceModel();
        $dev      = $model->find($deviceId);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }

        // Hanya baris perangkat yang dihapus. Sesi/entri dibiarkan (riwayat
        // tetap bisa dibuka lewat halaman karyawan).
        $model->delete($deviceId);

        ActivityLog::write('delete', 'ai_monitor', (string) $deviceId,
            'Hapus perangkat: ' . $dev['label'] . ' (riwayat tetap disimpan; akses AI di laptop mati)');

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Perangkat "' . $dev['label'] . '" dihapus. Riwayat pemakaian tetap tersimpan; akses AI di laptop itu mati pada kontak berikutnya.');
    }

    // ── Regenerasi kunci enrollment ─────────────────────────────────────
    //
    // Memutar kunci = menutup pendaftaran laptop BARU dengan kunci lama.
    // Perangkat yang sudah enroll tetap jalan (pakai token sendiri). Hanya
    // admin — ini kunci bersama seluruh tim, bukan hak per-menu biasa.

    public function regenEnrollKey()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Hanya admin yang boleh meregenerasi kunci.');
        }

        AiEnrollKey::regenerate();
        ActivityLog::write('update', 'ai_monitor', AiEnrollKey::KEY,
            'Regenerasi kunci enrollment Pemantauan AI');

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Kunci enrollment baru dibuat. Pemasangan laptop baru kini memakai kunci ini.');
    }

    // ── Password copot (uninstall) terpusat ─────────────────────────────
    //
    // Satu password untuk semua perangkat; mencopot agen di laptop butuh
    // password ini. Server hanya menyimpan hash-nya (AiCopotPassword), yang
    // lalu disalurkan ke laptop lewat respons enroll/ingest. Hak tinggi —
    // admin saja, sejajar regenEnrollKey.

    public function setCopotPassword()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Hanya admin yang boleh mengubah password copot.');
        }

        $password = (string) $this->request->getPost('password');
        if (strlen($password) < 8) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Password copot minimal 8 karakter.');
        }

        AiCopotPassword::set($password);

        // Password maupun hash-nya TIDAK pernah masuk pesan audit.
        ActivityLog::write('update', 'ai_monitor', AiCopotPassword::KEY,
            'Ubah password copot perangkat');

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Password copot diperbarui. Tersinkron ke laptop pada laporan berikutnya.');
    }

    // ── Ubah nama/alias perangkat ───────────────────────────────────────
    //
    // Nama komputer bisa acak; IT memberi alias yang mudah dikenali. Alias ini
    // yang MENANG dan tak pernah ditimpa enroll ulang (enroll hanya rotasi
    // token + update host/akun/enrolled_at). Host asli tetap tampil sebagai
    // info di halaman perangkat.

    public function ubahLabel()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $deviceId = (int) $this->request->getPost('device_id');
        $label    = trim((string) $this->request->getPost('label'));
        $model    = new AiDeviceModel();
        $dev      = $model->find($deviceId);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }
        if ($label === '') {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Nama perangkat wajib diisi.');
        }
        $label = substr($label, 0, 100);

        $model->update($deviceId, ['label' => $label]);
        ActivityLog::write('update', 'ai_monitor', (string) $deviceId,
            'Ubah nama perangkat menjadi: ' . $label);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Nama perangkat diperbarui menjadi "' . $label . '".');
    }

    public function buatPerangkat()
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $employeeId = (int) $this->request->getPost('employee_id');
        $label      = trim((string) $this->request->getPost('label'));
        if (! $employeeId || $label === '') {
            return redirect()->back()->with('error', 'Pemilik dan label perangkat wajib diisi.');
        }

        // Token mentah: 32 byte acak kuat → 64 hex. Yang DISIMPAN hanya
        // sha256-nya; nilai mentah tak pernah masuk DB, hanya ditampilkan
        // sekali ke admin untuk disalin ke pemasang laptop.
        $token = bin2hex(random_bytes(32));

        $model = new AiDeviceModel();
        $model->insert([
            'employee_id' => $employeeId,
            'label'       => substr($label, 0, 100),
            'token_hash'  => hash('sha256', $token),
            'aktif'       => 1,
            'created_by'  => (int) session()->get('user_id'),
        ]);

        ActivityLog::write('create', 'ai_monitor', (string) $model->getInsertID(),
            'Terbitkan token perangkat: ' . $label);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('token_baru', ['label' => $label, 'token' => $token]);
    }

    public function nonaktifPerangkat($id)
    {
        if (! $this->canEditMenu(self::MENU)) {
            return redirect()->to('/ai-monitor')->with('error', 'Akses ditolak.');
        }

        $id    = (int) $id;
        $model = new AiDeviceModel();
        $dev   = $model->find($id);
        if (! $dev) {
            return redirect()->to('/ai-monitor/perangkat')->with('error', 'Perangkat tidak ditemukan.');
        }

        // Toggle aktif. Menonaktifkan = token perangkat berhenti diterima
        // (ingest mensyaratkan aktif=1), tanpa menghapus riwayatnya.
        $baru = $dev['aktif'] ? 0 : 1;
        $model->update($id, ['aktif' => $baru]);
        ActivityLog::write('update', 'ai_monitor', (string) $id,
            ($baru ? 'Aktifkan' : 'Nonaktifkan') . ' perangkat: ' . $dev['label']);

        return redirect()->to('/ai-monitor/perangkat')
            ->with('success', 'Perangkat "' . $dev['label'] . '" ' . ($baru ? 'diaktifkan.' : 'dinonaktifkan.'));
    }
}
