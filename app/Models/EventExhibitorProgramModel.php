<?php

namespace App\Models;

use CodeIgniter\Model;

class EventExhibitorProgramModel extends Model
{
    protected $table         = 'exhibitor_programs';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['exhibitor_id', 'event_id', 'nama_program', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai', 'deskripsi', 'created_by'];
    protected $useTimestamps = true;

    public function getByEventGrouped(int $eventId): array
    {
        $rows    = $this->where('event_id', $eventId)->orderBy('tanggal_mulai')->orderBy('nama_program')->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['exhibitor_id']][] = $row;
        }
        return $grouped;
    }

    public function getForSummary(int $eventId): array
    {
        return $this->db->table('exhibitor_programs ep')
            ->select('ep.*, ex.nama_exhibitor, ex.kategori')
            ->join('event_exhibitors ex', 'ex.id = ep.exhibitor_id')
            ->where('ep.event_id', $eventId)
            ->orderBy('ep.tanggal_mulai')
            ->orderBy('ep.jam_mulai')
            ->orderBy('ex.nama_exhibitor')
            ->get()->getResultArray();
    }
}
