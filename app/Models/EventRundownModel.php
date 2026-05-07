<?php

namespace App\Models;

use CodeIgniter\Model;

class EventRundownModel extends Model
{
    protected $table         = 'event_rundown';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'content_item_id', 'hari_ke', 'tanggal', 'waktu_mulai', 'waktu_selesai', 'sesi', 'deskripsi', 'pic', 'lokasi', 'urutan'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)
            ->orderBy('hari_ke')->orderBy('urutan')->orderBy('waktu_mulai')
            ->findAll();
    }

    public function getByDay(int $eventId, int $hariKe): array
    {
        return $this->where('event_id', $eventId)->where('hari_ke', $hariKe)
            ->orderBy('urutan')->orderBy('waktu_mulai')
            ->findAll();
    }

    public function deleteByEvent(int $eventId): void
    {
        // Only delete manually-entered rows; content-linked rows are managed via content items
        $this->where('event_id', $eventId)->where('content_item_id IS NULL')->delete();
    }

    public function deleteByContentItem(int $contentItemId): void
    {
        $this->where('content_item_id', $contentItemId)->delete();
    }

    public function syncFromContentItem(array $item, int $eventId, int $startTs): void
    {
        if (! $item['tanggal']) return;
        $hariKe = (int)((strtotime($item['tanggal']) - $startTs) / 86400) + 1;
        $this->where('content_item_id', $item['id'])->delete();
        $this->insert([
            'event_id'        => $eventId,
            'content_item_id' => $item['id'],
            'hari_ke'         => $hariKe,
            'tanggal'         => $item['tanggal'],
            'waktu_mulai'     => $item['waktu_mulai'],
            'waktu_selesai'   => $item['waktu_selesai'],
            'sesi'            => $item['nama'],
            'deskripsi'       => $item['jenis'],
            'pic'             => $item['pic'],
            'lokasi'          => $item['lokasi'],
            'urutan'          => 0,
        ]);
    }
}
