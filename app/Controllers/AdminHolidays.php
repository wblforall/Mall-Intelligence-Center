<?php

namespace App\Controllers;

use App\Models\PublicHolidayModel;
use App\Libraries\ActivityLog;

class AdminHolidays extends BaseController
{
    private PublicHolidayModel $model;

    public function __construct()
    {
        $this->model = new PublicHolidayModel();
    }

    public function index()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $year     = (int)($this->request->getGet('year') ?? date('Y'));
        $holidays = $this->model->getByYear($year);

        return view('admin/holidays/index', [
            'user'     => $this->currentUser(),
            'year'     => $year,
            'holidays' => $holidays,
        ]);
    }

    public function store()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $tanggal = $this->request->getPost('tanggal');
        $nama    = trim($this->request->getPost('nama'));
        $jenis   = $this->request->getPost('jenis') ?? 'nasional';

        if (! $tanggal || ! $nama) {
            return redirect()->back()->with('error', 'Tanggal dan nama wajib diisi.');
        }

        $this->model->insert(['tanggal' => $tanggal, 'nama' => $nama, 'jenis' => $jenis]);
        ActivityLog::write('create', 'public_holiday', null, $nama . ' (' . $tanggal . ')');

        $year = substr($tanggal, 0, 4);
        return redirect()->to("admin/holidays?year={$year}")->with('success', 'Hari libur ditambahkan.');
    }

    public function delete(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $record = $this->model->find($id);
        if ($record) {
            $this->model->delete($id);
            ActivityLog::write('delete', 'public_holiday', (string)$id, $record['nama'] . ' (' . $record['tanggal'] . ')');
            $year = substr($record['tanggal'], 0, 4);
            return redirect()->to("admin/holidays?year={$year}")->with('success', 'Hari libur dihapus.');
        }
        return redirect()->to('admin/holidays')->with('error', 'Data tidak ditemukan.');
    }

    public function bulkStore()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $raw  = trim($this->request->getPost('bulk_input') ?? '');
        $year = (int)($this->request->getPost('year') ?? date('Y'));

        if (! $raw) {
            return redirect()->back()->with('error', 'Input kosong.');
        }

        $lines   = preg_split('/\r?\n/', $raw);
        $inserted = 0;
        $errors   = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (! $line) continue;

            // Format: YYYY-MM-DD Nama Hari Libur [jenis]
            if (! preg_match('/^(\d{4}-\d{2}-\d{2})\s+(.+)$/', $line, $m)) {
                $errors[] = "Format salah: {$line}";
                continue;
            }

            $tanggal = $m[1];
            $rest    = trim($m[2]);

            // Optionally ends with |nasional|bersama|lokal
            $jenis = 'nasional';
            if (preg_match('/\|(nasional|bersama|lokal)$/i', $rest, $jm)) {
                $jenis = strtolower($jm[1]);
                $rest  = trim(substr($rest, 0, -strlen($jm[0])));
            }

            $this->model->insert(['tanggal' => $tanggal, 'nama' => $rest, 'jenis' => $jenis]);
            $inserted++;
        }

        ActivityLog::write('create', 'public_holiday', null, "bulk import {$inserted} hari libur");

        $msg = "{$inserted} hari libur berhasil ditambahkan.";
        if ($errors) $msg .= ' ' . count($errors) . ' baris diabaikan (format salah).';

        return redirect()->to("admin/holidays?year={$year}")->with('success', $msg);
    }

    // Tarik otomatis dari API kalender libur nasional Indonesia (sumber: Google Calendar)
    public function syncApi()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $year = (int)($this->request->getPost('year') ?? date('Y'));
        $rows = $this->fetchHolidaysApi($year);

        if ($rows === null) {
            return redirect()->to("admin/holidays?year={$year}")
                ->with('error', 'Gagal mengambil data dari API. Coba lagi atau gunakan Import Massal.');
        }

        // Dedup terhadap data tahun ini yang sudah ada (cocokkan tanggal + nama)
        $existing = [];
        foreach ($this->model->getByYear($year) as $h) {
            $existing[$h['tanggal'] . '|' . mb_strtolower($h['nama'])] = true;
        }

        $inserted = 0;
        $skipped  = 0;
        foreach ($rows as $r) {
            $key = $r['tanggal'] . '|' . mb_strtolower($r['nama']);
            if (isset($existing[$key])) { $skipped++; continue; }
            $this->model->insert($r);
            $existing[$key] = true;
            $inserted++;
        }

        ActivityLog::write('create', 'public_holiday', null, "sync API {$year}: +{$inserted} libur ({$skipped} sudah ada)");

        return redirect()->to("admin/holidays?year={$year}")
            ->with('success', "Sinkronisasi {$year} selesai: {$inserted} hari libur baru ditambahkan, {$skipped} dilewati (sudah ada).");
    }

    // Ambil daftar libur dari feed ICS kalender libur Indonesia (Google); return array siap-insert atau null bila gagal
    private function fetchHolidaysApi(int $year): ?array
    {
        $url = 'https://calendar.google.com/calendar/ical/'
             . 'id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MallIC/1.4)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip, deflate',
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! $raw || $code < 200 || $code >= 300) return null;

        // Normalkan line-ending + unfold baris ICS (lanjutan diawali spasi/tab)
        $ics = str_replace(["\r\n", "\r"], "\n", $raw);
        $ics = preg_replace('/\n[ \t]/', '', $ics);

        $out  = [];
        $seen = [];
        if (! preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $ics, $events)) return $out;

        foreach ($events[1] as $ev) {
            if (! preg_match('/DTSTART(?:;[^:]*)?:(\d{8})/', $ev, $dm)) continue;
            $ymd = $dm[1];
            if (substr($ymd, 0, 4) !== (string)$year) continue;
            if (! preg_match('/\nSUMMARY(?:;[^:]*)?:(.+)/', $ev, $sm)) continue;

            $nama = trim($sm[1]);
            // Unescape karakter ICS
            $nama = str_replace(['\\,', '\\;', '\\n', '\\\\'], [',', ';', ' ', '\\'], $nama);
            if ($nama === '') continue;

            $tanggal = substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
            $key     = $tanggal . '|' . mb_strtolower($nama);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $out[] = [
                'tanggal' => $tanggal,
                'nama'    => mb_substr($nama, 0, 150),
                'jenis'   => stripos($nama, 'cuti bersama') !== false ? 'bersama' : 'nasional',
            ];
        }
        return $out;
    }
}
