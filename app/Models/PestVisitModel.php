<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Kunjungan pest — satu baris per (mall, tanggal).
 *
 * Seluruh agregasi mingguan/bulanan tinggal di sini; controller tidak boleh
 * menyusun query agregat sendiri (konvensi MIC).
 *
 * Dua definisi periode yang SENGAJA berbeda, jangan disamakan:
 *  - mingguan → YEARWEEK(tanggal, 1), minggu ISO penuh, boleh lintas bulan.
 *  - bulanan  → DATE_FORMAT(tanggal, '%Y-%m'), selalu tepat karena satu
 *               kunjungan tidak pernah membelah bulan.
 */
class PestVisitModel extends Model
{
    protected $table         = 'pest_visits';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['mall', 'tanggal', 'vendor', 'petugas', 'sumber',
                                'catatan', 'created_by', 'created_at', 'updated_at'];
    protected $useTimestamps = false;

    public const MALLS = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];

    /**
     * Kunjungan NYATA pada satu tanggal.
     *
     * Sengaja menyaring sumber='kunjungan': baris 'rekap_legacy' mewakili satu
     * bulan, bukan satu hari, dan tidak boleh ikut terambil oleh form input —
     * kalau ikut, pengguna yang membuka tanggal itu menghadapi jalan buntu.
     */
    public function getByMallTanggal(string $mall, string $tanggal): ?array
    {
        return $this->where('mall', $mall)
            ->where('tanggal', $tanggal)
            ->where('sumber', 'kunjungan')
            ->first();
    }

    /**
     * Ambil kunjungan, buat bila belum ada. Dipakai saat sel pertama diisi dan
     * saat tombol "Nihil temuan" ditekan.
     */
    public function pastikanAda(string $mall, string $tanggal, int $userId): int
    {
        $ada = $this->getByMallTanggal($mall, $tanggal);
        if ($ada) return (int) $ada['id'];

        return (int) $this->insert([
            'mall'       => $mall,
            'tanggal'    => $tanggal,
            'sumber'     => 'kunjungan',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], true);
    }

    public function sentuh(int $visitId): void
    {
        $this->update($visitId, ['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Daftar kunjungan + jumlah temuan & foto, terbaru dulu.
     * $sumber null = semua.
     */
    public function daftar(?string $mall = null, ?string $sumber = 'kunjungan', int $limit = 100, int $offset = 0): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('v.*, '
                . '(SELECT COALESCE(SUM(f.jumlah),0) FROM pest_findings f WHERE f.visit_id = v.id) AS total_temuan, '
                . '(SELECT COUNT(*) FROM pest_findings f WHERE f.visit_id = v.id) AS baris_temuan, '
                . '(SELECT COUNT(*) FROM pest_finding_photos p JOIN pest_findings f ON f.id = p.finding_id WHERE f.visit_id = v.id) AS jml_foto')
            ->orderBy('v.tanggal', 'DESC')->orderBy('v.mall');

        if ($mall)   $b->where('v.mall', $mall);
        if ($sumber) $b->where('v.sumber', $sumber);

        return $b->limit($limit, $offset)->get()->getResultArray();
    }

    public function hitungDaftar(?string $mall = null, ?string $sumber = 'kunjungan'): int
    {
        $b = $this->db->table('pest_visits');
        if ($mall)   $b->where('mall', $mall);
        if ($sumber) $b->where('sumber', $sumber);
        return $b->countAllResults();
    }

    /**
     * Temuan per minggu ISO per item, dalam rentang tanggal.
     *
     * HANYA sumber 'kunjungan' — baris 'rekap_legacy' berasal dari rekap
     * bulanan Excel dan TIDAK punya resolusi mingguan; memasukkannya akan
     * melahirkan tren mingguan yang tampak sahih padahal fiktif.
     *
     * Return: [yearweek => [item_id => jumlah]]
     */
    public function mingguanPerItem(string $dari, string $sampai, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('YEARWEEK(v.tanggal, 1) AS yw, f.item_id, SUM(f.jumlah) AS total')
            ->join('pest_findings f', 'f.visit_id = v.id')
            ->where('v.sumber', 'kunjungan')
            ->where('v.tanggal >=', $dari)
            ->where('v.tanggal <=', $sampai)
            ->groupBy('yw, f.item_id');

        if ($mall) $b->where('v.mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) {
            $map[(string) $r['yw']][(int) $r['item_id']] = (int) $r['total'];
        }
        return $map;
    }

    /** Jumlah kunjungan per minggu ISO — untuk membedakan "nihil" dari "belum diinput". */
    public function mingguanJumlahKunjungan(string $dari, string $sampai, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits')
            ->select('YEARWEEK(tanggal, 1) AS yw, COUNT(*) AS n')
            ->where('sumber', 'kunjungan')
            ->where('tanggal >=', $dari)
            ->where('tanggal <=', $sampai)
            ->groupBy('yw');

        if ($mall) $b->where('mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) $map[(string) $r['yw']] = (int) $r['n'];
        return $map;
    }

    /**
     * Temuan per bulan per item per mall. Termasuk 'rekap_legacy' — di sini
     * satuannya bulan, dan data legacy memang bulanan, jadi sah.
     *
     * Return: [bulan => [mall => [item_id => jumlah]]]
     */
    public function bulananPerItem(string $dariBulan, string $sampaiBulan, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select("DATE_FORMAT(v.tanggal, '%Y-%m') AS bulan, v.mall, f.item_id, SUM(f.jumlah) AS total")
            ->join('pest_findings f', 'f.visit_id = v.id')
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') >=", $dariBulan)
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') <=", $sampaiBulan)
            ->groupBy('bulan, v.mall, f.item_id')
            ->orderBy('bulan');

        if ($mall) $b->where('v.mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) {
            $map[$r['bulan']][$r['mall']][(int) $r['item_id']] = (int) $r['total'];
        }
        return $map;
    }

    /**
     * Temuan satu tahun per item per mall — untuk pembanding antar tahun.
     * Return: [mall => [item_id => jumlah]]
     */
    public function tahunanPerItem(int $tahun, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('v.mall, f.item_id, SUM(f.jumlah) AS total')
            ->join('pest_findings f', 'f.visit_id = v.id')
            ->where('YEAR(v.tanggal)', $tahun)
            ->groupBy('v.mall, f.item_id');

        if ($mall) $b->where('v.mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) {
            $map[$r['mall']][(int) $r['item_id']] = (int) $r['total'];
        }
        return $map;
    }

    /** Tahun-tahun yang punya data — untuk dropdown pembanding. */
    public function tahunTersedia(): array
    {
        $rows = $this->db->table('pest_visits')
            ->select('DISTINCT YEAR(tanggal) AS th', false)
            ->orderBy('th', 'DESC')->get()->getResultArray();
        return array_map(fn($r) => (int) $r['th'], $rows);
    }

    /**
     * Temuan per TANGGAL per item — bahan mentah rincian mingguan laporan
     * bulanan, yang mingguannya dipotong di batas bulan (§2.2).
     *
     * Sengaja per tanggal, bukan per minggu: dengan begitu tiap hari jatuh ke
     * tepat satu ember, dan jumlah baris mingguan PASTI sama dengan total
     * bulannya. Mengelompokkan lewat YEARWEEK lebih dulu akan menyeret hari
     * milik bulan tetangga ikut masuk.
     *
     * Return: [tanggal => [item_id => jumlah]]
     */
    public function harianPerItem(string $dari, string $sampai, ?string $mall = null, ?string $sumber = 'kunjungan'): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('v.tanggal, f.item_id, SUM(f.jumlah) AS total')
            ->join('pest_findings f', 'f.visit_id = v.id')
            ->where('v.tanggal >=', $dari)
            ->where('v.tanggal <=', $sampai)
            ->groupBy('v.tanggal, f.item_id')
            ->orderBy('v.tanggal');

        if ($mall)   $b->where('v.mall', $mall);
        if ($sumber) $b->where('v.sumber', $sumber);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) {
            $map[$r['tanggal']][(int) $r['item_id']] = (int) $r['total'];
        }
        return $map;
    }

    /** Berapa baris rekap_legacy di sebuah bulan — untuk memberi catatan di laporan. */
    public function hitungLegacy(string $bulan, ?string $mall = null): int
    {
        $b = $this->db->table('pest_visits')
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->where('sumber', 'rekap_legacy');
        if ($mall) $b->where('mall', $mall);
        return $b->countAllResults();
    }

    /** Jumlah kunjungan nyata (bukan legacy) dalam sebuah bulan. */
    public function hitungKunjungan(string $bulan, ?string $mall = null): int
    {
        $b = $this->db->table('pest_visits')
            ->where("DATE_FORMAT(tanggal, '%Y-%m')", $bulan)
            ->where('sumber', 'kunjungan');
        if ($mall) $b->where('mall', $mall);
        return $b->countAllResults();
    }
}
