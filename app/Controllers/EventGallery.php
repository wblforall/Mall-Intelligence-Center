<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventVMRealisasiModel;
use App\Models\EventContentRealisasiModel;
use App\Models\EventCreativeFileModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Models\EventSponsorRealisasiModel;

class EventGallery extends BaseController
{
    private static array $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    private function isImage(?string $filename): bool
    {
        if (! $filename) return false;
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::$imgExts);
    }

    public function index(int $eventId)
    {
        $event = (new EventModel())->find($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Event tidak ditemukan.');

        $photos = ['vm' => [], 'content' => [], 'creative' => [], 'sponsor' => []];

        // VM realisasi
        $vmReal = (new EventVMRealisasiModel())->where('event_id', $eventId)->findAll();
        foreach ($vmReal as $r) {
            if ($this->isImage($r['foto_file_name'])) {
                $photos['vm'][] = [
                    'src'     => base_url('uploads/vm/' . $eventId . '/' . $r['foto_file_name']),
                    'caption' => 'VM Realisasi' . ($r['tanggal'] ? ' — ' . date('d M Y', strtotime($r['tanggal'])) : ''),
                ];
            }
        }

        // Content realisasi
        $ctReal = (new EventContentRealisasiModel())->where('event_id', $eventId)->findAll();
        foreach ($ctReal as $r) {
            if ($this->isImage($r['file_foto'] ?? null)) {
                $photos['content'][] = [
                    'src'     => base_url('uploads/content-realisasi/' . $eventId . '/' . $r['file_foto']),
                    'caption' => 'Content Realisasi' . ($r['tanggal'] ? ' — ' . date('d M Y', strtotime($r['tanggal'])) : ''),
                ];
            }
        }

        // Creative design files
        $cFiles = (new EventCreativeFileModel())->where('event_id', $eventId)->findAll();
        foreach ($cFiles as $f) {
            if ($this->isImage($f['file_name'])) {
                $photos['creative'][] = [
                    'src'     => base_url('uploads/creative/' . $eventId . '/' . $f['file_name']),
                    'caption' => esc($f['original_name'] ?? $f['file_name']),
                ];
            }
        }

        // Creative realisasi (bukti, serah terima, bukti terpasang)
        $cReal = (new EventCreativeRealisasiModel())->where('event_id', $eventId)->findAll();
        $cRealLabels = [
            'file_name'                  => 'Bukti',
            'serah_terima_file_name'     => 'Serah Terima',
            'bukti_terpasang_file_name'  => 'Bukti Terpasang',
        ];
        foreach ($cReal as $r) {
            foreach ($cRealLabels as $col => $label) {
                if ($this->isImage($r[$col] ?? null)) {
                    $photos['creative'][] = [
                        'src'     => base_url('uploads/creative/' . $eventId . '/' . $r[$col]),
                        'caption' => 'Realisasi ' . $label . ($r['tanggal'] ? ' — ' . date('d M Y', strtotime($r['tanggal'])) : ''),
                    ];
                }
            }
        }

        // Creative insights (screenshots)
        $insights = (new EventCreativeInsightModel())->where('event_id', $eventId)->findAll();
        foreach ($insights as $r) {
            if ($this->isImage($r['file_name'] ?? null)) {
                $cap = 'Insight';
                if ($r['platform']) $cap .= ' — ' . $r['platform'];
                if ($r['tanggal'])  $cap .= ' — ' . date('d M Y', strtotime($r['tanggal']));
                $photos['creative'][] = [
                    'src'     => base_url('uploads/creative/' . $eventId . '/' . $r['file_name']),
                    'caption' => $cap,
                ];
            }
        }

        // Sponsor realisasi
        $spReal = (new EventSponsorRealisasiModel())->where('event_id', $eventId)->findAll();
        foreach ($spReal as $r) {
            if ($this->isImage($r['file_foto'] ?? null)) {
                $photos['sponsor'][] = [
                    'src'     => base_url('uploads/sponsor-realisasi/' . $eventId . '/' . $r['file_foto']),
                    'caption' => 'Sponsor Foto' . ($r['tanggal'] ? ' — ' . date('d M Y', strtotime($r['tanggal'])) : ''),
                ];
            }
            if ($this->isImage($r['file_terima'] ?? null)) {
                $photos['sponsor'][] = [
                    'src'     => base_url('uploads/sponsor-realisasi/' . $eventId . '/' . $r['file_terima']),
                    'caption' => 'Bukti Terima Sponsor' . ($r['tanggal'] ? ' — ' . date('d M Y', strtotime($r['tanggal'])) : ''),
                ];
            }
        }

        $totalPhotos = array_sum(array_map('count', $photos));

        return view('events/gallery', [
            'user'        => $this->currentUser(),
            'event'       => $event,
            'photos'      => $photos,
            'totalPhotos' => $totalPhotos,
        ]);
    }
}
