<?php

namespace App\Libraries;

/**
 * Biaya Creative yang berasal dari FlowStore (keputusan pemilik 18 Okt 2026: FlowStore = SUMBER realisasi biaya CCD).
 *
 * Satu BTB di tab FlowStore Budget Departemen CCD dapat ditautkan ke satu item Creative (budget_fs_pemetaan.creative_scope +
 * creative_item_id). Setelah "Catat", angkanya hidup di budget_realisasi (satu sumber kebenaran) dan modul Creative hanya MEMBACA:
 *  - baris / total biaya per item (rincian: tanggal, BTB/PO, supplier, barang, nilai),
 *  - penambahan ke realisasi manual lama (creative_realisasi) — data lama TIDAK diubah; tanpa tautan, angkanya identik dengan dulu,
 *  - penanda "kemungkinan dobel" bila item punya realisasi manual di bulan yang sama dengan BTB tertaut (hanya penanda).
 * Juga: kandidat Tema -> item untuk pemilih di tab FlowStore, dan USULAN otomatis tautan beserta alasannya.
 *
 * Semua pembacaan dibungkus try/catch: bila migrasi 2026-10-18-000010 belum dijalankan, Creative tetap tampil seperti semula.
 */
class CreativeBiayaFs
{
    const STATUS_ADA = ['dicatat', 'perlu_ditinjau'];

    private $db;
    private static ?int $ccdId = null;

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect();
    }

    /** Id dept Creative Concept & Design (0 bila tak ada). */
    public static function deptCcdId(): int
    {
        if (self::$ccdId === null) {
            try {
                $r = db_connect()->table('departments')->select('id')->where('name', 'Creative Concept & Design')->get()->getRowArray();
                self::$ccdId = $r ? (int) $r['id'] : 0;
            } catch (\Throwable $e) { self::$ccdId = 0; }
        }
        return self::$ccdId;
    }

    public static function ref(string $scope, int $id): string { return $scope . ':' . $id; }

    /** 'standalone:12' -> ['standalone', 12]; selain itu null. */
    public static function urai(?string $ref): ?array
    {
        if (! is_string($ref) || ! preg_match('/^(standalone|event):(\d+)$/', $ref, $m)) return null;
        return [$m[1], (int) $m[2]];
    }

    // ── Biaya tertaut per item (untuk modul Creative) ────────────────

    /**
     * @return array<int,array> [item_id => [baris...]] — baris: tgl_beban, nilai, status, catatan_tinjau, no_btb, no_po, nama_supplier,
     *                          nama_barang, tanggal_diterima, jumlah_diterima, satuan, pos_nama, id_btb_item, realisasi_id
     */
    public function baris(string $scope, array $ids, ?string $dari = null, ?string $sampai = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) return [];
        try {
            $q = $this->db->table('budget_fs_pemetaan p')
                ->select('p.creative_item_id AS item_id, p.status, p.catatan_tinjau, x.id AS realisasi_id, x.tanggal AS tgl_beban, x.nilai, bp.nama AS pos_nama,
                          f.id_btb_item, f.no_btb, f.no_po, f.nama_supplier, f.nama_barang, f.tanggal_diterima, f.jumlah_diterima, f.satuan')
                ->join('budget_realisasi x', 'x.id = p.realisasi_id')
                ->join('fs_btb_item f', 'f.id_btb_item = p.id_btb_item')
                ->join('budget_pos bp', 'bp.id = x.pos_id', 'left')
                ->where('p.creative_scope', $scope)->whereIn('p.creative_item_id', $ids)->whereIn('p.status', self::STATUS_ADA);
            if ($dari !== null) $q->where('x.tanggal >=', $dari);
            if ($sampai !== null) $q->where('x.tanggal <=', $sampai);
            $rows = $q->orderBy('x.tanggal', 'ASC')->orderBy('f.id_btb_item', 'ASC')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) $out[(int) $r['item_id']][] = $r;
        return $out;
    }

    /**
     * Tambahkan biaya FlowStore ke hasil getGroupedByItems() (realisasi manual). Tiap item bertautan mendapat:
     *  fs (baris), fs_total, manual (total manual semula), total (= manual + fs), dobel (bulan 'YYYY-MM' yang kemungkinan dobel).
     * 'entries' tetap realisasi manual saja. Item tanpa tautan tidak disentuh sama sekali.
     */
    public function gabung(array $grouped, string $scope, array $ids): array
    {
        foreach ($this->baris($scope, $ids) as $id => $rows) {
            $g = $grouped[$id] ?? ['entries' => [], 'total' => 0];
            $fsTotal = 0; $bulanFs = [];
            foreach ($rows as $r) {
                $fsTotal += (int) round((float) $r['nilai']);
                $bulanFs[substr((string) $r['tgl_beban'], 0, 7)] = 1;
                if (! empty($r['tanggal_diterima'])) $bulanFs[substr((string) $r['tanggal_diterima'], 0, 7)] = 1;
            }
            $dobel = [];
            foreach (($g['entries'] ?? []) as $e) {
                $m = substr((string) $e['tanggal'], 0, 7);
                if (isset($bulanFs[$m])) $dobel[$m] = 1;
            }
            $g['manual']   = (int) ($g['total'] ?? 0);
            $g['fs']       = $rows;
            $g['fs_total'] = $fsTotal;
            $g['total']    = $g['manual'] + $fsTotal;
            $g['dobel']    = array_keys($dobel);
            $grouped[$id]  = $g;
        }
        return $grouped;
    }

    /** Tambahkan biaya FlowStore bulan $bulan ('YYYY-MM') ke hasil getMonthlyGrouped(). */
    public function gabungBulanan(array $grouped, string $bulan, string $scope, array $ids): array
    {
        $dari = $bulan . '-01'; $sampai = date('Y-m-t', strtotime($dari));
        foreach ($this->baris($scope, $ids, $dari, $sampai) as $id => $rows) {
            $g = $grouped[$id] ?? ['entries' => [], 'total' => 0];
            $fs = 0;
            foreach ($rows as $r) $fs += (int) round((float) $r['nilai']);
            $g['fs_total'] = $fs;
            $g['total']    = (int) ($g['total'] ?? 0) + $fs;
            $grouped[$id]  = $g;
        }
        return $grouped;
    }

    /** Total biaya FlowStore (sampai akhir bulan $bulan bila diberikan). */
    public function total(string $scope, array $ids, ?string $bulan = null): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) return 0;
        try {
            $q = $this->db->table('budget_fs_pemetaan p')->selectSum('x.nilai', 't')
                ->join('budget_realisasi x', 'x.id = p.realisasi_id')
                ->where('p.creative_scope', $scope)->whereIn('p.creative_item_id', $ids)->whereIn('p.status', self::STATUS_ADA);
            if ($bulan !== null) $q->where('x.tanggal <=', date('Y-m-t', strtotime($bulan . '-01')));
            return (int) round((float) ($q->get()->getRowArray()['t'] ?? 0));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ── Pemilih Tema -> item (tab FlowStore dept CCD) ────────────────

    /**
     * Kandidat Tema berikut item-nya untuk bulan $dari..$sampai ('YYYY-MM', inklusif): Tema Bulanan, Event (mulai di rentang) dan
     * "Tanpa Tema" (item lama bertanggal di rentang). Item "Rencana" tidak ikut (isi dulu di Creative); Tema yang sudah ditutup tidak ikut.
     * Tambahan $ikutRef: ref item yang harus tetap tersedia (mis. yang sudah tersimpan) walau di luar rentang / Tema ditutup.
     * @return list<array{key:string,kind:string,nama:string,bulan:string,items:list<array>}>
     */
    public function kandidat(string $dari, string $sampai, array $ikutRef = []): array
    {
        $db = $this->db;
        $temaOut = [];
        try {
            $ikut = ['standalone' => [], 'event' => []];
            foreach ($ikutRef as $ref) if ($u = self::urai($ref)) $ikut[$u[0]][] = $u[1];

            $bulanan = $db->table('creative_tema')->where('bulan >=', $dari)->where('bulan <=', $sampai)->where('ditutup', 0)->orderBy('bulan', 'DESC')->get()->getResultArray();
            $ditutup = array_column($db->table('creative_tema_status')->select('event_id')->where('ditutup', 1)->get()->getResultArray(), 'event_id');
            $events  = array_filter($db->table('events')->select('id,name,start_date')->where('start_date >=', $dari . '-01')->where('start_date <=', date('Y-m-t', strtotime($sampai . '-01')))->orderBy('start_date', 'DESC')->get()->getResultArray(),
                fn ($e) => ! in_array($e['id'], $ditutup));

            $sItems = $db->table('creative_items')->where('dari_paket', 0)->get()->getResultArray();
            $eItems = $db->table('event_creative_items')->where('dari_paket', 0)->get()->getResultArray();
            // Tema yang dimuat: kandidat rentang + Tema dari item yang harus diikutkan
            $perluTema = []; $perluEvent = [];
            foreach ($sItems as $it) if (in_array((int) $it['id'], $ikut['standalone'], true)) { if ($it['tema_id']) $perluTema[(int) $it['tema_id']] = 1; if ($it['event_id']) $perluEvent[(int) $it['event_id']] = 1; }
            foreach ($eItems as $it) if (in_array((int) $it['id'], $ikut['event'], true)) $perluEvent[(int) $it['event_id']] = 1;
            if ($perluTema) foreach ($db->table('creative_tema')->whereIn('id', array_keys($perluTema))->get()->getResultArray() as $t) if (! in_array($t['id'], array_column($bulanan, 'id'))) $bulanan[] = $t;
            if ($perluEvent) foreach ($db->table('events')->select('id,name,start_date')->whereIn('id', array_keys($perluEvent))->get()->getResultArray() as $e) if (! in_array($e['id'], array_column($events, 'id'))) $events[] = $e;

            $item = function (array $it, string $scope): array {
                return ['ref' => self::ref($scope, (int) $it['id']), 'nama' => (string) $it['nama'], 'tipe' => (string) $it['tipe'], 'jenis' => (string) ($it['jenis_media'] ?? ''),
                        'ukuran' => (string) ($it['ukuran'] ?? ''), 'budget' => (int) ($it['budget'] ?? 0)];
            };
            $peta = [];
            foreach ($bulanan as $t) $peta['t' . $t['id']] = ['key' => 't' . $t['id'], 'kind' => 'bulanan', 'nama' => (string) $t['nama'], 'bulan' => (string) $t['bulan'], 'items' => []];
            foreach ($events as $e)  $peta['e' . $e['id']] = ['key' => 'e' . $e['id'], 'kind' => 'event', 'nama' => (string) $e['name'], 'bulan' => substr((string) $e['start_date'], 0, 7), 'items' => []];
            $peta['none'] = ['key' => 'none', 'kind' => 'none', 'nama' => 'Tanpa Tema', 'bulan' => '', 'items' => []];
            foreach ($sItems as $it) {
                $k = $it['tema_id'] ? 't' . $it['tema_id'] : ($it['event_id'] ? 'e' . $it['event_id'] : 'none');
                if (! isset($peta[$k])) continue;
                if ($k === 'none') {
                    $m = substr((string) (($it['tanggal'] ?: $it['tanggal_take']) ?? ''), 0, 7);
                    if (($m < $dari || $m > $sampai) && ! in_array((int) $it['id'], $ikut['standalone'], true)) continue;
                }
                $peta[$k]['items'][] = $item($it, 'standalone');
            }
            foreach ($eItems as $it) {
                $k = 'e' . $it['event_id'];
                if (isset($peta[$k])) $peta[$k]['items'][] = $item($it, 'event');
            }
            foreach ($peta as $t) if ($t['items']) $temaOut[] = $t;
            usort($temaOut, fn ($a, $b) => [$a['kind'] === 'none', strcmp($b['bulan'], $a['bulan'])] <=> [$b['kind'] === 'none', 0]);
        } catch (\Throwable $e) {
            return [];
        }
        return $temaOut;
    }

    /** Cari Tema yang memuat ref ini dalam kandidat -> key Tema atau ''. */
    public static function temaDariRef(array $kandidat, string $ref): string
    {
        foreach ($kandidat as $t) foreach ($t['items'] as $it) if ($it['ref'] === $ref) return $t['key'];
        return '';
    }

    /** Validasi tautan sebelum Catat/draf. @return ?string pesan galat (null = sah) */
    public function periksa(string $ref): ?string
    {
        $u = self::urai($ref);
        if (! $u) return 'item Creative tidak dikenal';
        [$scope, $id] = $u;
        $db = $this->db;
        try {
            $it = $db->table($scope === 'event' ? 'event_creative_items' : 'creative_items')->where('id', $id)->get()->getRowArray();
            if (! $it) return 'item Creative tidak ditemukan';
            if (! empty($it['dari_paket'])) return 'item Creative masih berstatus Rencana — isi dulu di Creative';
            $ev = $scope === 'event' ? (int) $it['event_id'] : (int) ($it['event_id'] ?? 0);
            if ($ev) {
                $s = $db->table('creative_tema_status')->where('event_id', $ev)->get()->getRowArray();
                if ($s && (int) $s['ditutup'] === 1) return 'Tema item Creative sudah ditutup';
            } elseif (! empty($it['tema_id'])) {
                $t = $db->table('creative_tema')->where('id', (int) $it['tema_id'])->get()->getRowArray();
                if ($t && (int) $t['ditutup'] === 1) return 'Tema item Creative sudah ditutup';
            }
        } catch (\Throwable $e) {
            return 'item Creative tidak dapat diperiksa';
        }
        return null;
    }

    /** Label "Tema › Item" + tautan ke /creative untuk sebuah ref (untuk baris yang sudah dicatat). */
    public function label(string $ref): ?array
    {
        $u = self::urai($ref);
        if (! $u) return null;
        [$scope, $id] = $u;
        try {
            $it = $this->db->table($scope === 'event' ? 'event_creative_items' : 'creative_items')->select($scope === 'event' ? 'id,nama,event_id' : 'id,nama,tema_id,event_id')->where('id', $id)->get()->getRowArray();
            if (! $it) return null;
            $tema = 'Tanpa Tema';
            if (! empty($it['event_id'])) {
                $e = $this->db->table('events')->select('name')->where('id', (int) $it['event_id'])->get()->getRowArray();
                $tema = $e['name'] ?? 'Event';
            } elseif (! empty($it['tema_id'])) {
                $t = $this->db->table('creative_tema')->select('nama')->where('id', (int) $it['tema_id'])->get()->getRowArray();
                $tema = $t['nama'] ?? 'Tema';
            }
            return ['tema' => $tema, 'item' => (string) $it['nama'], 'url' => base_url('creative#' . ($scope === 'event' ? 'item-e-' . $id : 'item-' . $id . '-s'))];
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Usulan otomatis ──────────────────────────────────────────────

    private const BULAN = ['JANUARI' => 1, 'JANUARY' => 1, 'FEBRUARI' => 2, 'FEBRUARY' => 2, 'MARET' => 3, 'MARCH' => 3, 'APRIL' => 4, 'MEI' => 5, 'MAY' => 5,
        'JUNI' => 6, 'JUNE' => 6, 'JULI' => 7, 'JULY' => 7, 'AGUSTUS' => 8, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'OCTOBER' => 10,
        'NOVEMBER' => 11, 'DESEMBER' => 12, 'DECEMBER' => 12];
    private const NAMA_BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    private const JENIS = [   // [kanon, label, pola]
        ['sticker lift', 'Sticker Lift', '/STICKER LIFT/'],
        ['t-banner', 'T-Banner', '/\bT ?BANNER\b|\bX ?BANNER\b/'],
        ['hanging', 'Hanging Banner', '/HANGING|GIANT BANNER/'],
        ['neon box', 'Neon Box', '/NEON BOX/'],
        ['totem', 'Totem', '/TOTEM/'],
        ['poster', 'Poster', '/POSTER/'],
        ['sticker', 'Sticker', '/STICKER/'],
        ['banner', 'Banner', '/BANNER/'],
    ];
    private const KATA_UMUM = ['UNTUK', 'KEPERLUAN', 'MEDIA', 'PROMOSI', 'BAHAN', 'DEADLINE', 'PEMASANGAN', 'PELEPASAN', 'LAMINASI', 'DOFF', 'VINYL', 'MATERI', 'CETAK',
        'STICKER', 'BANNER', 'POSTER', 'LIFT', 'DESIGN', 'ITEM', 'BULAN', 'TGL', 'RAB', 'PROGRAM', 'LOYALTY', 'WHATS', 'UK', 'CM'];

    /** Jenis kanon dari teks (nama barang / nama item) -> [kanon, label] atau null. */
    private static function jenisDari(string $teks): ?array
    {
        $n = ' ' . BudgetFsRekomendasi::normal($teks) . ' ';
        foreach (self::JENIS as [$kanon, $label, $pola]) if (preg_match($pola . 'u', $n)) return [$kanon, $label];
        return null;
    }

    /** Ukuran (cm, terurut kecil-besar) dari teks seperti "UK.123 X 206CM", "1 X 2,4 M", "40 CM X 122 CM". */
    private static function ukuranDari(string $teks): ?array
    {
        $t = strtoupper($teks);
        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(CM|M)?\s*[X×]\s*(\d+(?:[.,]\d+)?)\s*(CM|M)?\b/u', $t, $m)) return null;
        $a = (float) str_replace(',', '.', $m[1]); $b = (float) str_replace(',', '.', $m[3]);
        $unit = $m[4] ?: ($m[2] ?: '');
        $meter = $unit === 'M' || ($unit === '' && max($a, $b) < 20);
        if ($meter) { $a *= 100; $b *= 100; }
        $d = [round(min($a, $b)), round(max($a, $b))];
        return $d[0] > 0 ? $d : null;
    }

    private static function tokenBermakna(string $teks): array
    {
        $out = [];
        foreach (explode(' ', BudgetFsRekomendasi::normal($teks)) as $t) {
            if (strlen($t) < 4 || ctype_digit($t) || in_array($t, self::KATA_UMUM, true) || isset(self::BULAN[$t])) continue;
            $out[$t] = $t;
        }
        return array_values($out);
    }

    /** Nomor bulan yang disebut dalam teks (mis. "WHATS ON OKTOBER 2026" -> [10]). */
    private static function bulanDisebut(string $teks): array
    {
        $n = ' ' . BudgetFsRekomendasi::normal($teks) . ' ';
        $out = [];
        foreach (self::BULAN as $kata => $no) if (str_contains($n, ' ' . $kata . ' ')) $out[$no] = $no;
        return array_values($out);
    }

    /**
     * Usulan item Creative untuk satu item BTB. Skor: Tema (bulan disebut keperluan cocok Tema Bulanan +30; nama event ada di
     * keperluan/nama barang +40; Tema pada bulan terima +10) + item (jenis cocok +50; ukuran cocok +40; kata nama item cocok +10).
     * Usulan hanya bila skor >= 60 dan pemenang tunggal.
     * @return ?array{ref:string,tema:string,alasan:string,skor:int}
     */
    public static function saran(array $fs, array $kandidat): ?array
    {
        $namaB  = (string) ($fs['nama_barang'] ?? '');
        $kep    = (string) ($fs['keperluan'] ?? '');
        $terima = substr((string) ($fs['tanggal_diterima'] ?? ''), 0, 7);
        $jenisB = self::jenisDari($namaB);
        $ukB    = self::ukuranDari($namaB);
        $bulanKep = self::bulanDisebut($kep);
        $teksCari = ' ' . BudgetFsRekomendasi::normal($namaB . ' ' . $kep) . ' ';
        $tokB   = self::tokenBermakna($namaB);

        $hasil = [];
        foreach ($kandidat as $t) {
            $skorT = 0; $ket = [];
            if ($t['kind'] === 'bulanan' && $t['bulan'] !== '') {
                $no = (int) substr($t['bulan'], 5, 2);
                if (in_array($no, $bulanKep, true)) { $skorT += 30; $ket[] = 'Tema Bulanan ' . self::NAMA_BULAN[$no] . ' ' . substr($t['bulan'], 0, 4) . ' (keperluan menyebut ' . strtolower(self::NAMA_BULAN[$no]) . ')'; }
            }
            if ($t['kind'] === 'event') {
                $nt = self::tokenBermakna($t['nama']);
                $kena = array_intersect($nt, self::tokenBermakna($kep . ' ' . $namaB));
                if (count($nt) && (count($kena) >= min(2, count($nt)))) { $skorT += 40; $ket[] = 'nama event "' . $t['nama'] . '" disebut'; }
            }
            if ($t['bulan'] !== '' && $t['bulan'] === $terima) $skorT += 10;
            foreach ($t['items'] as $it) {
                $skorI = 0; $ketI = [];
                $jI = self::jenisDari(($it['jenis'] ?: '') . ' ' . $it['nama']);
                if ($jenisB && $jI && $jenisB[0] === $jI[0]) { $skorI += 50; $ketI[] = 'jenis ' . $jenisB[1]; }
                $uI = self::ukuranDari((string) $it['ukuran'] . ' ' . $it['nama']);
                if ($ukB && $uI && $ukB === $uI) { $skorI += 40; $ketI[] = 'ukuran ' . $ukB[0] . '×' . $ukB[1] . ' cm'; }
                if (self::tokenBermakna($it['nama']) && array_intersect(self::tokenBermakna($it['nama']), $tokB)) { $skorI += 10; $ketI[] = 'nama item mirip'; }
                if ($skorI < 50) continue;   // minimal jenis cocok (atau ukuran + nama)
                $total = $skorT + $skorI;
                $hasil[] = ['ref' => $it['ref'], 'tema' => $t['key'], 'skor' => $total, 'alasan' => implode(' · ', array_merge($ket, $ketI))];
            }
        }
        if (! $hasil) return null;
        usort($hasil, fn ($a, $b) => $b['skor'] <=> $a['skor']);
        if ($hasil[0]['skor'] < 60) return null;
        if (isset($hasil[1]) && $hasil[1]['skor'] === $hasil[0]['skor']) return null;   // seri: jangan menebak
        return $hasil[0];
    }
}
