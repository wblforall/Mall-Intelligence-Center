<?php

namespace App\Libraries;

use App\Controllers\BudgetDept;
use App\Models\BudgetDeptSettingModel;
use App\Models\BudgetPosModel;
use App\Models\BudgetRealisasiModel;

/**
 * "Catat" satu item BTB FlowStore ke budget_realisasi dept — satu jalur untuk tab FlowStore Budget Departemen dan
 * realisasi Creative yang menautkan nomor PO (keputusan pemilik 5 Nov 2026: CCD mencatat realisasi di Creative lengkap
 * dengan bukti, memilih PO FlowStore, dan baris BTB-nya ikut tercatat di Budget Departemen tanpa Catat ulang).
 *
 * Hak akses diperiksa PEMANGGIL. Di sini hanya aturan data: pos milik dept & aktif, bulan sah di tahun budget yang sudah
 * dibuka, mal sah, status antrean 'baru', BTB tidak batal, nilai > 0.
 */
class BudgetFsCatat
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect();
    }

    /**
     * @param array      $x       ['id' => id_btb_item, 'pos' => pos_id, 'bulan' => 'YYYY-MM', 'mall' => kode mal]
     * @param array|null $cref    [scope, item_id] tautan item Creative (hanya dept CCD)
     * @param int|null   $crRealId id baris realisasi Creative pemilik item ini (event_creative_realisasi / creative_realisasi menurut scope)
     * @return array{0:bool,1:string,2:?int} [berhasil, pesan galat, id budget_realisasi]
     */
    public function satu(array $dept, int $tahun, array $x, int $uid, ?array $cref = null, ?int $crRealId = null): array
    {
        $deptId = (int) $dept['id'];
        $id = (int) ($x['id'] ?? 0);
        $db = $this->db;
        $setting = new BudgetDeptSettingModel();
        $pisah = $setting->pisahMal($deptId);

        $posId = (int) ($x['pos'] ?? 0);
        $pos = $posId ? (new BudgetPosModel())->milik($posId, $deptId) : null;
        if (! $pos) return [false, '#' . $id . ': pilih pos dulu.', null];
        if (empty($pos['aktif'])) return [false, '#' . $id . ': pos "' . $pos['nama'] . '" nonaktif.', null];
        $bulan = (string) ($x['bulan'] ?? '');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) return [false, '#' . $id . ': bulan pembebanan tidak sah.', null];
        if ((int) substr($bulan, 0, 4) !== $tahun) return [false, '#' . $id . ': bulan pembebanan harus di tahun budget ' . $tahun . '.', null];
        if ($tahun < $setting->tahunMulai($deptId, BudgetDept::TAHUN_MULAI)) return [false, '#' . $id . ': tahun ' . $tahun . ' belum dibuka untuk dept ini.', null];
        $mal = (string) ($x['mall'] ?? '');
        if ($pisah && ! isset(BudgetRekap::MAL_REALISASI[$mal])) return [false, '#' . $id . ': mal tidak sah.', null];
        if (! $pisah) $mal = '';

        $db->transStart();
        // Kunci baris pemetaan: klik ganda / POST ulang menunggu di sini lalu mendapati status sudah 'dicatat'.
        $p = $db->query('SELECT * FROM budget_fs_pemetaan WHERE id_btb_item = ? AND dept_id = ? FOR UPDATE', [$id, $deptId])->getRowArray();
        $f = $p ? $db->table('fs_btb_item')->where('id_btb_item', $id)->get()->getRowArray() : null;
        $galat = null; $rid = null; $data = [];
        if (! $p || ! $f) $galat = '#' . $id . ': tidak ada di antrean departemen ini.';
        elseif ($p['status'] !== 'baru') $galat = '#' . $id . ': sudah ' . strtolower(\App\Controllers\BudgetFlowstore::STATUS[$p['status']] ?? $p['status']) . ', tidak dicatat ulang.';
        elseif ($f['status_btb'] === 'batal') $galat = '#' . $id . ': BTB dibatalkan di FlowStore.';
        elseif ((float) $f['nilai_diterima'] <= 0) $galat = '#' . $id . ' ' . $f['nama_barang'] . ': nilai Rp 0 — gunakan Abaikan.';

        if ($galat === null) {
            $terima = (string) ($f['tanggal_diterima'] ?: $f['tanggal_btb']);
            $bulanTerima = substr($terima, 0, 7);
            $tanggal = $bulan === $bulanTerima ? $terima : date('Y-m-t', strtotime($bulan . '-01'));
            $qty = rtrim(rtrim(number_format((float) $f['jumlah_diterima'], 2, ',', ''), '0'), ',');
            $akhiran = ' ×' . $qty . ($f['satuan'] ? ' ' . $f['satuan'] : '') . ' — BTB ' . $f['no_btb'] . ' / PO ' . $f['no_po'];
            $uraian = mb_substr($f['nama_barang'], 0, max(20, 255 - mb_strlen($akhiran))) . $akhiran;
            $data = [
                'dept_id' => $deptId, 'pos_id' => (int) $pos['id'], 'tanggal' => $tanggal,
                'periode_asal' => $bulanTerima !== $bulan ? $bulanTerima : null,
                'uraian' => mb_substr($uraian, 0, 255), 'keterangan' => \App\Controllers\BudgetFlowstore::keteranganCatat($f),
                'nilai' => $f['nilai_diterima'], 'mall' => $mal,
                'referensi' => mb_substr('FlowStore BTB ' . $f['no_btb'] . ' #' . $id, 0, 100),
                'created_by' => $uid, 'updated_by' => $uid,
            ];
            $rek = (new BudgetFsRekomendasi($db))->untuk($deptId, $f);
            $rid = (new BudgetRealisasiModel())->insert($data);
            $db->table('budget_fs_pemetaan')->where('id', $p['id'])->update([
                'status' => 'dicatat', 'pos_id' => (int) $pos['id'], 'bulan_beban' => $bulan,
                'mall' => $mal !== '' ? $mal : BudgetFsRekomendasi::malDariSkema($f['id_skema'] !== null ? (int) $f['id_skema'] : null, $f['skema']),
                'realisasi_id' => $rid, 'catatan_tinjau' => null, 'alasan_abaikan' => null, 'diperbarui_oleh' => $uid, 'dibuat_oleh' => $p['dibuat_oleh'] ?: $uid,
                'rekomendasi_pos_id' => $rek['pos_id'], 'rekomendasi_sumber' => $rek['sumber'], 'rekomendasi_skor' => $rek['skor'],
                'rekomendasi_alasan' => mb_substr($rek['alasan'], 0, 255), 'updated_at' => date('Y-m-d H:i:s'),
                'creative_scope' => $cref[0] ?? null, 'creative_item_id' => $cref[1] ?? null, 'creative_realisasi_id' => $crRealId,
            ]);
            (new BudgetFsRekomendasi($db))->ingat($deptId, $f, (int) $pos['id']);
        }
        $db->transComplete();
        if ($galat === null && ! $db->transStatus()) $galat = '#' . $id . ': gagal menyimpan.';
        if ($galat !== null) return [false, $galat, null];

        ActivityLog::captureAfter($data);
        ActivityLog::write('create', 'budget_realisasi', (string) $rid, $data['uraian'], ['sumber' => 'flowstore', 'id_btb_item' => $id]
            + ($cref ? ['creative' => CreativeBiayaFs::ref($cref[0], $cref[1])] : []) + ($crRealId ? ['realisasi_creative' => $crRealId] : []));
        return [true, '', (int) $rid];
    }
}
