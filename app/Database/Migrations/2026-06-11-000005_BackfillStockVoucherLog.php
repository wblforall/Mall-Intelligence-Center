<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rekonstruksi mutasi voucher fisik dari data kode yang sudah ada
 * (untuk distribusi/import sebelum fitur log dibuat).
 *   masuk  = saat kode dibuat (created_at)
 *   keluar = saat kode di-assign (assigned_at)
 * Hanya jalan bila tabel log masih kosong, agar tidak menggandakan data.
 */
class BackfillStockVoucherLog extends Migration
{
    public function up()
    {
        $db = $this->db;
        if ((int)$db->table('stock_voucher_log')->countAllResults() > 0) {
            return; // sudah ada data, jangan backfill
        }

        $batchIds = $db->table('stock_voucher_kode')->select('batch_id')->distinct()->get()->getResultArray();
        $now = date('Y-m-d H:i:s');

        foreach ($batchIds as $b) {
            $batchId = (int)$b['batch_id'];

            $masuk = $db->table('stock_voucher_kode')
                ->select("DATE(created_at) AS d, COUNT(*) AS c")
                ->where('batch_id', $batchId)->where('created_at IS NOT NULL', null, false)
                ->groupBy('d')->get()->getResultArray();
            $keluar = $db->table('stock_voucher_kode')
                ->select("DATE(assigned_at) AS d, COUNT(*) AS c")
                ->where('batch_id', $batchId)->where('status', 'assigned')
                ->where('assigned_at IS NOT NULL', null, false)
                ->groupBy('d')->get()->getResultArray();

            $events = [];
            foreach ($masuk as $m)  $events[] = ['d' => $m['d'], 'tipe' => 'masuk',  'c' => (int)$m['c'], 'o' => 0];
            foreach ($keluar as $k) $events[] = ['d' => $k['d'], 'tipe' => 'keluar', 'c' => (int)$k['c'], 'o' => 1];
            // urutkan kronologis; pada tanggal sama, masuk dulu baru keluar
            usort($events, fn($a, $z) => ($a['d'] <=> $z['d']) ?: ($a['o'] <=> $z['o']));

            $saldo = 0;
            $rows  = [];
            foreach ($events as $e) {
                $sebelum = $saldo;
                $saldo  += $e['tipe'] === 'keluar' ? -$e['c'] : $e['c'];
                $rows[] = [
                    'batch_id'       => $batchId,
                    'tipe'           => $e['tipe'],
                    'jumlah'         => $e['c'],
                    'saldo_sebelum'  => $sebelum,
                    'saldo_sesudah'  => $saldo,
                    'referensi_tipe' => 'backfill',
                    'referensi_id'   => null,
                    'tanggal'        => $e['d'] ?: date('Y-m-d'),
                    'catatan'        => 'Rekonstruksi data sebelum fitur log',
                    'created_by'     => null,
                    'created_at'     => $now,
                ];
            }
            if ($rows) $db->table('stock_voucher_log')->insertBatch($rows);
        }
    }

    public function down()
    {
        $this->db->table('stock_voucher_log')->where('referensi_tipe', 'backfill')->delete();
    }
}
