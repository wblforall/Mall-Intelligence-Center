<?php

namespace App\Models;

use CodeIgniter\Model;

class EventExhibitorModel extends Model
{
    protected $table         = 'event_exhibitors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'nama_exhibitor', 'kategori', 'nilai_dealing', 'lokasi_booth', 'catatan', 'created_by'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('kategori')->orderBy('nama_exhibitor')->findAll();
    }

    public function getTotalDealing(int $eventId): int
    {
        return (int)$this->selectSum('nilai_dealing')->where('event_id', $eventId)->first()['nilai_dealing'];
    }

    public function getKategoriOptions(int $eventId): array
    {
        $defaults = ['F&B', 'Fashion', 'Toys', 'Aksesoris', 'Beauty & Kosmetik', 'Elektronik', 'Jasa', 'Lainnya'];
        $fromDb   = array_column(
            $this->select('kategori')->distinct()->where('event_id', $eventId)->findAll(),
            'kategori'
        );
        $merged = array_unique(array_merge($defaults, $fromDb));
        sort($merged);
        return $merged;
    }
}
