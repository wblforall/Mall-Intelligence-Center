<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dua hal sekaligus, keduanya menopang pengkinian data mandiri karyawan.
 *
 * 1) Nomor identitas — `no_kk` & `no_npwp`. Nomor KTP TIDAK ditambahkan
 *    karena sudah ada sebagai `nik_ktp` (terisi 201 dari 202 karyawan);
 *    menambah kolom kedua hanya akan memecah sumber kebenaran.
 *
 * 2) Sertifikat bisa diunggah karyawan sendiri, HR tinggal memverifikasi.
 *    Sebelumnya `employee_certificates` hanya bisa diisi HR dan tidak punya
 *    konsep "menunggu verifikasi" sama sekali.
 *
 *    Kolom `status` sengaja DEFAULT 'pending': jalur HR menuliskan
 *    'approved' secara eksplisit, sehingga bila suatu saat ada jalur baru
 *    yang lupa menyetel status, hasilnya tertahan untuk diperiksa —
 *    bukan lolos diam-diam sebagai sudah terverifikasi.
 *
 *    Field tambahan (jenis, level, bidang, url_verifikasi, pembiayaan)
 *    membuat sertifikat bisa DIOLAH, bukan sekadar diarsipkan: dikelompokkan
 *    per jenis, dicari per bidang keahlian, dan dinilai kesetaraannya lewat
 *    level. `competency_id` disiapkan untuk menaut ke master kompetensi —
 *    dibiarkan NULL karena tabel `competencies` masih kosong.
 *
 *    `catatan` yang sudah ada tetap milik karyawan; alasan penolakan HR
 *    ditaruh terpisah di `catatan_review` agar keduanya tidak saling timpa.
 */
class AddIdentitasDanSertifikatMandiri extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE employees
            ADD COLUMN no_kk VARCHAR(20) NULL AFTER nik_ktp,
            ADD COLUMN no_npwp VARCHAR(30) NULL AFTER no_kk");

        $this->db->query("ALTER TABLE employee_certificates
            ADD COLUMN jenis VARCHAR(30) NULL AFTER nama_sertifikat,
            ADD COLUMN bidang VARCHAR(150) NULL AFTER jenis,
            ADD COLUMN level VARCHAR(60) NULL AFTER bidang,
            ADD COLUMN competency_id INT UNSIGNED NULL AFTER level,
            ADD COLUMN url_verifikasi VARCHAR(255) NULL AFTER penerbit,
            ADD COLUMN pembiayaan VARCHAR(20) NULL AFTER url_verifikasi,
            ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER catatan,
            ADD COLUMN uploaded_by INT UNSIGNED NULL AFTER status,
            ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER uploaded_by,
            ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
            ADD COLUMN catatan_review TEXT NULL AFTER reviewed_at,
            ADD KEY status (status)");

        // Sertifikat yang terlanjur ada dimasukkan HR lewat form lama, jadi
        // memang sudah terverifikasi. Tanpa ini semuanya mendadak berstatus
        // pending dan membanjiri Kotak Persetujuan dengan data lama.
        $this->db->table('employee_certificates')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE employees
            DROP COLUMN no_kk,
            DROP COLUMN no_npwp");

        $this->db->query("ALTER TABLE employee_certificates
            DROP KEY status,
            DROP COLUMN jenis,
            DROP COLUMN bidang,
            DROP COLUMN level,
            DROP COLUMN competency_id,
            DROP COLUMN url_verifikasi,
            DROP COLUMN pembiayaan,
            DROP COLUMN status,
            DROP COLUMN uploaded_by,
            DROP COLUMN reviewed_by,
            DROP COLUMN reviewed_at,
            DROP COLUMN catatan_review");
    }
}
