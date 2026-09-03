<?php

namespace App\Commands;

use App\Libraries\ActivityLog;
use App\Libraries\AppSync;
use App\Models\EmployeeAppAccessModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cocokkan akun aplikasi lain ke data karyawan MIC, lalu tautkan.
 *
 * Ada karena penautan manual tidak mungkin selesai: 202 karyawan dikali 5
 * aplikasi, lewat layar satu per satu. Itu bukan soal kemauan, itu soal
 * aritmetika.
 *
 *   php spark mic:import-akses esign                  # periksa saja (bawaan)
 *   php spark mic:import-akses esign --terapkan --oleh 1
 *
 * BAWAANNYA MEMERIKSA, BUKAN MENULIS. Menulis harus diminta eksplisit dengan
 * --terapkan. Perintah yang menyentuh 61 akun sekaligus tidak boleh berjalan
 * hanya karena seseorang menekan Enter untuk melihat apa yang terjadi.
 *
 * Aturan pencocokan dan tingkat keyakinannya mengikuti
 * `erp-integrasi-wbl/PROSEDUR-PENAUTAN-AKUN.md` §2, peran mengikuti §3.
 */
class ImportAkses extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:import-akses';
    protected $description = 'Cocokkan akun aplikasi lain ke karyawan MIC dan tautkan (bawaan: periksa saja).';
    protected $usage       = 'mic:import-akses <kode_app> [--terapkan] [--oleh <user_id>] [--csv <berkas>]';
    protected $arguments   = [
        'kode_app' => 'Kode aplikasi di tabel `apps` (mis. esign)',
    ];
    protected $options = [
        '--terapkan' => 'Benar-benar tulis tautannya. Tanpa ini hanya memeriksa.',
        '--oleh'     => 'id user MIC yang bertanggung jawab. WAJIB bila --terapkan.',
        '--csv'      => 'Simpan hasil pemeriksaan ke berkas CSV (bawaan: writable/import-akses-<app>-<waktu>.csv)',
    ];

    /**
     * Akun yang bukan orang. Tidak boleh tertaut ke karyawan mana pun —
     * `Administrator` akun sistem, `Play Store` akun untuk peninjauan toko
     * aplikasi.
     */
    private const BUKAN_ORANG = ['administrator', 'play store'];

    public function run(array $params)
    {
        $kodeApp  = $params[0] ?? CLI::getSegment(2);
        $terapkan = CLI::getOption('terapkan') !== null;
        $oleh     = (int) (CLI::getOption('oleh') ?? 0);

        if (! $kodeApp) {
            CLI::error('Kode aplikasi wajib. Contoh: php spark mic:import-akses esign');

            return;
        }

        // Diperiksa SEBELUM menarik data: tidak ada gunanya memanggil jaringan
        // lalu berhenti karena syarat yang sudah bisa diketahui sejak awal.
        if ($terapkan && $oleh <= 0) {
            CLI::error('--terapkan wajib disertai --oleh <user_id>.');
            CLI::write('  grant() mencatat siapa yang menautkan ke activity_logs. Impor tanpa');
            CLI::write('  penanggung jawab akan menghasilkan 61 baris audit tanpa nama.');

            return;
        }

        $db  = db_connect();
        $app = $db->table('apps')->select('id, kode, nama')->where('kode', $kodeApp)->get()->getRowArray();

        if (! $app) {
            CLI::error('Aplikasi "' . $kodeApp . '" tidak ada di tabel apps.');

            return;
        }

        if ($terapkan && ! $this->pastikanUser($db, $oleh)) return;

        CLI::write('Menarik daftar akun dari ' . $app['nama'] . ' ...', 'yellow');

        $jawab = AppSync::ambilPengguna($kodeApp);
        if (! $jawab['ok']) {
            CLI::error('Gagal menarik daftar akun: ' . $jawab['error']);

            return;
        }

        $akun = $jawab['data'];
        CLI::write('  ' . count($akun) . ' akun diterima.', 'green');
        CLI::newLine();

        $karyawan = $this->muatKaryawan($db);
        $hasil    = [];

        foreach ($akun as $a) {
            $hasil[] = $this->cocokkan($a, $karyawan);
        }

        $this->laporkan($hasil);

        $berkasCsv = CLI::getOption('csv') ?: WRITEPATH . 'import-akses-' . $kodeApp . '-' . date('Ymd-His') . '.csv';
        $this->tulisCsv($berkasCsv, $hasil);
        CLI::newLine();
        CLI::write('Rincian per akun: ' . CLI::color($berkasCsv, 'blue'));

        if (! $terapkan) {
            CLI::newLine();
            CLI::write('Belum ada yang ditulis. Periksa CSV di atas, lalu jalankan:', 'yellow');
            CLI::write('  php spark mic:import-akses ' . $kodeApp . ' --terapkan --oleh <user_id>');

            return;
        }

        CLI::newLine();
        $this->terapkan($hasil, (int) $app['id'], $kodeApp, $oleh);
    }

    /** Penanggung jawab harus benar-benar ada — bukan angka yang kebetulan diketik. */
    private function pastikanUser($db, int $oleh): bool
    {
        $u = $db->table('users')->select('id, name, email')->where('id', $oleh)->get()->getRowArray();

        if (! $u) {
            CLI::error('User id ' . $oleh . ' tidak ada. Tidak ada yang bisa dicatat sebagai penaut.');

            return false;
        }

        CLI::write('Penanggung jawab: ' . $u['name'] . ' <' . $u['email'] . '>', 'yellow');

        return true;
    }

    /**
     * Karyawan MIC, diindeks tiga cara sesuai §2.
     *
     * Nama diindeks sebagai DAFTAR, bukan satu nilai — justru tabrakan nama
     * itu yang perlu ketahuan. Di MIC ada 12 kelompok nama depan yang
     * bertabrakan (terburuk 14 orang bernama depan "Muhammad").
     *
     * @return array{kerja:array<string,array>, pribadi:array<string,array>, nama:array<string,array<int,array>>}
     */
    private function muatKaryawan($db): array
    {
        $baris = $db->table('employees')
            ->select('id, nik, nama, jabatan, email, email_kerja, status, dept_id, company_id')
            ->get()->getResultArray();

        $idx = ['kerja' => [], 'pribadi' => [], 'nama' => []];

        foreach ($baris as $e) {
            // Karyawan yang sudah keluar TIDAK dijadikan kandidat: menautkan
            // akun aktif ke orang yang sudah resign akan langsung memicu
            // antrian pencabutan, dan tautannya sendiri memang salah.
            if ($e['status'] !== 'aktif') continue;

            if (! empty($e['email_kerja'])) $idx['kerja'][$this->norm($e['email_kerja'])]   = $e;
            if (! empty($e['email']))       $idx['pribadi'][$this->norm($e['email'])]       = $e;

            $idx['nama'][$this->normNama($e['nama'])][] = $e;
        }

        return $idx;
    }

    /**
     * Cocokkan satu akun ke karyawan.
     *
     * Urutan percobaannya adalah urutan keyakinan: email kerja diberikan
     * perusahaan sehingga tak mungkin kebetulan; email pribadi diberikan orang
     * yang sama ke dua sistem; nama paling lemah dan hanya diterima kalau
     * kandidatnya tepat satu.
     *
     * @param array<string,mixed>  $a
     * @param array<string,mixed>  $karyawan
     * @return array<string,mixed>
     */
    private function cocokkan(array $a, array $karyawan): array
    {
        $baris = [
            'id_lokal'   => (string) ($a['id'] ?? ''),
            'nama_akun'  => (string) ($a['nama'] ?? ''),
            'email_akun' => (string) ($a['email'] ?? ''),
            'unit_admin' => ! empty($a['unit_admin']),
            'aktif_akun' => ! empty($a['aktif']),
            'peran'      => ! empty($a['unit_admin']) ? 'unit_admin' : 'user',
            'employee_id' => null,
            'nik'        => '',
            'nama_mic'   => '',
            'jabatan'    => '',
            'cara'       => '',
            'keyakinan'  => '',
            'tulis'      => false,
            'catatan'    => '',
        ];

        if (in_array($this->normNama($baris['nama_akun']), self::BUKAN_ORANG, true)) {
            $baris['cara']    = 'bukan_orang';
            $baris['catatan'] = 'akun sistem, sengaja tidak ditautkan';

            return $baris;
        }

        $email = $this->norm($baris['email_akun']);

        if ($email !== '' && isset($karyawan['kerja'][$email])) {
            return $this->isiKandidat($baris, $karyawan['kerja'][$email], 'email_kerja', 'tertinggi', true);
        }

        if ($email !== '' && isset($karyawan['pribadi'][$email])) {
            return $this->isiKandidat($baris, $karyawan['pribadi'][$email], 'email_pribadi', 'tinggi', true);
        }

        $kandidat = $karyawan['nama'][$this->normNama($baris['nama_akun'])] ?? [];

        if (count($kandidat) === 1) {
            return $this->isiKandidat($baris, $kandidat[0], 'nama_persis', 'sedang', true);
        }

        if (count($kandidat) > 1) {
            // §2: keunikan diperiksa SAAT penautan, bukan mengandalkan hasil
            // pemeriksaan 2 Sep. Karyawan baru bisa membuat nama jadi ambigu.
            $baris['cara']      = 'nama_ambigu';
            $baris['keyakinan'] = 'perlu konfirmasi';
            $baris['catatan']   = count($kandidat) . ' karyawan bernama sama: '
                . implode(', ', array_map(fn ($k) => $k['nik'] . '/' . $k['jabatan'], $kandidat));

            return $baris;
        }

        $baris['cara']    = 'tidak_ada';
        $baris['catatan'] = 'tidak ada karyawan aktif yang cocok';

        return $baris;
    }

    /**
     * @param array<string,mixed> $baris
     * @param array<string,mixed> $e
     * @return array<string,mixed>
     */
    private function isiKandidat(array $baris, array $e, string $cara, string $keyakinan, bool $tulis): array
    {
        $baris['employee_id'] = (int) $e['id'];
        $baris['nik']         = (string) $e['nik'];
        $baris['nama_mic']    = (string) $e['nama'];
        $baris['jabatan']     = (string) $e['jabatan'];
        $baris['cara']        = $cara;
        $baris['keyakinan']   = $keyakinan;
        $baris['tulis']       = $tulis;

        return $baris;
    }

    private function norm(?string $s): string
    {
        return mb_strtolower(trim((string) $s));
    }

    /** Nama disamakan spasi gandanya supaya "Budi  Santoso" tidak dianggap beda. */
    private function normNama(?string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s) ?? ''));
    }

    /** @param array<int,array<string,mixed>> $hasil */
    private function laporkan(array $hasil): void
    {
        $per = [];
        foreach ($hasil as $h) {
            $per[$h['cara']] = ($per[$h['cara']] ?? 0) + 1;
        }

        $label = [
            'email_kerja'   => 'Email kerja sama      (tertinggi)',
            'email_pribadi' => 'Email pribadi sama    (tinggi)',
            'nama_persis'   => 'Nama persis & unik    (sedang)',
            'nama_ambigu'   => 'Nama ambigu           → PERLU KONFIRMASI',
            'bukan_orang'   => 'Bukan orang           → dikecualikan',
            'tidak_ada'     => 'Tidak ada di MIC      → perlu ditinjau',
        ];

        CLI::write('Hasil pencocokan', 'yellow');
        foreach ($label as $k => $t) {
            if (! isset($per[$k])) continue;
            $warna = in_array($k, ['email_kerja', 'email_pribadi', 'nama_persis'], true) ? 'green' : 'light_red';
            CLI::write('  ' . str_pad($t, 38) . CLI::color(str_pad((string) $per[$k], 3, ' ', STR_PAD_LEFT), $warna));
        }

        $akanDitulis = count(array_filter($hasil, fn ($h) => $h['tulis']));
        $unitAdmin   = count(array_filter($hasil, fn ($h) => $h['tulis'] && $h['peran'] === 'unit_admin'));

        CLI::newLine();
        CLI::write('  Siap ditautkan       : ' . CLI::color((string) $akanDitulis, 'green')
            . ' dari ' . count($hasil));
        CLI::write('  Di antaranya unit_admin: ' . $unitAdmin);

        if ($unitAdmin > 0) {
            CLI::write('  ⚠ unit_admin bisa MENGUBAH TEMPLATE ALUR PERSETUJUAN. PROSEDUR §3 minta', 'light_red');
            CLI::write('    peran ini ditinjau bersamaan dengan penautan — periksa daftarnya di CSV.', 'light_red');
        }
    }

    /** @param array<int,array<string,mixed>> $hasil */
    private function tulisCsv(string $berkas, array $hasil): void
    {
        $f = @fopen($berkas, 'w');
        if ($f === false) {
            CLI::error('Tidak bisa menulis ' . $berkas);

            return;
        }

        fputcsv($f, [
            'id_lokal', 'nama_akun', 'email_akun', 'akun_aktif', 'peran',
            'employee_id', 'nik', 'nama_mic', 'jabatan',
            'cara_cocok', 'keyakinan', 'akan_ditulis', 'catatan',
        ]);

        foreach ($hasil as $h) {
            fputcsv($f, [
                $h['id_lokal'], $h['nama_akun'], $h['email_akun'],
                $h['aktif_akun'] ? 'ya' : 'tidak', $h['peran'],
                $h['employee_id'], $h['nik'], $h['nama_mic'], $h['jabatan'],
                $h['cara'], $h['keyakinan'], $h['tulis'] ? 'ya' : 'tidak', $h['catatan'],
            ]);
        }

        fclose($f);
    }

    /** @param array<int,array<string,mixed>> $hasil */
    private function terapkan(array $hasil, int $appId, string $kodeApp, int $oleh): void
    {
        $db    = db_connect();
        $model = new EmployeeAppAccessModel();

        // CLI tidak punya sesi, jadi tanpa ini seluruh jejak audit berbunyi
        // "System" — dan --oleh yang sudah diwajibkan itu jadi setengah jujur:
        // tercatat di employee_app_access, tapi tidak di activity_logs.
        ActivityLog::sebagai($oleh);

        $peranId = [];
        foreach ($db->table('app_roles')->select('id, kode')->where('app_id', $appId)->get()->getResultArray() as $r) {
            $peranId[$r['kode']] = (int) $r['id'];
        }

        // Tautan yang sudah ada, untuk membedakan "baru" dari "sudah sama".
        $sudahAda = [];
        foreach ($db->table('employee_app_access')
            ->select('employee_id, app_role_id, id_lokal')
            ->where('app_id', $appId)->where('aktif', 1)->get()->getResultArray() as $r) {
            $sudahAda[(int) $r['employee_id']] = $r;
        }

        $ditulis = 0;
        $dilewati = 0;
        $gagal   = 0;

        foreach ($hasil as $h) {
            if (! $h['tulis']) continue;

            if (! isset($peranId[$h['peran']])) {
                CLI::error('  peran "' . $h['peran'] . '" tidak ada di app_roles untuk ' . $kodeApp
                    . ' — ' . $h['nama_akun'] . ' dilewati');
                $gagal++;
                continue;
            }

            // Yang sudah sama persis dilewati. Tanpa ini, menjalankan ulang
            // importer menghasilkan 48 catatan "update" padahal tak ada yang
            // berubah — kebisingan yang justru menutupi perubahan sungguhan
            // saat jejak audit dibaca nanti.
            $lama = $sudahAda[(int) $h['employee_id']] ?? null;
            if ($lama
                && (int) $lama['app_role_id'] === $peranId[$h['peran']]
                && (string) $lama['id_lokal'] === (string) $h['id_lokal']) {
                $dilewati++;
                continue;
            }

            // Lewat grant(), TIDAK insert langsung: grant() menulis ke
            // employee_app_access DAN activity_logs. Insert langsung
            // menghilangkan jejak siapa menautkan apa (PROSEDUR §4 langkah 4).
            $model->grant(
                (int) $h['employee_id'],
                $appId,
                $peranId[$h['peran']],
                null,                       // ikut company_id karyawan sendiri
                $oleh,
                'impor ' . $kodeApp . ' — cocok ' . $h['cara'],
                $h['id_lokal']
            );
            $ditulis++;
        }

        CLI::write('Selesai.', 'green');
        CLI::write('  Tertaut  : ' . $ditulis);
        if ($dilewati > 0) CLI::write('  Sudah sama: ' . $dilewati . ' (dilewati, tidak dicatat ulang)');
        if ($gagal > 0)    CLI::write('  Gagal    : ' . $gagal, 'light_red');

        $sisa = count(array_filter($hasil, fn ($h) => ! $h['tulis'] && $h['cara'] !== 'bukan_orang'));
        if ($sisa > 0) {
            CLI::newLine();
            CLI::write($sisa . ' akun belum tertaut dan perlu keputusan manusia — lihat CSV.', 'yellow');
            CLI::write('Selama belum tertaut, akun itu tidak ikut pencabutan otomatis saat resign.');
        }
    }
}
