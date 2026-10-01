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

        // Default rentang: 7 hari terakhir. GET dari/sampai mengesampingkan,
        // divalidasi agar tak ada tanggal ngawur yang masuk ke query.
        $today  = $this->hariIni();
        $dari   = $this->tanggalSah((string) $this->request->getGet('dari'))   ?? date('Y-m-d', strtotime($today . ' -6 days'));
        $sampai = $this->tanggalSah((string) $this->request->getGet('sampai')) ?? $today;
        if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

        return view('ai_monitor/karyawan', [
            'emp'      => $emp,
            'filter'   => ['dari' => $dari, 'sampai' => $sampai],
            'sessions' => $this->sessions->byKaryawan($employeeId, $dari, $sampai),
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

    /** Y-m-d yang sah atau null (kosong/ngawur → null → pakai default). */
    private function tanggalSah(string $tanggal): ?string
    {
        if ($tanggal === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        return ($d && $d->format('Y-m-d') === $tanggal) ? $tanggal : null;
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
