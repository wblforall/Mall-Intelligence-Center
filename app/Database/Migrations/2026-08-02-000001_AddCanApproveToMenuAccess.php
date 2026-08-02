<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tingkat akses ketiga: SETUJUI.
 *
 * Sebelumnya akses menu hanya punya lihat/ubah, sedangkan hak menyetujui
 * hanya bisa diberikan lewat flag global di tabel `roles` (can_approve_*).
 * Akibatnya untuk memberi hak setuju ke SATU orang, terpaksa mengaktifkan
 * flag di role — yang otomatis mengenai semua pemegang role itu (mis. 23
 * user ber-role Manager), atau menjadikannya admin.
 *
 * Dengan kolom ini, hak setuju bisa diberikan per-user / per-dept persis
 * seperti lihat & ubah. Flag lama di `roles` TETAP berlaku (aditif), jadi
 * tak ada perilaku existing yang berubah.
 */
class AddCanApproveToMenuAccess extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE user_menu_access ADD COLUMN can_approve TINYINT(1) NOT NULL DEFAULT 0 AFTER can_edit');
        $this->db->query('ALTER TABLE department_menu_access ADD COLUMN can_approve TINYINT(1) NOT NULL DEFAULT 0 AFTER can_edit');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE user_menu_access DROP COLUMN can_approve');
        $this->db->query('ALTER TABLE department_menu_access DROP COLUMN can_approve');
    }
}
