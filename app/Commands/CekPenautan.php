<?php

namespace App\Commands;

use App\Libraries\PenautanAkses;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Periksa keadaan penautan akun sebuah aplikasi. HANYA MELAPORKAN.
 *
 *   php spark mic:cek-penautan esign
 *
 * Perintah ini SENGAJA TIDAK BISA MENULIS. Penautan dilakukan admin di layar
 * Penautan Akun, satu per satu, karena tiga akibatnya tidak boleh lahir dari
 * sebuah perintah yang dijalankan sekali:
 *
 *   - Peran `unit_admin` bisa mengubah template alur persetujuan.
 *   - Tautan yang salah menonaktifkan akun ORANG LAIN saat karyawan resign.
 *   - Penautan memindahkan kredensial ke MIC dan mematikan sandi lokal.
 *
 * Versi pertama perintah ini punya `--terapkan` yang menulis 48 tautan
 * sekaligus. Itu dibuang: keyakinan "tinggi" pada pencocokan email bukan
 * persetujuan, dan hanya orang yang bisa memberi persetujuan.
 *
 * Gunanya sekarang: melihat cepat berapa yang belum tertaut dan apa yang
 * menghalangi, tanpa perlu membuka peramban — mis. saat memeriksa server.
 */
class CekPenautan extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:cek-penautan';
    protected $description = 'Laporkan keadaan penautan akun sebuah aplikasi (hanya membaca).';
    protected $usage       = 'mic:cek-penautan <kode_app> [--csv <berkas>]';
    protected $arguments   = ['kode_app' => 'Kode aplikasi di tabel `apps` (mis. esign)'];
    protected $options     = ['--csv' => 'Simpan rincian per akun ke berkas CSV'];

    public function run(array $params)
    {
        $kodeApp = $params[0] ?? CLI::getSegment(2);

        if (! $kodeApp) {
            CLI::error('Kode aplikasi wajib. Contoh: php spark mic:cek-penautan esign');

            return;
        }

        $app = db_connect()->table('apps')->select('id, kode, nama')
            ->where('kode', $kodeApp)->get()->getRowArray();

        if (! $app) {
            CLI::error('Aplikasi "' . $kodeApp . '" tidak ada di tabel apps.');

            return;
        }

        CLI::write('Menarik daftar akun dari ' . $app['nama'] . ' ...', 'yellow');

        $usul = PenautanAkses::usulkan($kodeApp);
        if (! $usul['ok']) {
            CLI::error('Gagal: ' . $usul['error']);

            return;
        }

        $baris = $usul['baris'];
        CLI::write('  ' . count($baris) . ' akun diterima.', 'green');
        CLI::newLine();

        $sudah = count(array_filter($baris, fn ($b) => $b['sudah_tertaut']));
        $bukanOrang = count(array_filter($baris, fn ($b) => $b['cara'] === 'bukan_orang'));

        CLI::write('Keadaan', 'yellow');
        CLI::write('  Sudah tertaut        : ' . CLI::color((string) $sudah, 'green'));
        CLI::write('  Bukan orang          : ' . $bukanOrang);
        CLI::write('  Belum tertaut        : ' . (count($baris) - $sudah - $bukanOrang));
        CLI::newLine();

        CLI::write('Dasar cocok untuk yang belum tertaut', 'yellow');
        foreach ($usul['ringkasan'] as $cara => $n) {
            if ($cara === 'bukan_orang') continue;
            $kuat  = in_array($cara, PenautanAkses::USUL_KUAT, true);
            $label = str_pad(PenautanAkses::label($cara), 22);
            CLI::write('  ' . $label . CLI::color((string) $n, $kuat ? 'green' : 'light_red'));
        }

        // Hanya yang benar-benar akan datang tercentang. Menghitung SEMUA
        // pemegang unit_admin akan melebihkan angkanya: yang tidak punya
        // kandidat karyawan tidak diusulkan sama sekali.
        $unitAdmin = count(array_filter(
            $baris,
            fn ($b) => $b['usul_centang'] && $b['peran_usul'] === 'unit_admin'
        ));

        if ($unitAdmin > 0) {
            CLI::newLine();
            CLI::write('  ' . $unitAdmin . ' usul tercentang berperan unit_admin —', 'light_red');
            CLI::write('  peran itu bisa MENGUBAH TEMPLATE ALUR PERSETUJUAN. Tinjau di layar.', 'light_red');
        }

        if ($berkas = CLI::getOption('csv')) {
            $this->tulisCsv((string) $berkas, $baris);
            CLI::newLine();
            CLI::write('Rincian: ' . CLI::color((string) $berkas, 'blue'));
        }

        CLI::newLine();
        CLI::write('Penautan dilakukan di layar: Portal → Penautan Akun.', 'yellow');
        CLI::write('Perintah ini tidak menulis apa pun.');
    }

    /** @param array<int,array<string,mixed>> $baris */
    private function tulisCsv(string $berkas, array $baris): void
    {
        $f = @fopen($berkas, 'w');
        if ($f === false) {
            CLI::error('Tidak bisa menulis ' . $berkas);

            return;
        }

        fputcsv($f, ['id_lokal', 'nama_akun', 'email_akun', 'akun_aktif', 'sudah_tertaut',
                     'peran_usul', 'employee_id', 'nik', 'nama_mic', 'jabatan',
                     'dasar_cocok', 'keyakinan', 'catatan']);

        foreach ($baris as $b) {
            fputcsv($f, [
                $b['id_lokal'], $b['nama_akun'], $b['email_akun'],
                $b['aktif_akun'] ? 'ya' : 'tidak', $b['sudah_tertaut'] ? 'ya' : 'tidak',
                $b['peran_usul'], $b['employee_id'], $b['nik'], $b['nama_mic'], $b['jabatan'],
                $b['cara'], $b['keyakinan'], $b['catatan'],
            ]);
        }

        fclose($f);
    }
}
