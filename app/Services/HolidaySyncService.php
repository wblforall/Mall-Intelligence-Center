<?php

namespace App\Services;

use App\Models\PublicHolidayModel;

/**
 * Tarik & simpan hari libur nasional Indonesia dari feed ICS kalender Google.
 * Dipakai bersama oleh AdminHolidays (tombol web) dan command holidays:sync (cron).
 */
class HolidaySyncService
{
    private const ICS_URL = 'https://calendar.google.com/calendar/ical/'
        . 'id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

    private PublicHolidayModel $model;

    public function __construct()
    {
        $this->model = new PublicHolidayModel();
    }

    /**
     * Sinkronkan satu tahun. Return ['ok'=>bool, 'inserted'=>int, 'skipped'=>int].
     */
    public function sync(int $year): array
    {
        $rows = $this->fetchFromIcs($year);
        if ($rows === null) {
            return ['ok' => false, 'inserted' => 0, 'skipped' => 0];
        }

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

        return ['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped];
    }

    /**
     * Ambil & parse feed ICS untuk satu tahun. Return array siap-insert, atau null bila gagal fetch.
     */
    public function fetchFromIcs(int $year): ?array
    {
        $ch = curl_init(self::ICS_URL);
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
            if (substr($ymd, 0, 4) !== (string) $year) continue;
            if (! preg_match('/\nSUMMARY(?:;[^:]*)?:(.+)/', $ev, $sm)) continue;

            $nama = trim($sm[1]);
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
