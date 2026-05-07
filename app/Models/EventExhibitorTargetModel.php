<?php

namespace App\Models;

use CodeIgniter\Model;

class EventExhibitorTargetModel extends Model
{
    protected $table         = 'event_exhibitor_targets';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['event_id', 'target_jumlah', 'target_nilai_dealing'];
    protected $useTimestamps = true;

    public function getByEvent(int $eventId): ?array
    {
        return $this->where('event_id', $eventId)->first();
    }

    public function saveTarget(int $eventId, int $targetJumlah, int $targetNilai): void
    {
        $existing = $this->where('event_id', $eventId)->first();
        $data = [
            'target_jumlah'        => $targetJumlah,
            'target_nilai_dealing' => $targetNilai,
        ];
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert(array_merge($data, ['event_id' => $eventId]));
        }
    }
}
