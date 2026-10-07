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
     * Syarat baris yang SAH dihitung dalam agregat bulanan (alias tabel `v`).
     *
     * Baris 'rekap_legacy' adalah rekap Excel satu bulan. Begitu bulan itu
     * (untuk mall yang sama) punya kunjungan harian — mis. hasil input susulan
     * Jan–Sep 2026 di produksi — keduanya mencatat bulan yang SAMA dan
     * menjumlahkan keduanya menghitung ganda. Aturannya: kunjungan harian
     * menang, rekap impor hanya mengisi bulan yang sama sekali tanpa kunjungan.
     * Ditemukan 7 Okt 2026: produksi berisi 545 kunjungan + 18 rekap 2026 untuk
     * bulan yang sama.
     */
    public const SYARAT_EFEKTIF = "(v.sumber = 'kunjungan' OR NOT EXISTS ("
        . "SELECT 1 FROM pest_visits k WHERE k.sumber = 'kunjungan' AND k.mall = v.mall "
        . "AND k.tanggal BETWEEN DATE_FORMAT(v.tanggal, '%Y-%m-01') AND LAST_DAY(v.tanggal)))";

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
     * satuannya bulan, dan data legacy memang bulanan, jadi sah — KECUALI
     * bulan yang sudah punya kunjungan harian (lihat SYARAT_EFEKTIF).
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
            ->where(self::SYARAT_EFEKTIF, null, false)
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
            ->where(self::SYARAT_EFEKTIF, null, false)
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

    /**
     * Berapa baris rekap_legacy di sebuah bulan yang BENAR-BENAR ikut dihitung
     * (bulan-mall tanpa kunjungan harian) — untuk memberi catatan di laporan.
     * $tergeser = true menghitung kebalikannya: rekap yang diabaikan karena
     * bulan itu sudah punya kunjungan harian.
     */
    public function hitungLegacy(string $bulan, ?string $mall = null, bool $tergeser = false): int
    {
        $b = $this->db->table('pest_visits v')
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m')", $bulan)
            ->where('v.sumber', 'rekap_legacy')
            ->where(($tergeser ? 'NOT ' : '') . self::SYARAT_EFEKTIF, null, false);
        if ($mall) $b->where('v.mall', $mall);
        return $b->countAllResults();
    }

    /**
     * Bulan-mall yang angkanya berasal dari rekap impor (bukan kunjungan) —
     * untuk menandai kolom tren/pembanding yang satuannya rekap bulanan Excel.
     * Return: [bulan => [mall => true]]
     */
    public function bulanLegacyEfektif(string $dariBulan, string $sampaiBulan, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select("DATE_FORMAT(v.tanggal, '%Y-%m') AS bulan, v.mall")
            ->where('v.sumber', 'rekap_legacy')
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') >=", $dariBulan)
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') <=", $sampaiBulan)
            ->where(self::SYARAT_EFEKTIF, null, false);
        if ($mall) $b->where('v.mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) $map[$r['bulan']][$r['mall']] = true;
        return $map;
    }

    // ── Rekap rentang tanggal bebas ──────────────────────────────────────

    /**
     * Temuan kunjungan NYATA per tanggal per mall per item dalam rentang.
     * Rekap impor tidak ikut — ia tidak punya tanggal sungguhan (lihat
     * legacyDalamRentang()).
     *
     * Return: [tanggal => [mall => [item_id => jumlah]]]
     */
    public function harianPerMallItem(string $dari, string $sampai, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('v.tanggal, v.mall, f.item_id, SUM(f.jumlah) AS total')
            ->join('pest_findings f', 'f.visit_id = v.id')
            ->where('v.sumber', 'kunjungan')
            ->where('v.tanggal >=', $dari)
            ->where('v.tanggal <=', $sampai)
            ->groupBy('v.tanggal, v.mall, f.item_id')
            ->orderBy('v.tanggal');
        if ($mall) $b->where('v.mall', $mall);

        $map = [];
        foreach ($b->get()->getResultArray() as $r) {
            $map[$r['tanggal']][$r['mall']][(int) $r['item_id']] = (int) $r['total'];
        }
        return $map;
    }

    /**
     * Daftar kunjungan nyata dalam rentang beserta total temuannya — termasuk
     * kunjungan "nihil" (total 0), yang membuktikan pemeriksaan dilakukan.
     */
    public function kunjunganDalamRentang(string $dari, string $sampai, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select('v.id, v.mall, v.tanggal, '
                . '(SELECT COALESCE(SUM(f.jumlah),0) FROM pest_findings f WHERE f.visit_id = v.id) AS total')
            ->where('v.sumber', 'kunjungan')
            ->where('v.tanggal >=', $dari)
            ->where('v.tanggal <=', $sampai)
            ->orderBy('v.tanggal')->orderBy('v.mall');
        if ($mall) $b->where('v.mall', $mall);
        return $b->get()->getResultArray();
    }

    /**
     * Baris rekap impor yang BULANNYA bersinggungan dengan rentang, beserta
     * rinciannya. Controller yang memutuskan nasibnya:
     *  - `tergeser`  → bulan-mall itu sudah punya kunjungan harian → diabaikan;
     *  - bulan utuh di dalam rentang → dihitung;
     *  - bulan terpotong rentang → TIDAK dihitung (angka bulanan tidak boleh
     *    dipecah menjadi harian — itu mengarang data, PESTCARE_DESIGN §6).
     *
     * Return: list of ['id','mall','bulan','tanggal','tergeser'=>bool,'items'=>[item_id=>n],'total'=>n]
     */
    public function legacyDalamRentang(string $dari, string $sampai, ?string $mall = null): array
    {
        $b = $this->db->table('pest_visits v')
            ->select("v.id, v.mall, v.tanggal, DATE_FORMAT(v.tanggal, '%Y-%m') AS bulan, "
                . 'NOT ' . self::SYARAT_EFEKTIF . ' AS tergeser', false)
            ->where('v.sumber', 'rekap_legacy')
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') >=", substr($dari, 0, 7))
            ->where("DATE_FORMAT(v.tanggal, '%Y-%m') <=", substr($sampai, 0, 7))
            ->orderBy('v.tanggal')->orderBy('v.mall');
        if ($mall) $b->where('v.mall', $mall);

        $rows = $b->get()->getResultArray();
        if (! $rows) return [];

        $temuan = $this->db->table('pest_findings')
            ->select('visit_id, item_id, SUM(jumlah) AS total')
            ->whereIn('visit_id', array_column($rows, 'id'))
            ->groupBy('visit_id, item_id')->get()->getResultArray();
        $perVisit = [];
        foreach ($temuan as $t) $perVisit[(int) $t['visit_id']][(int) $t['item_id']] = (int) $t['total'];

        return array_map(function ($r) use ($perVisit) {
            $items = $perVisit[(int) $r['id']] ?? [];
            return [
                'id'       => (int) $r['id'],
                'mall'     => $r['mall'],
                'bulan'    => $r['bulan'],
                'tanggal'  => $r['tanggal'],
                'tergeser' => (bool) $r['tergeser'],
                'items'    => $items,
                'total'    => array_sum($items),
            ];
        }, $rows);
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
