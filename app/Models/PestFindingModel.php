<?php

namespace App\Models;

use CodeIgniter\Model;

class PestFindingModel extends Model
{
    protected $table         = 'pest_findings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['visit_id', 'item_id', 'area_id', 'jumlah', 'created_at'];
    protected $useTimestamps = false;

    /** Area 0 = tidak dirinci. Satu-satunya nilai yang dipakai di Fase 1. */
    public const AREA_TIDAK_DIRINCI = 0;

    public function getCell(int $visitId, int $itemId, int $areaId = self::AREA_TIDAK_DIRINCI): ?array
    {
        return $this->where('visit_id', $visitId)
            ->where('item_id', $itemId)
            ->where('area_id', $areaId)
            ->first();
    }

    /**
     * Upsert satu baris temuan — tidak menyentuh baris lain (aman untuk input
     * paralel), pola DailyTrafficModel::upsertCell().
     *
     * NOL TIDAK DISIMPAN: jumlah 0 → baris dihapus. Tabel ini tetap jarang, dan
     * rekap tidak perlu menyaring nol. Yang membuktikan pemeriksaan dilakukan
     * adalah keberadaan baris pest_visits, bukan baris nol di sini.
     *
     * @return array{action: 'insert'|'update'|'delete'|'none', before: ?int, id: ?int}
     */
    public function upsertCell(int $visitId, int $itemId, int $jumlah, int $areaId = self::AREA_TIDAK_DIRINCI): array
    {
        $ada = $this->getCell($visitId, $itemId, $areaId);

        if ($jumlah <= 0) {
            if (! $ada) return ['action' => 'none', 'before' => null, 'id' => null];
            $before = (int) $ada['jumlah'];
            // Foto ikut terhapus lewat FK CASCADE; berkas fisiknya dibersihkan
            // pemanggil SETELAH transaksi commit.
            $this->delete($ada['id']);
            return ['action' => 'delete', 'before' => $before, 'id' => (int) $ada['id']];
        }

        if ($ada) {
            $before = (int) $ada['jumlah'];
            if ($before === $jumlah) {
                return ['action' => 'none', 'before' => $before, 'id' => (int) $ada['id']];
            }
            $this->update($ada['id'], ['jumlah' => $jumlah]);
            return ['action' => 'update', 'before' => $before, 'id' => (int) $ada['id']];
        }

        $id = $this->insert([
            'visit_id'   => $visitId,
            'item_id'    => $itemId,
            'area_id'    => $areaId,
            'jumlah'     => $jumlah,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        return ['action' => 'insert', 'before' => null, 'id' => (int) $id];
    }

    /**
     * Temuan satu kunjungan, lengkap dengan jumlah fotonya.
     * Return: [item_id => ['id'=>, 'jumlah'=>, 'area_id'=>, 'jml_foto'=>]]
     * (Fase 1: satu baris per item karena area selalu 0.)
     */
    public function byVisit(int $visitId): array
    {
        $rows = $this->db->table('pest_findings f')
            ->select('f.*, (SELECT COUNT(*) FROM pest_finding_photos p WHERE p.finding_id = f.id) AS jml_foto')
            ->where('f.visit_id', $visitId)
            ->orderBy('f.item_id')->orderBy('f.area_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['item_id']] = [
                'id'       => (int) $r['id'],
                'area_id'  => (int) $r['area_id'],
                'jumlah'   => (int) $r['jumlah'],
                'jml_foto' => (int) $r['jml_foto'],
            ];
        }
        return $map;
    }

    public function totalByVisit(int $visitId): int
    {
        return (int) ($this->db->table('pest_findings')
            ->selectSum('jumlah', 'total')
            ->where('visit_id', $visitId)
            ->get()->getRow()->total ?? 0);
    }
}
