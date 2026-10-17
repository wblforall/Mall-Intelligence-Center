<?php

namespace App\Libraries;

/**
 * Mesin rekomendasi pos budget untuk item BTB FlowStore. HANYA menyarankan —
 * user yang memutuskan (keputusan pemilik 17 Okt 2026). Urutan prioritas:
 *
 *  (0) keperluan : keterangan order ("untuk kebutuhan apa") menjelaskan TUJUAN pembelian, lebih terang daripada
 *                  nama barang (kabel UTP untuk CCTV vs untuk kantor). Aturan kata kunci bersasaran 'keperluan'
 *                  (berurutan; yang pertama cocok menang; skor 80; "tanpa pos" menutup rekomendasi),
 *                  lalu — bila tak ada aturan — kemiripan keperluan ↔ nama pos (skor 55, hanya bila unik & jelas),
 *  (a) memori  : kode_barang / nama ternormalisasi yang pernah dicatat user di dept yang sama
 *                (skor 90–99; pos yang paling sering dipakai menang),
 *  (b) kata kunci : aturan bawaan/admin per dept (jenis 'kata_kunci'; skor 70),
 *  (b2) pengganti terakhir: aturan bertanda 'pengganti' (keperluan lalu nama; skor 40) — hanya bila tak ada aturan biasa yang cocok,
 *  (c) kemiripan  : token nama barang ↔ nama pos (skor 30–60),
 *  (d) tanpa rekomendasi.
 *
 * Aturan kata kunci dapat memuat kualifier MAL (kolom budget_fs_aturan.mal: ewalk | pentacity | both): aturan hanya dipakai bila mal item
 * (dari skema FlowStore; selain Ewalk/Pentacity = 'both') sama. Aturan tanpa mal berlaku untuk semua mal. Bila ada aturan bermal yang
 * cocok dengan nama/keperluan item, memori tidak dipakai (memori hanya mengenal nama barang, tak tahu mal-nya).
 *
 * Hanya pos AKTIF milik dept yang pernah disarankan. Tanpa akses DB lain selain
 * budget_pos & budget_fs_aturan; dimuat sekali per dept lalu di-cache di objek.
 */
class BudgetFsRekomendasi
{
    /** Kata yang tak membawa makna saat membandingkan nama barang dengan nama pos. */
    private const KATA_UMUM = ['DAN', 'ATAU', 'YANG', 'UNTUK', 'DENGAN', 'THE', 'AND', 'FOR', 'PCS', 'UNIT', 'SET', 'BOX', 'PACK',
        'ORIGINAL', 'WARNA', 'HITAM', 'PUTIH', 'UK', 'UKURAN', 'GEDUNG', 'BIAYA', 'PENGADAAN', 'PENAMBAHAN', 'MAINTENANCE', 'SUBS', 'VENDOR', 'JASA', 'DASHBOARD', 'MONITORING', 'INTEGRATED'];

    /** @var array<int,array> cache per dept: ['pos'=>[id=>row], 'memori'=>[kunci=>[pos_id=>kali]], 'kata'=>[rows]] */
    private array $cache = [];
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect();
    }

    /** Nama ternormalisasi: huruf besar, hanya A-Z0-9 dan spasi tunggal. */
    public static function normal(?string $s): string
    {
        $s = strtoupper((string) $s);
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s);
        return trim((string) $s);
    }

    /** Kunci memori untuk item: [K:<kode>, N:<nama>] (kode lebih dulu bila ada). */
    public static function kunciMemori(array $item): array
    {
        $out = [];
        $kode = self::normal($item['kode_barang'] ?? '');
        if ($kode !== '') $out[] = 'K:' . $kode;
        $nama = self::normal($item['nama_barang'] ?? '');
        if ($nama !== '') $out[] = 'N:' . mb_substr($nama, 0, 188);
        return $out;
    }

    private function muat(int $deptId): array
    {
        if (isset($this->cache[$deptId])) return $this->cache[$deptId];
        $pos = [];
        foreach ($this->db->table('budget_pos')->where('dept_id', $deptId)->get()->getResultArray() as $p) $pos[(int) $p['id']] = $p;
        $memori = $kata = $kataKep = $kataP = $kataKepP = [];
        $rows = $this->db->table('budget_fs_aturan')->where('dept_id', $deptId)->orderBy('urutan')->orderBy('id')->get()->getResultArray();
        foreach ($rows as $a) {
            $ganti = ! empty($a['pengganti']);
            if ($a['jenis'] === 'memori') $memori[$a['kunci']][(int) $a['pos_id']] = (int) $a['dipakai_berapa_kali'];
            elseif (($a['sasaran'] ?? 'nama') === 'keperluan') { if ($ganti) $kataKepP[] = $a; else $kataKep[] = $a; }
            elseif ($ganti) $kataP[] = $a;
            else $kata[] = $a;
        }
        return $this->cache[$deptId] = ['pos' => $pos, 'memori' => $memori, 'kata' => $kata, 'kataKep' => $kataKep, 'kataP' => $kataP, 'kataKepP' => $kataKepP];
    }

    /** Lupakan cache (setelah memori berubah). */
    public function segarkan(): void { $this->cache = []; }

    /** Mal item dari skema FlowStore: 'ewalk' | 'pentacity' | 'both' (skema tak dikenal / tak ada). */
    public static function malItem(array $item): string
    {
        return self::malDariSkema(isset($item['id_skema']) && $item['id_skema'] !== null && $item['id_skema'] !== '' ? (int) $item['id_skema'] : null, $item['skema'] ?? null);
    }

    /** Mal bawaan dari skema FlowStore (1 Pentacity, 2 Ewalk) — selain itu keduanya ('both'). */
    public static function malDariSkema(?int $idSkema, ?string $skema): string
    {
        $s = strtolower((string) $skema);
        if ($idSkema === 2 || str_contains($s, 'ewalk') || str_contains($s, 'e-walk')) return 'ewalk';
        if ($idSkema === 1 || str_contains($s, 'penta')) return 'pentacity';
        return 'both';
    }

    /**
     * @param array $item baris fs_btb_item (butuh kode_barang, nama_barang; keperluan, id_skema/skema bila ada)
     * @return array{pos_id:?int,skor:float,sumber:string,alasan:string}  sumber: memori|kata_kunci|keperluan|kemiripan|none
     */
    public function untuk(int $deptId, array $item): array
    {
        $d = $this->muat($deptId);
        $aktif = fn (int $id) => isset($d['pos'][$id]) && ! empty($d['pos'][$id]['aktif']);
        $nomor = [];
        foreach ($d['pos'] as $p) $nomor[mb_strtolower($p['nama'])] = (int) $p['id'];
        $mal = self::malItem($item);
        $nama = ' ' . self::normal($item['nama_barang'] ?? '') . ' ';

        // (0) keperluan (keterangan order). Item tanpa keperluan (feed lama) langsung ke langkah berikutnya.
        $kep = self::normal($item['keperluan'] ?? '');
        if ($kep !== '') {
            $kepB = ' ' . $kep . ' ';
            $r = $this->kataKeperluan($d, $kepB, $nama, $nomor, $aktif, $mal, false);
            if ($r) return $r;
            $mirip = $this->miripKeperluan($d, $kep);
            if ($mirip) {
                return ['pos_id' => $mirip[0], 'skor' => 55.0, 'sumber' => 'keperluan',
                        'alasan' => 'keperluan mirip pos (' . implode(', ', array_map('strtolower', $mirip[1])) . ')'];
            }
        }

        // (a) memori persis — kode dulu, lalu nama. Dilewati bila ada aturan bermal yang cocok (memori tak tahu mal).
        if (! $this->malMenentukan($d, ' ' . $kep . ' ', $nama)) {
            foreach (self::kunciMemori($item) as $kunci) {
                $kandidat = array_filter($d['memori'][$kunci] ?? [], fn ($n, $pid) => $n > 0 && $aktif((int) $pid), ARRAY_FILTER_USE_BOTH);
                if (! $kandidat) continue;
                arsort($kandidat);
                $pid = (int) array_key_first($kandidat);
                $n = (int) $kandidat[$pid];
                return ['pos_id' => $pid, 'skor' => (float) min(99, 90 + $n), 'sumber' => 'memori',
                        'alasan' => 'pernah dipetakan ' . $n . '× ke pos ini' . (str_starts_with($kunci, 'K:') ? ' (kode barang sama)' : ' (nama barang sama)')];
            }
        }

        // (b) kata kunci pada nama barang (lihat kataNama)
        $nm = $this->kataNama($d, $nama, $nomor, $aktif, $mal, false);
        if ($nm) return $nm;

        // (b2) pengganti terakhir: keperluan lalu nama (hanya bila tak ada aturan biasa yang cocok)
        if ($kep !== '') {
            $r = $this->kataKeperluan($d, ' ' . $kep . ' ', $nama, $nomor, $aktif, $mal, true);
            if ($r) return $r;
        }
        $nm = $this->kataNama($d, $nama, $nomor, $aktif, $mal, true);
        if ($nm) return $nm;

        // (c) kemiripan token nama barang ↔ nama pos
        $tokBarang = self::token($nama);
        $terbaik = null; $skorTerbaik = 0.0; $kenaTerbaik = [];
        if ($tokBarang) {
            foreach ($d['pos'] as $p) {
                if (empty($p['aktif'])) continue;
                $tokPos = self::token(self::normal($p['nama']));
                if (! $tokPos) continue;
                $irisan = array_values(array_intersect($tokPos, $tokBarang));
                if (! $irisan) continue;
                $skor = count($irisan) / count($tokPos);
                if ($skor > $skorTerbaik) { $skorTerbaik = $skor; $terbaik = (int) $p['id']; $kenaTerbaik = $irisan; }
            }
        }
        if ($terbaik !== null && $skorTerbaik >= 0.5) {
            return ['pos_id' => $terbaik, 'skor' => round(30 + 30 * $skorTerbaik, 2), 'sumber' => 'kemiripan',
                    'alasan' => 'nama mirip pos (' . implode(', ', array_map('strtolower', $kenaTerbaik)) . ')'];
        }

        return ['pos_id' => null, 'skor' => 0.0, 'sumber' => 'none', 'alasan' => 'tidak ada petunjuk — pilih pos'];
    }

    /** Aturan ini berlaku untuk mal item? (tanpa kualifier = semua mal) */
    private static function malCocok(array $a, string $mal): bool
    {
        $m = trim((string) ($a['mal'] ?? ''));
        return $m === '' || $m === $mal;
    }

    /** Kata yang kena untuk alasan; pola regex lookahead tak punya teks cocok -> 'pola aturan'. */
    private static function labelKena(array $kena): string
    {
        return trim((string) $kena[0]) !== '' ? (string) $kena[0] : 'pola aturan';
    }

    private static function labelMal(array $a): string
    {
        $m = trim((string) ($a['mal'] ?? ''));
        return $m === '' ? '' : ' · mal ' . (['ewalk' => 'E-Walk', 'pentacity' => 'Pentacity', 'both' => 'tak dikenal'][$m] ?? $m);
    }

    /** Adakah aturan bermal (jenis apa pun) yang cocok dengan keperluan/nama item, terlepas dari mal-nya? */
    private function malMenentukan(array $d, string $kepB, string $nama): bool
    {
        foreach (['kataKep' => $kepB, 'kataKepP' => $kepB, 'kata' => $nama, 'kataP' => $nama] as $daftar => $teks) {
            foreach ($d[$daftar] as $a) {
                if (trim((string) ($a['mal'] ?? '')) === '') continue;
                if (self::cocokKata((string) $a['kunci'], $teks) !== null) return true;
            }
        }
        return false;
    }

    /**
     * (0) Aturan kata kunci pada KEPERLUAN, berurutan (yang pertama cocok menang; skor 80; "tanpa pos" menutup rekomendasi).
     * $pengganti = true: aturan "pengganti terakhir" (skor 40).
     */
    private function kataKeperluan(array $d, string $kepB, string $nama, array $nomor, callable $aktif, string $mal, bool $pengganti): ?array
    {
        foreach ($d[$pengganti ? 'kataKepP' : 'kataKep'] as $a) {   // urutan aturan = prioritas
            if (! self::malCocok($a, $mal)) continue;
            $tanpaPos = trim((string) $a['pos_nama']) === '' && ! $a['pos_id'];
            $pid = $a['pos_id'] ? (int) $a['pos_id'] : ($nomor[mb_strtolower((string) $a['pos_nama'])] ?? 0);
            if (! $tanpaPos && (! $pid || ! $aktif($pid))) continue;
            $kena = self::cocokKata((string) $a['kunci'], $kepB);
            if ($kena === null) continue;
            if ($tanpaPos) {
                return ['pos_id' => null, 'skor' => 0.0, 'sumber' => 'none',
                        'alasan' => 'keperluan "' . strtolower(self::labelKena($kena)) . '": tidak ada pos khusus untuk ini — pilih manual' . self::labelMal($a)];
            }
            $alasan = 'keperluan "' . strtolower(self::labelKena($kena)) . '"' . ($pengganti ? ' (pengganti terakhir)' : '') . self::labelMal($a);
            if (! $pengganti) {
                $nm = $this->kataNama($d, $nama, $nomor, $aktif, $mal, false);
                if ($nm && $nm['pos_id'] === $pid) $alasan .= ' · nama barang sejalan';
                elseif ($nm && $nm['pos_id']) $alasan .= ' (mengalahkan nama barang → ' . $d['pos'][$nm['pos_id']]['nama'] . ')';
            }
            return ['pos_id' => $pid, 'skor' => $pengganti ? 40.0 : 80.0, 'sumber' => 'keperluan', 'alasan' => $alasan];
        }
        return null;
    }

    /**
     * (b) Kata kunci pada NAMA barang. Aturan "tanpa pos" (pos_nama kosong, mis. perangkat jaringan) DIDAHULUKAN dan
     * menutup rekomendasi; selain itu kata kunci yang muncul paling awal pada nama barang menang
     * (CHARGER ADAPTOR LAPTOP → "CHARGER" di depan → pos aksesori, bukan pos laptop); seri → urutan aturan.
     * Aturan bermal hanya dipertimbangkan bila mal-nya sama dengan mal item. $pengganti = true: hanya aturan "pengganti terakhir" (skor 40).
     * @return ?array hasil rekomendasi (sumber kata_kunci/none) atau null bila tak ada yang cocok
     */
    private function kataNama(array $d, string $nama, array $nomor, callable $aktif, string $mal = 'both', bool $pengganti = false): ?array
    {
        $menang = null;
        foreach ($d[$pengganti ? 'kataP' : 'kata'] as $a) {
            if (! self::malCocok($a, $mal)) continue;
            $tanpaPos = trim((string) $a['pos_nama']) === '' && ! $a['pos_id'];
            $pid = $a['pos_id'] ? (int) $a['pos_id'] : ($nomor[mb_strtolower((string) $a['pos_nama'])] ?? 0);
            if (! $tanpaPos && (! $pid || ! $aktif($pid))) continue;
            $kena = self::cocokKata((string) $a['kunci'], $nama);
            if ($kena === null) continue;
            if ($tanpaPos) {
                return ['pos_id' => null, 'skor' => 0.0, 'sumber' => 'none',
                        'alasan' => 'kata kunci ' . self::labelKena($kena) . ': tidak ada pos khusus untuk ini — pilih manual' . self::labelMal($a)];
            }
            if ($menang === null || $kena[1] < $menang['posisi']) $menang = ['pid' => $pid, 'kata' => self::labelKena($kena), 'posisi' => $kena[1], 'label' => self::labelMal($a)];
        }
        return $menang ? ['pos_id' => $menang['pid'], 'skor' => $pengganti ? 40.0 : 70.0, 'sumber' => 'kata_kunci',
                          'alasan' => 'kata kunci ' . $menang['kata'] . ($pengganti ? ' (pengganti terakhir)' : '') . $menang['label']] : null;
    }

    /**
     * Kemiripan keperluan ↔ nama pos, sengaja KONSERVATIF: seluruh token bermakna milik pos (≥ 2 token) harus
     * ada di keperluan, dan pemenangnya harus tunggal (seri → tanpa rekomendasi).
     * @return ?array{0:int,1:array} [pos_id, token yang cocok]
     */
    private function miripKeperluan(array $d, string $kepNormal): ?array
    {
        $tokKep = self::token($kepNormal);
        if (! $tokKep) return null;
        $terbaik = []; $maks = 0;
        foreach ($d['pos'] as $p) {
            if (empty($p['aktif'])) continue;
            $tokPos = self::token(self::normal($p['nama']));
            if (count($tokPos) < 2) continue;
            $irisan = array_values(array_intersect($tokPos, $tokKep));
            if (count($irisan) !== count($tokPos)) continue;
            if (count($irisan) > $maks) { $maks = count($irisan); $terbaik = []; }
            if (count($irisan) === $maks) $terbaik[] = [(int) $p['id'], $irisan];
        }
        return count($terbaik) === 1 ? $terbaik[0] : null;
    }

    /**
     * Cocokkan satu aturan kata kunci terhadap nama barang ternormalisasi (berspasi di tepi).
     * Aturan: "/regex/" atau daftar kata dipisah "|" / ",". Kata ≥ 4 huruf boleh cocok sebagian
     * (DOORLOCK ⊂ "SMART DOORLOCK"), kata pendek harus kata utuh (PC ≠ SPECIAL). Mengembalikan [kata yang kena, posisi] atau null.
     */
    public static function cocokKata(string $pola, string $namaBerspasi): ?array
    {
        $pola = trim($pola);
        if (strlen($pola) > 2 && $pola[0] === '/' && substr($pola, -1) === '/') {
            return @preg_match($pola . 'iu', $namaBerspasi, $m, PREG_OFFSET_CAPTURE) === 1 ? [trim($m[0][0]), (int) $m[0][1]] : null;
        }
        $terbaik = null;
        foreach (preg_split('/[|,]/', $pola) as $kata) {
            $k = self::normal($kata);
            if ($k === '') continue;
            $pos = (strlen($k) >= 4 || str_contains($k, ' ')) ? strpos($namaBerspasi, $k) : strpos($namaBerspasi, ' ' . $k . ' ');
            if ($pos !== false && ($terbaik === null || $pos < $terbaik[1])) $terbaik = [$k, (int) $pos];
        }
        return $terbaik;
    }

    /** Token bermakna: ≥ 3 huruf/angka, bukan angka murni, bukan kata umum. */
    private static function token(string $normal): array
    {
        $out = [];
        foreach (explode(' ', trim($normal)) as $t) {
            if (strlen($t) < 3 || ctype_digit($t) || in_array($t, self::KATA_UMUM, true)) continue;
            $out[$t] = $t;
        }
        return array_values($out);
    }

    // ── Memori: dipanggil saat user menekan "Catat" / saat realisasi hasil catat dihapus ──

    /** Catat bahwa item ini dipetakan ke pos tersebut (+1 untuk kunci kode & nama). Panggil dalam transaksi pemanggil. */
    public function ingat(int $deptId, array $item, int $posId): void
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::kunciMemori($item) as $kunci) {
            $this->db->query(
                "INSERT INTO budget_fs_aturan (dept_id, jenis, kunci, pos_id, dipakai_berapa_kali, terakhir_dipakai, created_at)
                 VALUES (?, 'memori', ?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE dipakai_berapa_kali = dipakai_berapa_kali + 1, terakhir_dipakai = VALUES(terakhir_dipakai)",
                [$deptId, $kunci, $posId, $now, $now]
            );
        }
        $this->segarkan();
    }

    /** Kebalikan ingat() — realisasi hasil catat dihapus: kurangi, dan buang bila habis. */
    public function lupakan(int $deptId, array $item, int $posId): void
    {
        foreach (self::kunciMemori($item) as $kunci) {
            $this->db->query(
                "UPDATE budget_fs_aturan SET dipakai_berapa_kali = GREATEST(0, dipakai_berapa_kali - 1)
                  WHERE dept_id = ? AND jenis = 'memori' AND kunci = ? AND pos_id = ?", [$deptId, $kunci, $posId]);
            $this->db->query(
                "DELETE FROM budget_fs_aturan WHERE dept_id = ? AND jenis = 'memori' AND kunci = ? AND pos_id = ? AND dipakai_berapa_kali <= 0",
                [$deptId, $kunci, $posId]);
        }
        $this->segarkan();
    }
}
