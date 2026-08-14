<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Memecah data pendidikan menjadi kolom-kolom terpisah.
 *
 * Sebelumnya seluruh riwayat pendidikan menumpuk di satu kolom teks bebas
 * `pendidikan`, sehingga isinya bercampur — jenjang, nama sekolah, jurusan,
 * dan tahun lulus tertulis dalam satu baris dengan format berbeda-beda
 * ("S1 Universitas Gajah Mada (2015)", "SMK Listrik", "UNIBA"). Akibatnya
 * jenjang tidak bisa diagregasi dengan benar dan sebagian karyawan bahkan
 * tak diketahui jenjangnya karena hanya nama kampus yang tercatat.
 *
 * Mulai sekarang:
 *   pendidikan  → HANYA jenjang, dipilih dari dropdown baku (SD..S3)
 *   institusi   → nama sekolah / perguruan tinggi
 *   jurusan     → jurusan atau fakultas (kolom lama, dipakai ulang)
 *   ipk         → hanya relevan untuk jenjang di atas SMA/SMK sederajat
 *   tahun_lulus → tahun kelulusan
 *
 * Kolom baru sengaja NULL semua — tidak ada backfill otomatis. Nilai lama
 * seperti "UNIBA" tidak bisa ditebak jenjangnya (D3 atau S1?), dan menebak
 * akan menghasilkan data yang tampak sahih padahal karangan. Pengisiannya
 * diserahkan ke karyawan lewat pengajuan mandiri di /profile.
 */
class AddPendidikanDetailToEmployees extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE employees
            ADD COLUMN institusi VARCHAR(150) NULL AFTER pendidikan,
            ADD COLUMN ipk DECIMAL(3,2) NULL AFTER jurusan,
            ADD COLUMN tahun_lulus SMALLINT UNSIGNED NULL AFTER ipk");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE employees
            DROP COLUMN institusi,
            DROP COLUMN ipk,
            DROP COLUMN tahun_lulus");
    }
}
