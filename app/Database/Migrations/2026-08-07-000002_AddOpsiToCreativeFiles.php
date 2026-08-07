<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menjadikan file materi creative sebagai "opsi desain" yang bisa dipilih.
 *
 * Sebelumnya file hanyalah galeri datar tanpa penanda apa pun, sehingga
 * ketika desainer mengirim beberapa alternatif tidak ada cara untuk tahu
 * mana yang akhirnya dipakai produksi selain bertanya langsung.
 *
 * - is_opsi     : 0 untuk file pendukung (brief/referensi) yang bukan kandidat
 * - opsi_label  : label otomatis A, B, C ... per item
 * - is_terpilih : hasil keputusan peninjau. Flag di file (bukan kolom
 *                 penunjuk tunggal di item) supaya kasus dua opsi disetujui
 *                 sekaligus — mis. versi portrait + landscape — tetap muat.
 * - versi       : putaran review saat file diunggah
 *
 * Backfill: file lama diberi label berurutan agar tampil rapi, tetapi
 * is_terpilih sengaja dibiarkan 0 — termasuk pada item yang sudah
 * berstatus approved — karena keputusan "opsi mana" memang belum pernah
 * diambil. UI menampilkannya sebagai "opsi final belum ditentukan".
 */
class AddOpsiToCreativeFiles extends Migration
{
    /** @var string[] */
    private array $tables = ['creative_files', 'event_creative_files'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->db->query("ALTER TABLE {$table}
                ADD COLUMN is_opsi TINYINT(1) NOT NULL DEFAULT 1 AFTER original_name,
                ADD COLUMN opsi_label VARCHAR(10) NULL AFTER is_opsi,
                ADD COLUMN is_terpilih TINYINT(1) NOT NULL DEFAULT 0 AFTER opsi_label,
                ADD COLUMN versi INT UNSIGNED NOT NULL DEFAULT 1 AFTER is_terpilih");

            $this->backfillLabels($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $this->db->query("ALTER TABLE {$table}
                DROP COLUMN is_opsi,
                DROP COLUMN opsi_label,
                DROP COLUMN is_terpilih,
                DROP COLUMN versi");
        }
    }

    private function backfillLabels(string $table): void
    {
        $rows = $this->db->table($table)
            ->select('id, creative_item_id')
            ->orderBy('creative_item_id', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $seen = [];
        foreach ($rows as $row) {
            $itemId = (int) $row['creative_item_id'];
            $seen[$itemId] = ($seen[$itemId] ?? 0) + 1;
            $this->db->table($table)
                ->where('id', (int) $row['id'])
                ->update(['opsi_label' => $this->label($seen[$itemId])]);
        }
    }

    /** 1 => A, 2 => B, ... 27 => AA */
    private function label(int $n): string
    {
        $out = '';
        while ($n > 0) {
            $n--;
            $out = chr(65 + ($n % 26)) . $out;
            $n = intdiv($n, 26);
        }
        return $out;
    }
}
