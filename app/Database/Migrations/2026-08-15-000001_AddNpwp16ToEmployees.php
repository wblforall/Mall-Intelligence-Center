<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pisahkan NPWP format lama (15 digit) dan format baru (16 digit).
 *
 * Sebelumnya `no_npwp` menerima keduanya, sehingga isi kolom itu bercampur
 * dua format tanpa bisa dibedakan selain dari panjangnya — permintaan
 * sesederhana "daftar NPWP seluruh karyawan" jadi menghasilkan campuran yang
 * harus ditebak satu per satu.
 *
 * Sejak DJP bermigrasi, NPWP-16 untuk WNI perorangan SAMA dengan NIK. Itu
 * membuat kolom ini tampak mubazir, tetapi justru memberi pemeriksaan silang
 * gratis: bila `no_npwp16` tidak sama dengan `nik_ktp`, salah satunya pasti
 * salah ketik. Pengecualian tetap ada — WNA atau yang belum ber-NIK mendapat
 * 16 digit yang bukan NIK — sehingga kolomnya tidak bisa sekadar diturunkan
 * dari `nik_ktp`.
 *
 * Nilai 16 digit yang terlanjur masuk `no_npwp` dipindahkan, bukan dibuang:
 * membiarkannya di sana akan gagal validasi baru (tepat 15 digit) dan
 * karyawan diminta mengisi ulang data yang sebenarnya sudah benar.
 */
class AddNpwp16ToEmployees extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE employees
            ADD COLUMN no_npwp16 VARCHAR(30) NULL AFTER no_npwp");

        // Pindahkan yang terlanjur 16 digit ke kolom yang benar.
        $this->db->query("UPDATE employees
            SET no_npwp16 = no_npwp, no_npwp = NULL
            WHERE no_npwp IS NOT NULL
              AND CHAR_LENGTH(REGEXP_REPLACE(no_npwp, '[^0-9]', '')) = 16");
    }

    public function down(): void
    {
        // Kembalikan dulu isinya agar tidak hilang saat kolomnya dibuang.
        $this->db->query("UPDATE employees
            SET no_npwp = no_npwp16
            WHERE (no_npwp IS NULL OR no_npwp = '') AND no_npwp16 IS NOT NULL");

        $this->db->query("ALTER TABLE employees DROP COLUMN no_npwp16");
    }
}
