<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Models\CreativeItemModel;
use App\Models\EventCompletionModel;
use App\Models\EventCreativeItemModel;
use App\Models\PromoMediaUsageModel;

/**
 * Titik Media Cetak (Creative Tahap 2b).
 *
 * Memilih titik dari master Media Promo (`promo_media_spots`) = MEMBUAT PEMESANAN TERHUBUNG di Media Promo
 * (`promo_media_usage`: sumber internal, dept Creative, status approved langsung, `creative_ref` = 'standalone:ID' | 'event:ID').
 * Tautan item -> titik -> pemesanan disimpan di `creative_cetak_titik` bersama bukti pasang (foto) per titik.
 *
 * Aturan bentrok TIDAK diduplikasi: memakai PromoMediaUsageModel::pemakaiCetak (aturan yang sama dengan hasConflictCetak).
 * Dua orang tak bisa memesan titik yang sama: transaksi + SELECT ... FOR UPDATE pada baris titik (urut id).
 *
 * Satu kelas melayani dua lingkup (item standalone `creative_items` / item event `event_creative_items`).
 * Hak ubah diperiksa pemanggil (controller); kunci Tema dan keadaan diperiksa di sini.
 */
class CreativeTitikService
{
    public const SCOPE_STANDALONE = 'standalone';
    public const SCOPE_EVENT      = 'event';

    /** Nama jenis media (huruf kecil) -> tipe titik di master Media Promo. Jenis lain: tanpa master, titik tidak ada. */
    private const JENIS_TIPE = [
        't-banner'        => 't_banner',
        'hanging banner'  => 'hanging',
        'hanging'         => 'hanging',
        'sticker lift'    => 'sticker_lift',
        'totem'           => 'totem_stainless',
        'totem stainless' => 'totem_stainless',
    ];

    public const TIPE_LABEL = [
        't_banner' => 'T-Banner', 'hanging' => 'Hanging Banner', 'sticker_lift' => 'Sticker Lift', 'totem_stainless' => 'Totem',
    ];

    private string $scope;
    private ?int $eventId;

    public function __construct(string $scope, ?int $eventId = null)
    {
        if (! in_array($scope, [self::SCOPE_STANDALONE, self::SCOPE_EVENT], true)) {
            throw new \InvalidArgumentException('Scope titik tidak dikenal: ' . $scope);
        }
        $this->scope   = $scope;
        $this->eventId = $eventId;
    }

    public static function standalone(): self { return new self(self::SCOPE_STANDALONE); }
    public static function forEvent(int $eventId): self { return new self(self::SCOPE_EVENT, $eventId); }

    private function isEvent(): bool { return $this->scope === self::SCOPE_EVENT; }

    // ───────────────────────────── Peta jenis ─────────────────────────────

    /** Tipe titik master untuk sebuah jenis media; null bila jenis itu tidak punya master. */
    public static function tipeSpot(?string $jenis): ?string
    {
        $k = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $jenis)));
        return self::JENIS_TIPE[$k] ?? null;
    }

    /** Nama jenis (tampilan) yang punya titik di master Media Promo, untuk JS formulir. */
    public static function jenisBerMaster(): array
    {
        return array_values(self::TIPE_LABEL);
    }

    /** Ukuran dinormalkan untuk membandingkan: "1×3 m" = "1 x 3 M". */
    public static function normUkuran(?string $u): string
    {
        $s = mb_strtolower(trim((string) $u));
        $s = str_replace(['×', '✕', '*'], 'x', $s);
        $s = preg_replace('/\s+/', '', $s);
        return str_replace(',', '.', $s);
    }

    public static function dirBukti(int $linkId): string
    {
        return FCPATH . 'uploads/creative-cetak-titik/' . $linkId . '/';
    }

    // ───────────────────────────── Formulir Media Cetak (dipakai kedua controller) ─────────────────────────────

    /** Kolom Media Cetak dari isian formulir; semua NULL bila item bukan Media Cetak. */
    public static function dataCetak(array $post): array
    {
        $cetak = ($post['tipe'] ?? '') === 'cetak';
        $t = fn($k, $n) => $cetak && trim((string) ($post[$k] ?? '')) !== '' ? mb_substr(trim((string) $post[$k]), 0, $n) : null;
        $tg = fn($k) => $cetak && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($post[$k] ?? '')) ? $post[$k] : null;
        $dr = $cetak && preg_match('/^(standalone|event):\d+$/', (string) ($post['design_ref'] ?? '')) ? $post['design_ref'] : null;
        return ['jenis_media' => $t('jenis_media', 60), 'ukuran' => $t('ukuran', 80),
                'pasang_mulai' => $tg('pasang_mulai'), 'pasang_selesai' => $tg('pasang_selesai'), 'design_ref' => $dr];
    }

    /** Validasi item Media Cetak yang disimpan lewat formulir: budget wajib > 0, periode pasang konsisten. Pesan galat atau null. */
    public static function validasiCetak(array $post): ?string
    {
        if (($post['tipe'] ?? '') !== 'cetak') return null;
        $budget = (int) str_replace([',', '.', ' '], '', (string) ($post['budget'] ?? 0));
        if ($budget <= 0) return 'Budget Media Cetak wajib diisi (lebih dari 0).';
        $m = trim((string) ($post['pasang_mulai'] ?? '')); $s = trim((string) ($post['pasang_selesai'] ?? ''));
        if (($m === '') !== ($s === '')) return 'Periode pasang diisi lengkap (mulai dan selesai) atau dikosongkan.';
        if ($m !== '' && ! self::periodeSah($m, $s)) return 'Periode pasang tidak valid: tanggal mulai tidak boleh melewati tanggal selesai.';
        return null;
    }

    // ───────────────────────────── Item & penguncian ─────────────────────────────

    private function item(int $id): ?array
    {
        $it = $this->isEvent() ? (new EventCreativeItemModel())->find($id) : (new CreativeItemModel())->find($id);
        if (! $it || $it['tipe'] !== 'cetak') return null;
        if ($this->isEvent() && (int) ($it['event_id'] ?? 0) !== (int) $this->eventId) return null;
        return $it;
    }

    /** Pesan penolakan bila item tak boleh diubah (Tema ditutup / modul event diselesaikan); null bila boleh. */
    private function terkunci(array $item): ?string
    {
        if ($this->isEvent()) {
            $eid = (int) $this->eventId;
            if (CreativeTemaService::eventDitutup($eid)) return CreativeTemaService::PESAN_TEMA_TUTUP;
            $done = (new EventCompletionModel())->getByEvent($eid)['creative'] ?? null;
            if ($done) return 'Modul Creative event ini sudah ditandai selesai sehingga terkunci.';
            return null;
        }
        return CreativeTemaService::itemTerkunci($item) ? CreativeTemaService::PESAN_TEMA_TUTUP : null;
    }

    /** ['key'=>'t5'|'e13'|'', 'nama'=>nama Tema, 'event_id'=>?int] untuk sebuah item. */
    public static function infoTema(string $scope, array $item): array
    {
        $db = db_connect();
        $eventId = $scope === self::SCOPE_EVENT ? (int) $item['event_id'] : (int) ($item['event_id'] ?? 0);
        if ($eventId > 0) {
            $e = $db->table('events')->select('name')->where('id', $eventId)->get()->getRowArray();
            return ['key' => 'e' . $eventId, 'nama' => (string) ($e['name'] ?? ''), 'event_id' => $eventId];
        }
        if (! empty($item['tema_id'])) {
            $t = $db->table('creative_tema')->select('nama')->where('id', (int) $item['tema_id'])->get()->getRowArray();
            return ['key' => 't' . (int) $item['tema_id'], 'nama' => (string) ($t['nama'] ?? ''), 'event_id' => null];
        }
        return ['key' => '', 'nama' => '', 'event_id' => null];
    }

    private function ref(int $itemId): string { return $this->scope . ':' . $itemId; }

    private static function rentang(string $a, string $b): string
    {
        $m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $f = fn($d) => date('d', strtotime($d)) . ' ' . $m[(int) date('n', strtotime($d)) - 1];
        return $f($a) . '–' . $f($b) . ' ' . date('Y', strtotime($b));
    }

    private static function periodeSah(?string $m, ?string $s): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $m) && (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $s) && $m <= $s;
    }

    private function gagal(string $msg): array { return ['ok' => false, 'message' => $msg]; }

    // ───────────────────────────── Pembacaan ─────────────────────────────

    /**
     * Titik beberapa item sekaligus (dengan data titik + pemesanan).
     * @return array<int,array{rows:array,total:int,terpasang:int}> keyed by item_id
     */
    public function untukItems(array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        $out = [];
        foreach ($itemIds as $id) $out[$id] = ['rows' => [], 'total' => 0, 'terpasang' => 0];
        if (! $itemIds) return $out;

        $rows = db_connect()->table('creative_cetak_titik l')
            ->select('l.*, s.kode spot_kode, s.nama spot_nama, s.area spot_area, s.ukuran spot_ukuran, s.tipe spot_tipe, '
                . 'u.status usage_status, u.tanggal_mulai usage_mulai, u.tanggal_selesai usage_selesai, un.name dibuat_nama')
            ->join('promo_media_spots s', 's.id = l.spot_id', 'left')
            ->join('promo_media_usage u', 'u.id = l.usage_id', 'left')
            ->join('users un', 'un.id = l.dibuat_oleh', 'left')
            ->where('l.scope', $this->scope)->whereIn('l.item_id', $itemIds)
            ->orderBy('s.area')->orderBy('s.kode')->orderBy('l.id')
            ->get()->getResultArray();
        foreach ($rows as $r) {
            $iid = (int) $r['item_id'];
            $out[$iid]['rows'][] = $r;
            $out[$iid]['total']++;
            if (! empty($r['tanggal_pasang'])) $out[$iid]['terpasang']++;
        }
        return $out;
    }

    /** Daftar titik tipe yang sesuai dengan keadaan pada periode pasang item, untuk panel "Pilih titik". */
    public function daftar(int $itemId): array
    {
        $item = $this->item($itemId);
        if (! $item) return $this->gagal('Item Media Cetak tidak ditemukan.');
        $tipe = self::tipeSpot($item['jenis_media'] ?? '');
        if (! $tipe) return $this->gagal('Jenis media ini tidak punya titik di master Media Promo.');

        $mulai = (string) ($item['pasang_mulai'] ?? ''); $selesai = (string) ($item['pasang_selesai'] ?? '');
        $periodeOk = self::periodeSah($mulai, $selesai);

        $db = db_connect();
        $spots = $db->table('promo_media_spots')->where('tipe', $tipe)->where('is_active', 1)->orderBy('area')->orderBy('kode')->get()->getResultArray();
        $mine  = [];
        foreach ($db->table('creative_cetak_titik')->select('spot_id')->where('scope', $this->scope)->where('item_id', $itemId)->get()->getResultArray() as $r) $mine[(int) $r['spot_id']] = true;

        $pakai = [];
        if ($periodeOk && $spots) {
            foreach ((new PromoMediaUsageModel())->pemakaiCetak(array_map('intval', array_column($spots, 'id')), $mulai, $selesai) as $u) {
                $pakai[(int) $u['spot_id']] ??= $u;
            }
        }

        $normItem = self::normUkuran($item['ukuran'] ?? '');
        $list = [];
        foreach ($spots as $s) {
            $sid = (int) $s['id'];
            $state = isset($mine[$sid]) ? 'terpilih' : (isset($pakai[$sid]) ? 'bentrok' : 'bebas');
            $p = $pakai[$sid] ?? null;
            $list[] = [
                'id' => $sid, 'kode' => $s['kode'], 'nama' => $s['nama'], 'area' => (string) $s['area'], 'ukuran' => (string) $s['ukuran'],
                'state' => $state,
                'pemakai' => $p ? ($p['nama_materi'] . ' · ' . $p['dept'] . ' · ' . self::rentang($p['tanggal_mulai'], $p['tanggal_selesai']) . ($p['creative_ref'] ? ' (dari Creative)' : '')) : '',
                'ukuranBeda' => $normItem !== '' && trim((string) $s['ukuran']) !== '' && $normItem !== self::normUkuran($s['ukuran']),
            ];
        }
        return [
            'ok' => true, 'tipe' => $tipe, 'tipeLabel' => self::TIPE_LABEL[$tipe], 'periodeOk' => $periodeOk,
            'mulai' => $mulai, 'selesai' => $selesai, 'ukuranItem' => (string) ($item['ukuran'] ?? ''), 'spots' => $list,
        ];
    }

    // ───────────────────────────── Pilih titik = buat pemesanan ─────────────────────────────

    public function pilih(int $itemId, array $spotIds, array $user): array
    {
        $item = $this->item($itemId);
        if (! $item) return $this->gagal('Item Media Cetak tidak ditemukan.');
        if ($msg = $this->terkunci($item)) return $this->gagal($msg);
        if (! empty($item['dari_paket'])) return $this->gagal('Item ini masih berstatus Rencana. Lengkapi dulu lewat Ubah item (budget wajib diisi), baru pilih titik.');
        $tipe = self::tipeSpot($item['jenis_media'] ?? '');
        if (! $tipe) return $this->gagal('Jenis media “' . ($item['jenis_media'] ?: 'belum diisi') . '” tidak punya titik di master Media Promo.');
        $mulai = (string) ($item['pasang_mulai'] ?? ''); $selesai = (string) ($item['pasang_selesai'] ?? '');
        if (! self::periodeSah($mulai, $selesai)) return $this->gagal('Isi periode pasang (mulai dan selesai) lewat Ubah item sebelum memilih titik.');
        $spotIds = array_values(array_unique(array_filter(array_map('intval', $spotIds))));
        if (! $spotIds) return $this->gagal('Pilih minimal satu titik.');

        $db = db_connect();
        $db->transBegin();
        try {
            // kunci baris titik (urut id) agar dua orang tak memesan titik yang sama
            $rows = $db->query('SELECT * FROM promo_media_spots WHERE id IN (' . implode(',', $spotIds) . ') ORDER BY id FOR UPDATE')->getResultArray();
            $spots = [];
            foreach ($rows as $r) $spots[(int) $r['id']] = $r;
            foreach ($spotIds as $sid) {
                if (! isset($spots[$sid]) || ! (int) $spots[$sid]['is_active'] || $spots[$sid]['tipe'] !== $tipe) {
                    $db->transRollback();
                    return $this->gagal('Ada titik yang tidak valid untuk jenis media ' . self::TIPE_LABEL[$tipe] . '.');
                }
            }

            $sudah = [];
            $batch = null;
            foreach ($db->table('creative_cetak_titik l')->select('l.spot_id, u.batch_id')->join('promo_media_usage u', 'u.id = l.usage_id', 'left')
                ->where('l.scope', $this->scope)->where('l.item_id', $itemId)->get()->getResultArray() as $r) {
                $sudah[(int) $r['spot_id']] = true;
                if (! $batch && ! empty($r['batch_id'])) $batch = $r['batch_id'];
            }
            $baru = array_values(array_filter($spotIds, fn($s) => ! isset($sudah[$s])));
            if (! $baru) {
                $db->transRollback();
                return $this->gagal('Titik yang dipilih sudah terpilih untuk item ini.');
            }

            $bentrok = (new PromoMediaUsageModel())->pemakaiCetak($baru, $mulai, $selesai);
            if ($bentrok) {
                $db->transRollback();
                $ket = [];
                foreach ($bentrok as $u) {
                    $ket[] = $spots[(int) $u['spot_id']]['kode'] . ' dipakai “' . $u['nama_materi'] . '” (' . $u['dept'] . ', ' . self::rentang($u['tanggal_mulai'], $u['tanggal_selesai']) . ($u['creative_ref'] ? ', dari Creative' : '') . ')';
                }
                return $this->gagal('Titik bentrok pada periode ' . self::rentang($mulai, $selesai) . ', tidak ada yang dipesan: ' . implode('; ', $ket) . '.');
            }

            $tema  = self::infoTema($this->scope, $item);
            $batch = $batch ?: substr(bin2hex(random_bytes(8)), 0, 16);
            $now   = date('Y-m-d H:i:s');
            $uid   = (int) $user['id'];
            $label = trim(($item['jenis_media'] ?: '') . ' ' . ($item['ukuran'] ?: ''));
            foreach ($baru as $sid) {
                $db->table('promo_media_usage')->insert([
                    'batch_id' => $batch, 'spot_id' => $sid, 'slot_number' => null,
                    'dept' => 'Creative', 'requested_by' => mb_substr((string) ($user['name'] ?? ''), 0, 100) ?: null,
                    'event_id' => $tema['event_id'], 'nama_materi' => mb_substr((string) $item['nama'], 0, 200),
                    'deskripsi_materi' => 'Dari Creative' . ($tema['nama'] !== '' ? ' · ' . $tema['nama'] : '') . ($label !== '' ? ' · ' . $label : ''),
                    'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai,
                    'status' => 'approved', 'sumber' => 'internal', 'is_berbayar' => 0,
                    'submitted_at' => $now, 'approved_by' => $uid, 'approved_at' => $now,
                    'catatan_approver' => 'Disetujui otomatis (pemesan internal Creative).',
                    'created_by' => $uid, 'created_at' => $now, 'updated_at' => $now, 'creative_ref' => $this->ref($itemId),
                ]);
                $uidUsage = (int) $db->insertID();
                $db->table('creative_cetak_titik')->insert([
                    'scope' => $this->scope, 'item_id' => $itemId, 'usage_id' => $uidUsage, 'spot_id' => $sid,
                    'dibuat_oleh' => $uid, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal memilih titik cetak: ' . $e->getMessage());
            return $this->gagal('Gagal menyimpan pemilihan titik. Coba lagi.');
        }

        $kode = array_map(fn($s) => $spots[$s]['kode'], $baru);
        ActivityLog::write('create', 'creative_cetak', $this->ref($itemId), $item['nama'], ['aksi' => 'pilih_titik', 'titik' => $kode, 'periode' => $mulai . ' s/d ' . $selesai]);
        $this->sinkronTurunan($item);
        $lewat = count($spotIds) - count($baru);
        return ['ok' => true, 'message' => count($baru) . ' titik dipilih (' . implode(', ', $kode) . '). Pemesanan di Media Promo dibuat dan langsung disetujui untuk '
            . self::rentang($mulai, $selesai) . '.' . ($lewat ? ' ' . $lewat . ' titik sudah terpilih sebelumnya (dilewati).' : '')];
    }

    // ───────────────────────────── Cabut titik ─────────────────────────────

    private function temukanLink(int $linkId): array
    {
        $l = db_connect()->table('creative_cetak_titik')->where('id', $linkId)->get()->getRowArray();
        if (! $l || $l['scope'] !== $this->scope) return ['err' => 'Titik tidak ditemukan.'];
        $item = $this->item((int) $l['item_id']);
        if (! $item) return ['err' => 'Item Media Cetak tidak ditemukan.'];
        return ['l' => $l, 'item' => $item];
    }

    private function kodeSpot(int $spotId): string
    {
        $r = db_connect()->table('promo_media_spots')->select('kode')->where('id', $spotId)->get()->getRowArray();
        return (string) ($r['kode'] ?? ('#' . $spotId));
    }

    /**
     * Cabut satu titik: pemesanan terhubung dihapus (hanya bila belum 'done'). Titik yang sudah ditandai terpasang
     * butuh konfirmasi ($konfirmasi = true); fotonya ikut terhapus.
     */
    public function cabut(int $linkId, bool $konfirmasi, array $user): array
    {
        $x = $this->temukanLink($linkId);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['l' => $l, 'item' => $item] = $x;
        if ($msg = $this->terkunci($item)) return $this->gagal($msg);

        $db = db_connect();
        $kode = $this->kodeSpot((int) $l['spot_id']);
        $u = $db->table('promo_media_usage')->where('id', (int) $l['usage_id'])->get()->getRowArray();
        if ($u && $u['status'] === 'done') {
            return $this->gagal('Pemesanan titik ' . $kode . ' sudah selesai (periode pasang lewat) dan menjadi riwayat Media Promo, sehingga tidak dapat dicabut.');
        }
        $terpasang = ! empty($l['tanggal_pasang']) || ! empty($l['bukti_pasang_file']);
        if ($terpasang && ! $konfirmasi) {
            return $this->gagal('Titik ' . $kode . ' sudah ditandai terpasang (ada bukti pasang). Konfirmasi dulu untuk mencabutnya; bukti pasangnya ikut terhapus.');
        }

        $db->transBegin();
        try {
            if ($u && $u['creative_ref'] === $this->ref((int) $l['item_id'])) $db->table('promo_media_usage')->where('id', (int) $u['id'])->delete();
            $db->table('creative_cetak_titik')->where('id', $linkId)->delete();
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal mencabut titik cetak: ' . $e->getMessage());
            return $this->gagal('Gagal mencabut titik. Coba lagi.');
        }
        $this->hapusBerkasLink($l);
        ActivityLog::write('delete', 'creative_cetak', $this->ref((int) $l['item_id']), $item['nama'], ['aksi' => 'cabut_titik', 'titik' => $kode, 'terpasang' => $terpasang]);
        $this->sinkronTurunan($item);
        return ['ok' => true, 'message' => 'Titik ' . $kode . ' dicabut dan pemesanannya di Media Promo dihapus.'];
    }

    private function hapusBerkasLink(array $l): void
    {
        $dir = self::dirBukti((int) $l['id']);
        if (! empty($l['bukti_pasang_file']) && is_file($dir . $l['bukti_pasang_file'])) @unlink($dir . $l['bukti_pasang_file']);
        if (is_dir($dir)) @rmdir($dir);
    }

    // ───────────────────────────── Siklus hidup item ─────────────────────────────

    /**
     * Dipanggil SEBELUM item disimpan: pastikan perubahan jenis / periode cocok dengan titik yang sudah dipilih,
     * dan bila periode berubah, perbarui pemesanan terhubung (atau tolak bila bentrok). Mengembalikan pesan galat atau null.
     * $baru: jenis_media, pasang_mulai, pasang_selesai (nilai yang akan disimpan).
     */
    public function selaraskan(array $itemLama, array $baru): ?string
    {
        $db = db_connect();
        $itemId = (int) $itemLama['id'];
        $links = $db->table('creative_cetak_titik l')->select('l.*, s.tipe spot_tipe, s.kode spot_kode, u.status usage_status')
            ->join('promo_media_spots s', 's.id = l.spot_id', 'left')->join('promo_media_usage u', 'u.id = l.usage_id', 'left')
            ->where('l.scope', $this->scope)->where('l.item_id', $itemId)->get()->getResultArray();
        if (! $links) return null;

        $tipeBaru = self::tipeSpot($baru['jenis_media'] ?? '');
        foreach ($links as $l) {
            if ($l['spot_tipe'] !== $tipeBaru) {
                return 'Jenis media tidak cocok dengan titik yang sudah dipilih (' . $l['spot_kode'] . ' bertipe ' . (self::TIPE_LABEL[$l['spot_tipe']] ?? $l['spot_tipe']) . '). Cabut titiknya dulu sebelum mengganti jenis media.';
            }
        }
        $mulai = (string) ($baru['pasang_mulai'] ?? ''); $selesai = (string) ($baru['pasang_selesai'] ?? '');
        if (! self::periodeSah($mulai, $selesai)) return 'Periode pasang wajib diisi (mulai dan selesai, mulai tidak melewati selesai) selama masih ada titik terpilih.';

        $berubah = $mulai !== (string) ($itemLama['pasang_mulai'] ?? '') || $selesai !== (string) ($itemLama['pasang_selesai'] ?? '');
        if (! $berubah) return null;

        $db->transBegin();
        try {
            $spotIds = array_map('intval', array_column($links, 'spot_id'));
            $usageIds = array_map('intval', array_column($links, 'usage_id'));
            $db->query('SELECT id FROM promo_media_spots WHERE id IN (' . implode(',', $spotIds) . ') ORDER BY id FOR UPDATE');
            foreach ($links as $l) {
                if ($l['usage_status'] === 'done') {
                    $db->transRollback();
                    return 'Periode tidak dapat diubah: pemesanan titik ' . $l['spot_kode'] . ' sudah selesai. Cabut titik yang sudah selesai dulu atau biarkan periodenya.';
                }
            }
            $bentrok = (new PromoMediaUsageModel())->pemakaiCetak($spotIds, $mulai, $selesai, $usageIds);
            if ($bentrok) {
                $db->transRollback();
                $kodeById = array_column($links, 'spot_kode', 'spot_id');
                $ket = array_map(fn($u) => ($kodeById[$u['spot_id']] ?? $u['spot_id']) . ' dipakai “' . $u['nama_materi'] . '” (' . $u['dept'] . ', ' . self::rentang($u['tanggal_mulai'], $u['tanggal_selesai']) . ')', $bentrok);
                return 'Periode baru bentrok dengan pemesanan lain, tidak disimpan: ' . implode('; ', $ket) . '.';
            }
            $db->table('promo_media_usage')->whereIn('id', $usageIds)->update(['tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai, 'updated_at' => date('Y-m-d H:i:s')]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal menyelaraskan periode titik: ' . $e->getMessage());
            return 'Gagal memperbarui pemesanan titik. Perubahan tidak disimpan.';
        }
        ActivityLog::write('update', 'creative_cetak', $this->ref($itemId), $itemLama['nama'], ['aksi' => 'ubah_periode', 'dari' => ($itemLama['pasang_mulai'] ?? '') . ' s/d ' . ($itemLama['pasang_selesai'] ?? ''), 'ke' => $mulai . ' s/d ' . $selesai]);
        return null;
    }

    /** Setelah item disimpan / dipindah Tema: samakan nama materi, deskripsi dan event pada pemesanan terhubung. */
    public function segarkan(int $itemId): void
    {
        $item = $this->item($itemId);
        if (! $item) return;
        $db = db_connect();
        $usageIds = array_column($db->table('creative_cetak_titik')->select('usage_id')->where('scope', $this->scope)->where('item_id', $itemId)->get()->getResultArray(), 'usage_id');
        if (! $usageIds) return;
        $tema  = self::infoTema($this->scope, $item);
        $label = trim(($item['jenis_media'] ?: '') . ' ' . ($item['ukuran'] ?: ''));
        $db->table('promo_media_usage')->whereIn('id', array_map('intval', $usageIds))->where('creative_ref', $this->ref($itemId))->update([
            'nama_materi' => mb_substr((string) $item['nama'], 0, 200),
            'event_id' => $tema['event_id'],
            'deskripsi_materi' => 'Dari Creative' . ($tema['nama'] !== '' ? ' · ' . $tema['nama'] : '') . ($label !== '' ? ' · ' . $label : ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Item dihapus: lepaskan semua pemesanan terhubung (yang belum 'done' dihapus; yang sudah 'done' tetap sebagai
     * riwayat Media Promo), hapus tautan dan foto bukti. @return array{dilepas:int,riwayat:int,terpasang:int}
     */
    public function lepasUntukItem(int $itemId): array
    {
        $db = db_connect();
        $links = $db->table('creative_cetak_titik')->where('scope', $this->scope)->where('item_id', $itemId)->get()->getResultArray();
        $hasil = ['dilepas' => 0, 'riwayat' => 0, 'terpasang' => 0];
        if (! $links) return $hasil;

        $db->transBegin();
        try {
            foreach ($links as $l) {
                $u = $db->table('promo_media_usage')->where('id', (int) $l['usage_id'])->get()->getRowArray();
                if ($u && $u['status'] !== 'done' && $u['creative_ref'] === $this->ref($itemId)) {
                    $db->table('promo_media_usage')->where('id', (int) $u['id'])->delete();
                    $hasil['dilepas']++;
                } elseif ($u) {
                    $hasil['riwayat']++;
                }
                if (! empty($l['tanggal_pasang']) || ! empty($l['bukti_pasang_file'])) $hasil['terpasang']++;
                $db->table('creative_cetak_titik')->where('id', (int) $l['id'])->delete();
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal melepas titik cetak: ' . $e->getMessage());
            return $hasil;
        }
        foreach ($links as $l) $this->hapusBerkasLink($l);
        ActivityLog::write('delete', 'creative_cetak', $this->ref($itemId), 'Item dihapus', ['aksi' => 'lepas_titik'] + $hasil);
        return $hasil;
    }

    private function sinkronTurunan(array $item): void
    {
        $t = self::infoTema($this->scope, $item);
        if ($t['key'] !== '') CreativeTurunanOtomatis::sinkron($t['key'], CreativeTurunanOtomatis::SUMBER_CETAK);
    }

    // ───────────────────────────── Bukti pasang ─────────────────────────────

    /** Periksa izin sebelum controller memindahkan berkas; ['l'=>, 'item'=>] atau ['err'=>]. */
    public function siapBukti(int $linkId): array
    {
        $x = $this->temukanLink($linkId);
        if (isset($x['err'])) return $x;
        if ($msg = $this->terkunci($x['item'])) return ['err' => $msg];
        return $x;
    }

    /** Catat bukti pasang: berkas baru (opsional), tanggal pasang, catatan. Tanggal kosong + ada berkas = hari ini. */
    public function catatBukti(int $linkId, ?string $namaBerkas, ?string $asli, ?string $tanggal, ?string $catatan, array $user): array
    {
        $x = $this->siapBukti($linkId);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['l' => $l, 'item' => $item] = $x;

        $tanggal = trim((string) $tanggal);
        if ($tanggal !== '' && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || ! strtotime($tanggal))) return $this->gagal('Tanggal pasang tidak valid.');
        $data = ['updated_at' => date('Y-m-d H:i:s')];
        $data['catatan'] = trim((string) $catatan) !== '' ? mb_substr(trim((string) $catatan), 0, 500) : null;
        if ($tanggal !== '') $data['tanggal_pasang'] = $tanggal;
        elseif ($namaBerkas !== null && empty($l['tanggal_pasang'])) $data['tanggal_pasang'] = date('Y-m-d');
        if ($namaBerkas !== null) {
            $data['bukti_pasang_file'] = $namaBerkas;
            $data['bukti_pasang_asli'] = mb_substr((string) $asli, 0, 255);
        }
        db_connect()->table('creative_cetak_titik')->where('id', $linkId)->update($data);
        if ($namaBerkas !== null && ! empty($l['bukti_pasang_file']) && $l['bukti_pasang_file'] !== $namaBerkas) {
            $p = self::dirBukti($linkId) . $l['bukti_pasang_file'];
            if (is_file($p)) @unlink($p);
        }
        CreativeTemaService::tandaiDikerjakan($this->scope, (int) $item['id']);
        ActivityLog::write('update', 'creative_cetak', $this->ref((int) $item['id']), $item['nama'], ['aksi' => 'bukti_pasang', 'titik' => $this->kodeSpot((int) $l['spot_id']), 'berkas' => $asli, 'tanggal' => $data['tanggal_pasang'] ?? ($l['tanggal_pasang'] ?? null)]);
        return ['ok' => true, 'message' => 'Bukti pasang titik ' . $this->kodeSpot((int) $l['spot_id']) . ' disimpan.'];
    }

    /** Hapus bukti pasang (foto, tanggal, catatan) sehingga titik kembali "belum terpasang". */
    public function hapusBukti(int $linkId, array $user): array
    {
        $x = $this->siapBukti($linkId);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['l' => $l, 'item' => $item] = $x;
        db_connect()->table('creative_cetak_titik')->where('id', $linkId)->update([
            'tanggal_pasang' => null, 'bukti_pasang_file' => null, 'bukti_pasang_asli' => null, 'catatan' => null, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->hapusBerkasLink($l);
        ActivityLog::write('delete_file', 'creative_cetak', $this->ref((int) $item['id']), $item['nama'], ['aksi' => 'hapus_bukti', 'titik' => $this->kodeSpot((int) $l['spot_id'])]);
        return ['ok' => true, 'message' => 'Bukti pasang titik ' . $this->kodeSpot((int) $l['spot_id']) . ' dihapus.'];
    }
}
