<?php

namespace App\Controllers;

use App\Models\StockBarangModel;
use App\Models\StockBarangLogModel;
use App\Models\StockVoucherBatchModel;
use App\Models\StockVoucherLogModel;

class StockCtrl extends BaseController
{
    private function period(): array
    {
        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-t');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-t');
        return [$from, $to];
    }

    // Summary gabungan stok fisik (barang + voucher)
    public function summary()
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        [$from, $to] = $this->period();

        $barang   = (new StockBarangModel())->getAll();
        $bSum     = (new StockBarangLogModel())->getPeriodSummary($from, $to);
        foreach ($barang as &$b) {
            $b['masuk']  = (int)($bSum[$b['id']]['masuk']  ?? 0);
            $b['keluar'] = (int)($bSum[$b['id']]['keluar'] ?? 0);
            $b['nilai_stok'] = (int)$b['stok_tersedia'] * (int)$b['nilai_satuan'];
        }
        unset($b);

        $batches = (new StockVoucherBatchModel())->getAll();
        $vSum    = (new StockVoucherLogModel())->getPeriodSummary($from, $to);
        foreach ($batches as &$v) {
            $v['masuk']  = (int)($vSum[$v['id']]['masuk']  ?? 0);
            $v['keluar'] = (int)($vSum[$v['id']]['keluar'] ?? 0);
            $v['retur']  = (int)($vSum[$v['id']]['retur']  ?? 0);
            $v['nilai_stok'] = (int)$v['sisa_kode'] * (int)$v['nilai_voucher'];
        }
        unset($v);

        return view('stock/summary', [
            'user'           => $this->currentUser(),
            'from'           => $from,
            'to'             => $to,
            'barang'         => $barang,
            'batches'        => $batches,
            'totalNilaiBarang'  => array_sum(array_column($barang, 'nilai_stok')),
            'totalNilaiVoucher' => array_sum(array_column($batches, 'nilai_stok')),
        ]);
    }

    // Kartu stok satu barang
    public function kartuBarang(int $id)
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $barang = (new StockBarangModel())->find($id);
        if (! $barang) return redirect()->to('/stock/summary')->with('error', 'Barang tidak ditemukan.');
        [$from, $to] = $this->period();

        $entries = (new StockBarangLogModel())->getByBarangAsc($id, $from, $to);
        $entries = $this->attachRealisasi($entries, $id, $from, $to);
        return view('stock/kartu_barang', [
            'user'    => $this->currentUser(),
            'barang'  => $barang,
            'entries' => $entries,
            'from'    => $from,
            'to'      => $to,
        ]);
    }


    /**
     * Tautkan baris log 'keluar' (program loyalty / event) ke realisasi (penerima + foto).
     * Log tidak punya id realisasi, jadi dicocokkan per kelompok (tipe, program, tanggal):
     *  - 'pasti'  : 1 log ↔ 1 realisasi, jumlah sama.
     *  - 'urutan' : n log ↔ n realisasi, urutan id & jumlah identik (dibuat bersamaan, urutan sama).
     *  - 'ambigu' : selain itu → semua penerima kelompok itu ditampilkan, tanpa dipasangkan.
     * Hanya baca; tanpa ubah data/skema.
     */
    private function attachRealisasi(array $entries, int $barangId, string $from, string $to): array
    {
        $db  = \Config\Database::connect();
        $src = [
            'loyalty_program' => ['loyalty_hadiah_realisasi', 'loyalty_hadiah_items', 'loyalty'],
            'event_loyalty'   => ['event_loyalty_hadiah_realisasi', 'event_loyalty_hadiah_items', 'event'],
        ];
        $real = []; // "tipe|program|tanggal" => [rows]
        foreach ($src as $tipe => [$rt, $it, $kind]) {
            $rows = $db->table($rt . ' r')
                ->select('r.id, r.program_id, r.tanggal, r.jumlah_dibagikan, r.nama_penerima, r.foto')
                ->join($it . ' i', 'i.id = r.item_id')
                ->where('i.barang_id', $barangId)->where('r.kode_id', null)->where('r.jumlah_dibagikan >', 0)
                ->where('r.tanggal >=', $from)->where('r.tanggal <=', $to)
                ->orderBy('r.id', 'ASC')->get()->getResultArray();
            foreach ($rows as $r) {
                $r['foto_ada'] = ! empty($r['foto']) && is_file(FCPATH . 'uploads/loyalty-realisasi/' . $r['foto']);
                $real[$tipe . '|' . $r['program_id'] . '|' . $r['tanggal']][] = $r;
            }
        }
        $grp = [];
        foreach ($entries as $i => $e) {
            if ($e['tipe'] !== 'keluar' || ! isset($src[$e['referensi_tipe'] ?? ''])) continue;
            $grp[$e['referensi_tipe'] . '|' . $e['referensi_id'] . '|' . $e['tanggal']][] = $i;
        }
        foreach ($grp as $key => $idxs) {
            $rs = $real[$key] ?? [];
            if (! $rs) continue;
            $sameCount = count($rs) === count($idxs);
            $sameQty = $sameCount;
            if ($sameCount) foreach ($idxs as $n => $i) if ((int) $entries[$i]['jumlah'] !== (int) $rs[$n]['jumlah_dibagikan']) { $sameQty = false; break; }
            foreach ($idxs as $n => $i) {
                if ($sameQty) {
                    $entries[$i]['realisasi'] = ['mode' => count($idxs) === 1 ? 'pasti' : 'urutan', 'rows' => [$rs[$n]]];
                } else {
                    $entries[$i]['realisasi'] = ['mode' => 'ambigu', 'rows' => $rs];
                }
            }
        }
        return $entries;
    }

    // Kartu stok satu batch voucher
    public function kartuVoucher(int $batchId)
    {
        if (! $this->canViewMenu('loyalty_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }
        $batch = (new StockVoucherBatchModel())->find($batchId);
        if (! $batch) return redirect()->to('/stock/summary')->with('error', 'Batch tidak ditemukan.');
        [$from, $to] = $this->period();

        $all     = (new StockVoucherLogModel())->getByBatch($batchId);
        $entries = array_filter($all, fn($e) => $e['tanggal'] >= $from && $e['tanggal'] <= $to);

        // Resolusi nama program untuk entri yg referensinya program loyalty
        $progIds = [];
        foreach ($entries as $e) {
            if (in_array($e['referensi_tipe'], ['program', 'program_batal'], true) && $e['referensi_id']) {
                $progIds[] = (int)$e['referensi_id'];
            }
        }
        $progNames = [];
        if ($progIds) {
            foreach ((new \App\Models\LoyaltyProgramModel())->whereIn('id', array_unique($progIds))->findAll() as $p) {
                $progNames[(int)$p['id']] = $p['nama_program'];
            }
        }

        return view('stock/kartu_voucher', [
            'user'      => $this->currentUser(),
            'batch'     => $batch,
            'entries'   => array_values($entries),
            'progNames' => $progNames,
            'from'      => $from,
            'to'        => $to,
        ]);
    }
}
