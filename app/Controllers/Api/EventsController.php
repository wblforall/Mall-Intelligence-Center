<?php

namespace App\Controllers\Api;

use App\Models\EventModel;

class EventsController extends BaseApiController
{
    public function index()
    {
        if (! $this->requireAuth()) return $this->response;

        $model  = new EventModel();
        $mall   = $this->request->getGet('mall');
        $status = $this->request->getGet('status');

        $builder = $model->orderBy('start_date', 'DESC');
        if ($mall)   $builder->where('mall', $mall);

        $events = $builder->findAll();

        if ($status) {
            $events = array_values(array_filter($events, fn($e) => $e['status'] === $status));
        }

        $result = array_map(fn($e) => [
            'id'         => $e['id'],
            'name'       => $e['name'],
            'tema'       => $e['tema'],
            'mall'       => $e['mall'],
            'start_date' => $e['start_date'],
            'event_days' => $e['event_days'],
            'status'     => $e['status'],
        ], $events);

        return $this->success($result);
    }

    public function show(int $id)
    {
        if (! $this->requireAuth()) return $this->response;

        $event = (new EventModel())->find($id);
        if (! $event) return $this->error('Event tidak ditemukan.', 404);

        // Completed modules
        $event['completions'] = array_column(
            $this->db->table('event_completions')
                ->select('module')
                ->where('event_id', $id)
                ->get()->getResultArray(),
            'module'
        );

        // Rundown grouped by day
        $rows = $this->db->table('event_rundown')
            ->where('event_id', $id)
            ->orderBy('hari_ke', 'ASC')
            ->orderBy('urutan',  'ASC')
            ->get()->getResultArray();

        $byDay = [];
        foreach ($rows as $row) {
            $day = (int)$row['hari_ke'];
            if (! isset($byDay[$day])) {
                $byDay[$day] = ['hari_ke' => $day, 'tanggal' => $row['tanggal'], 'items' => []];
            }
            $byDay[$day]['items'][] = [
                'sesi'          => $row['sesi'],
                'waktu_mulai'   => $row['waktu_mulai'],
                'waktu_selesai' => $row['waktu_selesai'],
                'lokasi'        => $row['lokasi'],
                'pic'           => $row['pic'],
                'deskripsi'     => $row['deskripsi'],
            ];
        }
        $event['rundown'] = array_values($byDay);

        // Loyalty programs
        $event['loyalty_programs'] = $this->db->table('event_loyalty_programs')
            ->select('id, nama_program, mekanisme, target_peserta, budget')
            ->where('event_id', $id)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Exhibitors
        $event['exhibitors'] = $this->db->table('event_exhibitors')
            ->select('id, nama_exhibitor, kategori, nilai_dealing, lokasi_booth')
            ->where('event_id', $id)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Sponsors
        $event['sponsors'] = $this->db->table('event_sponsors')
            ->select('id, nama_sponsor, jenis, nilai, deskripsi_barang')
            ->where('event_id', $id)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->success($event);
    }
}
