<?php

namespace App\Models;

use CodeIgniter\Model;

class EventSponsorModel extends Model
{
    protected $table         = 'event_sponsors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'nama_sponsor', 'jenis', 'nilai', 'detail', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('jenis')->orderBy('nama_sponsor')->findAll();
    }

    public function getTotalCash(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('event_id', $eventId)->where('jenis', 'cash')->get()->getRow()->total ?? 0);
    }

    public function getTotalInKind(int $eventId): int
    {
        return (int)($this->selectSum('nilai', 'total')->where('event_id', $eventId)->where('jenis', 'barang')->get()->getRow()->total ?? 0);
    }

    public function getTotalInKindQty(int $eventId): int
    {
        $row = $this->db->query("
            SELECT COALESCE(SUM(i.qty), 0) AS total
            FROM event_sponsors s
            JOIN event_sponsor_items i ON i.sponsor_id = s.id
            WHERE s.event_id = ? AND s.jenis = 'barang'
        ", [$eventId])->getRow();
        return (int)($row->total ?? 0);
    }

    /**
     * Agregat sponsor per event (utk Laporan Bulanan Sponsorship):
     * satu baris per event yang punya sponsor — nama event, mall, tanggal
     * mulai, jumlah sponsor, total nilai cash & barang.
     */
    public function getEventAggregates(): array
    {
        return $this->db->table('event_sponsors s')
            ->select("s.event_id, e.name AS event_name, e.mall AS event_mall,
                e.start_date AS event_start_date,
                COUNT(*) AS jumlah_sponsor,
                SUM(CASE WHEN s.jenis = 'cash'   THEN s.nilai ELSE 0 END) AS total_cash,
                SUM(CASE WHEN s.jenis = 'barang' THEN s.nilai ELSE 0 END) AS total_barang")
            ->join('events e', 'e.id = s.event_id')
            ->groupBy('s.event_id, e.name, e.mall, e.start_date')
            ->orderBy('e.start_date', 'DESC')
            ->get()->getResultArray();
    }
}
