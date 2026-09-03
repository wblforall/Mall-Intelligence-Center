<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Libraries\AppSync;
use App\Libraries\PenautanAkses;
use App\Models\AppModel;
use App\Models\AppRoleModel;
use App\Models\EmployeeAppAccessModel;

/**
 * Penautan akun aplikasi lain ke data karyawan MIC — dilakukan admin, di layar.
 *
 * TIDAK ADA PENAUTAN OTOMATIS. Sistem hanya mengusulkan pasangan beserta
 * tingkat keyakinannya; setiap tautan baru lahir dari klik admin. Usul yang
 * kuat memang datang tercentang, tapi mencentang bukan menyimpan — admin
 * tetap harus menekan tombol simpan, dan boleh membatalkan centang mana pun.
 *
 * Alasannya bukan kehati-hatian umum, melainkan tiga akibat konkret:
 *
 *   1. Peran `unit_admin` bisa mengubah template alur persetujuan. Memberikannya
 *      karena sebuah kolom boolean di sistem lain bernilai 1 berarti memindahkan
 *      wewenang tanpa ada yang menyetujuinya.
 *   2. Tautan yang salah akan menonaktifkan akun ORANG LAIN saat karyawan
 *      resign, lewat antrian yang berjalan sendiri.
 *   3. Penautan memindahkan kredensial ke MIC dan mematikan sandi lokal
 *      (PROSEDUR-PENAUTAN-AKUN.md §1b) — salah tautan berarti mengunci orang.
 *
 * Gate-nya memakai menu `app_access` yang sudah ada, SENGAJA tidak membuat
 * konsep izin baru — sama seperti layar AppAccess.
 */
class PenautanAkun extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('app_access')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $apps = [];
        foreach ((new AppModel())->aktifSaja() as $a) {
            $a['terkonfigurasi'] = AppSync::terkonfigurasi($a['kode']);
            $a['jumlah_tertaut'] = db_connect()->table('employee_app_access')
                ->where('app_id', $a['id'])->where('aktif', 1)
                ->where('id_lokal IS NOT NULL')->countAllResults();
            $apps[] = $a;
        }

        return view('penautan_akun/index', [
            'user'    => $this->currentUser(),
            'apps'    => $apps,
            'canEdit' => $this->canEditMenu('app_access'),
        ]);
    }

    /** Tarik daftar akun aplikasi tujuan dan tampilkan usulnya untuk ditinjau. */
    public function tinjau(string $kode)
    {
        if (! $this->canViewMenu('app_access')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $app = (new AppModel())->where('kode', $kode)->first();
        if (! $app) return redirect()->to('/penautan-akun')->with('error', 'Aplikasi tidak dikenal.');

        $usul = PenautanAkses::usulkan($kode);
        if (! $usul['ok']) {
            return redirect()->to('/penautan-akun')
                ->with('error', 'Tidak bisa menarik daftar akun ' . $app['nama'] . ': ' . $usul['error']);
        }

        return view('penautan_akun/tinjau', [
            'user'      => $this->currentUser(),
            'app'       => $app,
            'baris'     => $usul['baris'],
            'ringkasan' => $usul['ringkasan'],
            'peran'     => (new AppRoleModel())->where('app_id', $app['id'])->where('aktif', 1)->findAll(),
            'karyawan'  => $this->karyawanAktif(),
            'canEdit'   => $this->canEditMenu('app_access'),
        ]);
    }

    /**
     * Simpan keputusan admin.
     *
     * Yang dikirim formulir TIDAK dipercaya. Daftar akun ditarik ulang dari
     * aplikasi tujuan dan tiap `id_lokal` harus benar-benar ada di sana;
     * `employee_id` harus karyawan aktif; peran harus milik aplikasi itu.
     * Tanpa pemeriksaan ini, satu formulir yang diubah — atau sekadar salah
     * ketik — bisa menautkan akun ke karyawan yang tidak dimaksud, dan
     * akibatnya baru terasa berbulan-bulan kemudian saat orangnya resign.
     */
    public function simpan(string $kode)
    {
        if (! $this->canEditMenu('app_access')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $app = (new AppModel())->where('kode', $kode)->first();
        if (! $app) return redirect()->to('/penautan-akun')->with('error', 'Aplikasi tidak dikenal.');

        $pilih  = (array) ($this->request->getPost('tautkan') ?? []);   // id_lokal => 'on'
        $kePeg  = (array) ($this->request->getPost('employee') ?? []);  // id_lokal => employee_id
        $kePrn  = (array) ($this->request->getPost('peran') ?? []);     // id_lokal => kode peran
        $lepas  = (array) ($this->request->getPost('lepas') ?? []);     // id_lokal => 'on'

        $usul = PenautanAkses::usulkan($kode);
        if (! $usul['ok']) {
            return redirect()->back()->with('error', 'Tidak bisa memverifikasi: ' . $usul['error']);
        }

        $akunSah = [];
        foreach ($usul['baris'] as $b) $akunSah[$b['id_lokal']] = $b;

        $peranId = [];
        foreach ((new AppRoleModel())->where('app_id', $app['id'])->where('aktif', 1)->findAll() as $r) {
            $peranId[$r['kode']] = (int) $r['id'];
        }

        $pegawaiAktif = [];
        foreach ($this->karyawanAktif() as $e) $pegawaiAktif[(int) $e['id']] = $e;

        $model = new EmployeeAppAccessModel();
        ActivityLog::sebagai((int) $this->currentUser()['id']);

        $ditaut = 0;
        $dilepas = 0;
        $ditolak = [];

        // BELUM LENGKAP terhadap PROSEDUR-PENAUTAN-AKUN.md §1b: melepas tautan
        // seharusnya SEKALIGUS mengembalikan `users.kredensial` di aplikasi
        // tujuan ke 'lokal' dan mengirim sandi baru. Selama Fase 6 belum ada,
        // kolom itu belum ada juga, jadi kredensial belum pernah berpindah —
        // dan pelepasan di sini aman. Begitu Fase 6 mendarat, langkah itu
        // WAJIB ditambahkan di sini dalam satu transaksi; tanpa itu, melepas
        // tautan akan mengunci orangnya permanen.
        foreach ($lepas as $idLokal => $_) {
            $idLokal = (string) $idLokal;
            if (! isset($akunSah[$idLokal]) || ! $akunSah[$idLokal]['sudah_tertaut']) continue;

            $model->revoke(
                (int) $akunSah[$idLokal]['tautan_kini']['employee_id'],
                (int) $app['id'],
                (int) $this->currentUser()['id'],
                'tautan dilepas dari layar Penautan Akun'
            );
            $dilepas++;
        }

        foreach ($pilih as $idLokal => $_) {
            $idLokal = (string) $idLokal;

            if (! isset($akunSah[$idLokal])) {
                $ditolak[] = 'akun ' . $idLokal . ' tidak ada di ' . $app['nama'];
                continue;
            }

            $empId = (int) ($kePeg[$idLokal] ?? 0);
            if (! isset($pegawaiAktif[$empId])) {
                $ditolak[] = $akunSah[$idLokal]['nama_akun'] . ': karyawan tidak dipilih atau tidak aktif';
                continue;
            }

            $kodePeran = (string) ($kePrn[$idLokal] ?? '');
            if (! isset($peranId[$kodePeran])) {
                $ditolak[] = $akunSah[$idLokal]['nama_akun'] . ': peran tidak sah';
                continue;
            }

            $model->grant(
                $empId,
                (int) $app['id'],
                $peranId[$kodePeran],
                null,
                (int) $this->currentUser()['id'],
                'ditautkan admin lewat layar Penautan Akun',
                $idLokal
            );
            $ditaut++;
        }

        $pesan = $ditaut . ' akun ditautkan';
        if ($dilepas > 0) $pesan .= ', ' . $dilepas . ' dilepas';
        if ($ditolak !== []) {
            return redirect()->to('/penautan-akun/' . $kode)
                ->with('error', $pesan . '. Ditolak: ' . implode('; ', array_slice($ditolak, 0, 5)));
        }

        return redirect()->to('/penautan-akun/' . $kode)->with('success', $pesan . '.');
    }

    /** @return array<int,array<string,mixed>> */
    private function karyawanAktif(): array
    {
        return db_connect()->table('employees')
            ->select('id, nik, nama, jabatan')
            ->where('status', 'aktif')
            ->orderBy('nama', 'ASC')
            ->get()->getResultArray();
    }
}
