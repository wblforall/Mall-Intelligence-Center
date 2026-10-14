<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetRealisasiModel extends Model
{
    protected $table         = 'budget_realisasi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'pos_id', 'tanggal', 'periode_asal', 'uraian', 'keterangan',
                                'nilai', 'mall', 'referensi', 'created_by', 'updated_by'];
    protected $useTimestamps = true;

    /** Baris ringkas untuk BudgetRekap::susun() — bulan = bulan pembebanan. */
    public function untukRekap(int $deptId, int $tahun): array
    {
        $rows = $this->db->query(
            "SELECT pos_id, MONTH(tanggal) AS bulan, mall, SUM(nilai) AS nilai
               FROM budget_realisasi WHERE dept_id = ? AND tanggal BETWEEN ? AND ?
              GROUP BY pos_id, MONTH(tanggal), mall",
            [$deptId, "$tahun-01-01", "$tahun-12-31"]
        )->getResultArray();
        foreach ($this->dariEvent($deptId, "$tahun-01", "$tahun-12") as $x) {
            $rows[] = ['pos_id' => $x['pos_id'], 'bulan' => (int) substr($x['beban'], 5, 2), 'mall' => $x['mall'], 'nilai' => $x['nilai']];
        }
        return $rows;
    }

    public function totalTahun(int $deptId, int $tahun): array
    {
        $rows = $this->db->query(
            "SELECT pos_id, mall, SUM(nilai) AS nilai FROM budget_realisasi
              WHERE dept_id = ? AND tanggal BETWEEN ? AND ? GROUP BY pos_id, mall",
            [$deptId, "$tahun-01-01", "$tahun-12-31"]
        )->getResultArray();
        foreach ($this->dariEvent($deptId, "$tahun-01", "$tahun-12") as $x) {
            $rows[] = ['pos_id' => $x['pos_id'], 'mall' => $x['mall'], 'nilai' => $x['nilai']];
        }
        return $rows;
    }

    public function daftar(int $deptId, string $dari, string $sampai, ?int $posId, string $cari): array
    {
        $q = $this->db->table('budget_realisasi x')
            ->select("x.*, 'manual' AS sumber, p.nama AS pos_nama, u.name AS pembuat")
            ->join('budget_pos p', 'p.id = x.pos_id')
            ->join('users u', 'u.id = x.created_by', 'left')
            ->where('x.dept_id', $deptId)
            ->where('x.tanggal >=', $dari)->where('x.tanggal <=', $sampai);
        if ($posId) $q->where('x.pos_id', $posId);
        if ($cari !== '') {
            $q->groupStart()->like('x.uraian', $cari)->orLike('x.keterangan', $cari)->orLike('x.referensi', $cari)->groupEnd();
        }
        $rows = $q->orderBy('x.tanggal', 'DESC')->orderBy('x.id', 'DESC')->get()->getResultArray();

        // Realisasi dari Event Content tampil (hanya baca) di daftar yang sama.
        $cariL = mb_strtolower($cari);
        foreach ($this->dariEvent($deptId, substr($dari, 0, 7), substr($sampai, 0, 7)) as $x) {
            if ($posId && (int) $x['pos_id'] !== $posId) continue;
            $uraian = $x['item_nama'] . ' — ' . $x['event_nama'];
            if ($cariL !== '' && ! str_contains(mb_strtolower($uraian . ' ' . $x['catatan']), $cariL)) continue;
            $rows[] = [
                'id' => null, 'sumber' => 'event', 'event_id' => (int) $x['event_id'], 'item_id' => (int) $x['item_id'],
                'pos_id' => (int) $x['pos_id'], 'pos_nama' => $x['pos_nama'], 'tanggal' => $x['tanggal'], 'periode_asal' => null,
                'uraian' => $uraian, 'keterangan' => $x['catatan'], 'nilai' => $x['nilai'], 'mall' => $x['mall'],
                'referensi' => null, 'pembuat' => $x['pembuat'],
            ];
        }
        usort($rows, fn ($a, $b) => [$b['tanggal'], (int) ($b['id'] ?? 0)] <=> [$a['tanggal'], (int) ($a['id'] ?? 0)]);
        return $rows;
    }

    /**
     * Realisasi dari modul Events (Event Content) yang jatuh ke dept ini lewat
     * pos item/event — dibaca langsung, tidak disalin, supaya hanya ada satu
     * tempat input. Bulan beban = bulan item (atau bulan mulai event); hanya
     * mulai budget_dept_setting.event_mulai (sebelum itu realisasi dept sudah
     * tercatat dari Excel). Item tanpa pos tidak ikut — halaman RAB menandainya.
     * Tanggal tampil = tanggal realisasi bila sebulan dengan bulan beban, selain
     * itu akhir bulan beban (supaya daftar & rekap selalu sepakat).
     */
    public function dariEvent(int $deptId, string $dariYm, string $sampaiYm): array
    {
        $mulai = (new BudgetDeptSettingModel())->eventMulai($deptId);
        if (! $mulai) return [];
        if ($dariYm < $mulai) $dariYm = $mulai;
        if ($dariYm > $sampaiYm) return [];

        return $this->db->query(
            "SELECT x.*, p.nama AS pos_nama, u.name AS pembuat,
                    IF(DATE_FORMAT(x.tgl_real, '%Y-%m') = x.beban, x.tgl_real, LAST_DAY(CONCAT(x.beban, '-01'))) AS tanggal
               FROM (SELECT r.id, r.event_id, e.name AS event_nama, i.id AS item_id, i.nama AS item_nama,
                            COALESCE(i.pos_id, e.pos_id) AS pos_id,
                            DATE_FORMAT(COALESCE(i.tanggal, e.start_date), '%Y-%m') AS beban,
                            COALESCE(NULLIF(i.mall, ''), IF(e.mall IN ('ewalk', 'pentacity'), e.mall, 'both')) AS mall,
                            r.nilai, r.tanggal AS tgl_real, r.catatan, r.created_by
                       FROM event_content_realisasi r
                       JOIN event_content_items i ON i.id = r.content_item_id
                       JOIN events e ON e.id = r.event_id) x
               JOIN budget_pos p ON p.id = x.pos_id AND p.dept_id = ?
               LEFT JOIN users u ON u.id = x.created_by
              WHERE x.beban BETWEEN ? AND ?
              ORDER BY x.beban, x.event_id, x.item_id, x.id",
            [$deptId, $dariYm, $sampaiYm]
        )->getResultArray();
    }
}
