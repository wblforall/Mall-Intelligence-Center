<?php

namespace App\Libraries;

/**
 * Mesin USUL penautan akun aplikasi lain ke data karyawan MIC.
 *
 * Namanya usul, bukan penaut: kelas ini TIDAK MENULIS APA PUN. Ia hanya
 * mengusulkan pasangan beserta tingkat keyakinannya, dan admin yang
 * memutuskan satu per satu di layar Penautan Akun.
 *
 * Sengaja begitu. Versi pertama fitur ini punya perintah CLI yang menulis 48
 * tautan sekaligus begitu keyakinannya "tinggi", dan itu salah arah:
 *
 *   - Peran `unit_admin` bisa MENGUBAH TEMPLATE ALUR PERSETUJUAN. Memberikannya
 *     ke 17 orang karena kolom boolean di sistem lain bernilai 1 berarti
 *     memindahkan wewenang tanpa ada yang pernah menyetujuinya.
 *   - Cocok email pribadi bukan bukti identitas. Ia petunjuk kuat, dan
 *     petunjuk kuat tetap perlu dilihat orang sebelum jadi hak akses.
 *   - Tautan yang salah menonaktifkan akun orang lain saat karyawan resign,
 *     lewat antrian yang berjalan sendiri. Ongkos salahnya tidak simetris.
 *
 * Aturan pencocokan mengikuti `erp-integrasi-wbl/PROSEDUR-PENAUTAN-AKUN.md`
 * §2, penentuan peran mengikuti §3.
 */
class PenautanAkses
{
    /**
     * Akun yang bukan orang — tidak boleh tertaut ke karyawan mana pun.
     * `Administrator` akun sistem, `Play Store` akun peninjauan toko aplikasi.
     */
    public const BUKAN_ORANG = ['administrator', 'play store'];

    /** Cara cocok yang boleh diusulkan tercentang di layar. */
    public const USUL_KUAT = ['email_kerja', 'email_pribadi', 'nama_persis'];

    /**
     * Susun usul untuk seluruh akun aplikasi tujuan.
     *
     * @return array{ok:bool, error:?string, baris:array<int,array<string,mixed>>, ringkasan:array<string,int>}
     */
    public static function usulkan(string $kodeApp): array
    {
        $jawab = AppSync::ambilPengguna($kodeApp);
        if (! $jawab['ok']) {
            return ['ok' => false, 'error' => $jawab['error'], 'baris' => [], 'ringkasan' => []];
        }

        $karyawan = self::indeksKaryawan();
        $tertaut  = self::tautanSekarang($kodeApp);

        $baris = [];
        foreach ($jawab['data'] as $akun) {
            $baris[] = self::cocokkan($akun, $karyawan, $tertaut);
        }

        // Yang perlu perhatian didahulukan; yang sudah tertaut ke bawah.
        $urutan = ['nama_ambigu' => 0, 'tidak_ada' => 1, 'email_kerja' => 2,
                   'email_pribadi' => 3, 'nama_persis' => 4, 'bukan_orang' => 5];
        usort($baris, function ($a, $b) use ($urutan) {
            if ($a['sudah_tertaut'] !== $b['sudah_tertaut']) return $a['sudah_tertaut'] ? 1 : -1;

            return ($urutan[$a['cara']] ?? 9) <=> ($urutan[$b['cara']] ?? 9);
        });

        $ringkasan = [];
        foreach ($baris as $b) {
            $ringkasan[$b['cara']] = ($ringkasan[$b['cara']] ?? 0) + 1;
        }

        return ['ok' => true, 'error' => null, 'baris' => $baris, 'ringkasan' => $ringkasan];
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
    private static function indeksKaryawan(): array
    {
        $baris = db_connect()->table('employees')
            ->select('id, nik, nama, jabatan, email, email_kerja, status')
            ->get()->getResultArray();

        $idx = ['kerja' => [], 'pribadi' => [], 'nama' => []];

        foreach ($baris as $e) {
            // Karyawan yang sudah keluar TIDAK dijadikan kandidat: menautkan
            // akun aktif ke orang yang sudah resign akan langsung memicu
            // antrian pencabutan, dan tautannya sendiri memang salah.
            if ($e['status'] !== 'aktif') continue;

            if (! empty($e['email_kerja'])) $idx['kerja'][self::norm($e['email_kerja'])] = $e;
            if (! empty($e['email']))       $idx['pribadi'][self::norm($e['email'])]     = $e;

            $idx['nama'][self::normNama($e['nama'])][] = $e;
        }

        return $idx;
    }

    /**
     * id_lokal yang sudah tertaut untuk aplikasi ini -> baris tautannya.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function tautanSekarang(string $kodeApp): array
    {
        $baris = db_connect()->table('employee_app_access x')
            ->select('x.id_lokal, x.employee_id, e.nama AS nama_mic, e.nik, r.kode AS peran')
            ->join('apps a', 'a.id = x.app_id')
            ->join('employees e', 'e.id = x.employee_id')
            ->join('app_roles r', 'r.id = x.app_role_id', 'left')
            ->where('a.kode', $kodeApp)
            ->where('x.aktif', 1)
            ->where('x.id_lokal IS NOT NULL')
            ->get()->getResultArray();

        $peta = [];
        foreach ($baris as $b) $peta[(string) $b['id_lokal']] = $b;

        return $peta;
    }

    /**
     * @param array<string,mixed> $akun
     * @param array<string,mixed> $karyawan
     * @param array<string,array<string,mixed>> $tertaut
     * @return array<string,mixed>
     */
    private static function cocokkan(array $akun, array $karyawan, array $tertaut): array
    {
        $idLokal = (string) ($akun['id'] ?? '');

        $b = [
            'id_lokal'      => $idLokal,
            'nama_akun'     => (string) ($akun['nama'] ?? ''),
            'email_akun'    => (string) ($akun['email'] ?? ''),
            'unit_akun'     => (string) ($akun['unit'] ?? ''),
            'jabatan_akun'  => (string) ($akun['jabatan'] ?? ''),
            'aktif_akun'    => ! empty($akun['aktif']),
            'unit_admin'    => ! empty($akun['unit_admin']),
            'peran_usul'    => ! empty($akun['unit_admin']) ? 'unit_admin' : 'user',
            'employee_id'   => null,
            'nik'           => '',
            'nama_mic'      => '',
            'jabatan'       => '',
            'cara'          => '',
            'keyakinan'     => '',
            'usul_centang'  => false,
            'catatan'       => '',
            'sudah_tertaut' => false,
            'tautan_kini'   => null,
        ];

        if (isset($tertaut[$idLokal])) {
            $b['sudah_tertaut'] = true;
            $b['tautan_kini']   = $tertaut[$idLokal];
        }

        if (in_array(self::normNama($b['nama_akun']), self::BUKAN_ORANG, true)) {
            $b['cara']    = 'bukan_orang';
            $b['catatan'] = 'akun sistem, bukan orang';

            return $b;
        }

        $email = self::norm($b['email_akun']);

        if ($email !== '' && isset($karyawan['kerja'][$email])) {
            return self::isiKandidat($b, $karyawan['kerja'][$email], 'email_kerja', 'tertinggi');
        }

        if ($email !== '' && isset($karyawan['pribadi'][$email])) {
            return self::isiKandidat($b, $karyawan['pribadi'][$email], 'email_pribadi', 'tinggi');
        }

        $kandidat = $karyawan['nama'][self::normNama($b['nama_akun'])] ?? [];

        if (count($kandidat) === 1) {
            return self::isiKandidat($b, $kandidat[0], 'nama_persis', 'sedang');
        }

        if (count($kandidat) > 1) {
            // §2: keunikan diperiksa SAAT penautan, bukan mengandalkan hasil
            // pemeriksaan 2 Sep. Karyawan baru bisa membuat nama jadi ambigu.
            $b['cara']      = 'nama_ambigu';
            $b['keyakinan'] = 'perlu konfirmasi';
            $b['catatan']   = count($kandidat) . ' karyawan bernama sama: '
                . implode(', ', array_map(fn ($k) => $k['nik'] . ' ' . $k['jabatan'], $kandidat));

            return $b;
        }

        $b['cara']    = 'tidak_ada';
        $b['catatan'] = 'tidak ada karyawan aktif yang namanya/emailnya cocok';

        return $b;
    }

    /**
     * @param array<string,mixed> $b
     * @param array<string,mixed> $e
     * @return array<string,mixed>
     */
    private static function isiKandidat(array $b, array $e, string $cara, string $keyakinan): array
    {
        $b['employee_id'] = (int) $e['id'];
        $b['nik']         = (string) $e['nik'];
        $b['nama_mic']    = (string) $e['nama'];
        $b['jabatan']     = (string) $e['jabatan'];
        $b['cara']        = $cara;
        $b['keyakinan']   = $keyakinan;

        // Dicentang di layar HANYA sebagai usul yang memudahkan — admin tetap
        // harus menekan tombol simpan, dan boleh membatalkan centangnya.
        // Mencentang bukan menyimpan.
        $b['usul_centang'] = in_array($cara, self::USUL_KUAT, true) && ! $b['sudah_tertaut'];

        return $b;
    }

    public static function norm(?string $s): string
    {
        return mb_strtolower(trim((string) $s));
    }

    /** Nama disamakan spasi gandanya supaya "Budi  Santoso" tidak dianggap beda. */
    public static function normNama(?string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s) ?? ''));
    }

    /** Label manusiawi per cara cocok — dipakai layar dan CLI. */
    public static function label(string $cara): string
    {
        return [
            'email_kerja'   => 'Email kerja sama',
            'email_pribadi' => 'Email pribadi sama',
            'nama_persis'   => 'Nama persis & unik',
            'nama_ambigu'   => 'Nama ambigu',
            'bukan_orang'   => 'Bukan orang',
            'tidak_ada'     => 'Tidak ada di MIC',
        ][$cara] ?? $cara;
    }
}
