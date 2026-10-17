<?php

namespace App\Libraries;

/**
 * Pembaca feed baca-saja FlowStore (VPS terpisah). Dua driver dalam satu kelas:
 *
 *  - http    : GET {flowstore.url}/api/feed/btb-item?dari=&sampai=[&id_divisi=][&setelah_id=][&termasuk_batal=1]
 *              dan /api/feed/divisi, header X-Feed-Token. Halaman demi halaman memakai kursor.
 *  - fixture : membaca berkas JSON berbentuk sama (writable/flowstore-fixture.json) — untuk uji tanpa jaringan.
 *
 * .env:  flowstore.mode = http | fixture      (kosong: http bila url+token terisi, selain itu "mati")
 *        flowstore.url, flowstore.token       (token JANGAN dicetak/disimpan di repo)
 *        flowstore.fixture = path berkas      (bawaan WRITEPATH/flowstore-fixture.json)
 *        flowstore.timeout = detik            (bawaan 20)
 *
 * Semua galat dilempar sebagai FlowstoreGalat dengan pesan ramah yang TIDAK memuat token maupun URL penuh.
 */
class FlowstoreFeed
{
    const VERSI       = 1;
    const MAKS_HALAMAN = 400;        // pengaman putaran kursor
    const GALAT_KURSOR = 'KURSOR';
    const UKURAN_FIXTURE = 200;      // ukuran "halaman" simulasi fixture

    private string $mode;
    private string $url;
    private string $token;
    private string $fixture;
    private int $timeout;

    public function __construct(array $konfig = [])
    {
        $url   = rtrim((string) ($konfig['url'] ?? env('flowstore.url', '')), '/');
        $token = (string) ($konfig['token'] ?? env('flowstore.token', ''));
        $mode  = strtolower(trim((string) ($konfig['mode'] ?? env('flowstore.mode', ''))));
        if (! in_array($mode, ['http', 'fixture'], true)) $mode = ($url !== '' && $token !== '') ? 'http' : 'mati';

        $this->mode    = $mode;
        $this->url     = $url;
        $this->token   = $token;
        $this->fixture = (string) ($konfig['fixture'] ?? env('flowstore.fixture', '')) ?: WRITEPATH . 'flowstore-fixture.json';
        $this->timeout = max(2, (int) ($konfig['timeout'] ?? env('flowstore.timeout', 20)));
    }

    public function mode(): string { return $this->mode; }

    /** Siap dipakai? (http: url+token terisi; fixture: berkas ada) */
    public function siap(): bool
    {
        if ($this->mode === 'http') return $this->url !== '' && $this->token !== '';
        if ($this->mode === 'fixture') return is_file($this->fixture);
        return false;
    }

    /** Penjelasan ramah bila belum siap. */
    public function alasanBelumSiap(): string
    {
        if ($this->mode === 'http') return 'Alamat atau token FlowStore belum diisi di pengaturan server (.env).';
        if ($this->mode === 'fixture') return 'Berkas contoh data FlowStore tidak ditemukan.';
        return 'Penautan FlowStore belum dikonfigurasi di server (.env: flowstore.url dan flowstore.token).';
    }

    /**
     * Seluruh item BTB pada rentang tanggal (mengikuti kursor sampai habis).
     * @return array<int,array<string,mixed>> item mentah dari feed
     */
    public function btbItem(string $dari, string $sampai, ?int $idDivisi = null, bool $termasukBatal = true): array
    {
        $this->pastikanSiap();
        $mulaiUlang = false;
        ulang:
        $semua = [];
        $setelah = 0;
        for ($i = 0; $i < self::MAKS_HALAMAN; $i++) {
            $q = ['dari' => $dari, 'sampai' => $sampai];
            if ($idDivisi) $q['id_divisi'] = $idDivisi;
            if ($setelah)  $q['setelah_id'] = $setelah;
            if ($termasukBatal) $q['termasuk_batal'] = 1;

            try {
                $r = $this->mode === 'http' ? $this->ambilHttp('/api/feed/btb-item', $q) : $this->ambilFixture('btb-item', $q);
            } catch (FlowstoreGalat $g) {
                // Item penanda kursor dihapus di FlowStore (400 FEED_KURSOR_TIDAK_DIKENAL): mulai ulang dari awal rentang, sekali saja.
                if ($g->getMessage() === self::GALAT_KURSOR && ! $mulaiUlang) { $mulaiUlang = true; goto ulang; }
                if ($g->getMessage() === self::GALAT_KURSOR) throw new FlowstoreGalat('Penanda halaman FlowStore berubah terus; coba sinkron lagi.');
                throw $g;
            }
            $items = $r['items'] ?? null;
            if (! is_array($items)) throw new FlowstoreGalat('Jawaban FlowStore tidak berbentuk daftar item.');
            foreach ($items as $it) if (is_array($it)) $semua[] = $it;

            if (empty($r['ada_lagi'])) return $semua;
            $kursor = (int) ($r['kursor'] ?? 0);
            if ($kursor <= $setelah) throw new FlowstoreGalat('Kursor halaman FlowStore tidak maju; sinkron dihentikan agar tidak berputar.');
            $setelah = $kursor;
        }
        throw new FlowstoreGalat('Data FlowStore terlalu banyak untuk satu kali sinkron; persempit rentang tanggal.');
    }

    /** @return array<int,array{id_divisi:int,divisi:string,kode:?string}> */
    public function divisi(): array
    {
        $this->pastikanSiap();
        $r = $this->mode === 'http' ? $this->ambilHttp('/api/feed/divisi', []) : $this->ambilFixture('divisi', []);
        $out = [];
        foreach ((array) ($r['divisi'] ?? []) as $d) {
            if (! is_array($d) || empty($d['id_divisi'])) continue;
            $out[] = ['id_divisi' => (int) $d['id_divisi'], 'divisi' => (string) ($d['divisi'] ?? ''), 'kode' => isset($d['kode']) ? (string) $d['kode'] : null];
        }
        if (! $out) throw new FlowstoreGalat('FlowStore tidak mengirim daftar divisi.');
        return $out;
    }

    private function pastikanSiap(): void
    {
        if (! $this->siap()) throw new FlowstoreGalat($this->alasanBelumSiap());
    }

    // ── driver http ──────────────────────────────────────────────────

    private function ambilHttp(string $path, array $query): array
    {
        $ch = curl_init($this->url . $path . ($query ? '?' . http_build_query($query) : ''));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(6, $this->timeout),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => ['X-Feed-Token: ' . $this->token, 'Accept: application/json'],
            CURLOPT_MAXFILESIZE    => 64 * 1024 * 1024,
        ]);
        $isi  = curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_errno($ch);
        curl_close($ch);

        if ($isi === false || $kode === 0) {
            if (in_array($err, [CURLE_OPERATION_TIMEOUTED], true)) throw new FlowstoreGalat('FlowStore tidak menjawab dalam ' . $this->timeout . ' detik.');
            throw new FlowstoreGalat('FlowStore tidak terjangkau dari server ini. Coba lagi nanti.');
        }
        if ($kode === 401 || $kode === 403) throw new FlowstoreGalat('FlowStore menolak token akses (periksa flowstore.token di server).');
        if ($kode === 400 && str_contains((string) $isi, 'FEED_KURSOR_TIDAK_DIKENAL')) throw new FlowstoreGalat(self::GALAT_KURSOR);
        if ($kode === 404) throw new FlowstoreGalat('Feed FlowStore belum aktif atau alamatnya salah (404).');
        if ($kode >= 500) throw new FlowstoreGalat('FlowStore sedang bermasalah (HTTP ' . $kode . '). Coba lagi nanti.');
        if ($kode !== 200) throw new FlowstoreGalat('Jawaban FlowStore tak terduga (HTTP ' . $kode . ').');

        return $this->urai($isi);
    }

    private function urai(string $isi): array
    {
        $j = json_decode($isi, true);
        if (! is_array($j)) throw new FlowstoreGalat('Jawaban FlowStore bukan JSON yang sah.');
        if ((int) ($j['versi'] ?? 0) !== self::VERSI) throw new FlowstoreGalat('Versi feed FlowStore tidak dikenal (' . (int) ($j['versi'] ?? 0) . ').');
        return $j;
    }

    // ── driver fixture ───────────────────────────────────────────────

    private function ambilFixture(string $jenis, array $q): array
    {
        $isi = @file_get_contents($this->fixture);
        if ($isi === false) throw new FlowstoreGalat('Berkas contoh data FlowStore tidak terbaca.');
        $j = json_decode($isi, true);
        if (! is_array($j)) throw new FlowstoreGalat('Berkas contoh data FlowStore bukan JSON yang sah.');
        $r = self::sajikan($j, $jenis, $q);
        if (($r['_status'] ?? 0) === 400) throw new FlowstoreGalat(self::GALAT_KURSOR);
        return $r;
    }

    /**
     * Aturan penyajian yang SAMA untuk driver fixture dan server tiruan (scripts/flowstore-tiruan.php):
     * saring tanggal (tanggal_diterima, jatuh ke tanggal_btb bila kosong), divisi, status batal, lalu potong per halaman.
     */
    public static function sajikan(array $j, string $jenis, array $q): array
    {
        if ($jenis === 'divisi') {
            return ['versi' => self::VERSI, 'dibuat' => date('c'), 'divisi' => array_values((array) ($j['divisi'] ?? []))];
        }
        $dari   = (string) ($q['dari'] ?? '0000-01-01');
        $sampai = (string) ($q['sampai'] ?? '9999-12-31');
        $div    = (int) ($q['id_divisi'] ?? 0);
        $setelah = (int) ($q['setelah_id'] ?? 0);
        $batal  = ! empty($q['termasuk_batal']);

        if ($setelah) {   // seperti feed asli: penanda kursor yang sudah tak ada → 400
            $ada = false;
            foreach ((array) ($j['items'] ?? []) as $it) if ((int) $it['id_btb_item'] === $setelah) { $ada = true; break; }
            if (! $ada) return ['_status' => 400, 'galat' => 'FEED_KURSOR_TIDAK_DIKENAL'];
        }
        $cocok = [];
        foreach ((array) ($j['items'] ?? []) as $it) {
            $tgl = (string) (($it['tanggal_diterima'] ?? null) ?: ($it['tanggal_btb'] ?? ''));
            if ($tgl < $dari || $tgl > $sampai) continue;
            if ($div && (int) ($it['id_divisi'] ?? 0) !== $div) continue;
            if ((int) $it['id_btb_item'] <= $setelah) continue;
            if (! $batal && ($it['status_btb'] ?? '') === 'batal') continue;
            $cocok[] = $it;
        }
        usort($cocok, fn ($a, $b) => (int) $a['id_btb_item'] <=> (int) $b['id_btb_item']);
        $adaLagi = count($cocok) > self::UKURAN_FIXTURE;
        $halaman = array_slice($cocok, 0, self::UKURAN_FIXTURE);
        return [
            'versi' => self::VERSI, 'dibuat' => date('c'), 'jumlah' => count($halaman), 'ada_lagi' => $adaLagi,
            'kursor' => $halaman ? (int) end($halaman)['id_btb_item'] : $setelah, 'items' => $halaman,
        ];
    }
}

class FlowstoreGalat extends \RuntimeException {}
