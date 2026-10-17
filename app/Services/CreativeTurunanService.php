<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Models\CreativeItemModel;
use App\Models\EventCompletionModel;
use App\Models\EventCreativeItemModel;

/**
 * Turunan pada induk Design / Motion Graphic (Creative Tahap 2a).
 *
 *   belum ──ajukan──> diajukan ──setujui──> disetujui (terkunci, status "selesai")
 *                        │                       │
 *                        └──tolak──> ditolak     └──buka (lead)──> belum
 *
 * Satu kelas melayani dua lingkup: item standalone (creative_items, dikelola di /creative)
 * dan item event (event_creative_items, dikelola di tab Creative event). Hak ubah / setujui
 * diperiksa pemanggil (controller); aturan keadaan dan kunci Tema diperiksa di sini.
 */
class CreativeTurunanService
{
    public const SCOPE_STANDALONE = 'standalone';
    public const SCOPE_EVENT      = 'event';

    public const STATUS = [
        'belum'      => 'Belum',
        'dikerjakan' => 'Dikerjakan',
        'review'     => 'Review',
        'selesai'    => 'Selesai',
    ];

    public const KELOMPOK = ['digital' => 'Digital', 'cetak' => 'Cetak', 'video' => 'Video'];

    public const MIN_ALASAN = 10;

    private string $scope;
    private ?int $eventId;

    public function __construct(string $scope, ?int $eventId = null)
    {
        if (! in_array($scope, [self::SCOPE_STANDALONE, self::SCOPE_EVENT], true)) {
            throw new \InvalidArgumentException('Scope turunan tidak dikenal: ' . $scope);
        }
        $this->scope   = $scope;
        $this->eventId = $eventId;
    }

    public static function standalone(): self { return new self(self::SCOPE_STANDALONE); }
    public static function forEvent(int $eventId): self { return new self(self::SCOPE_EVENT, $eventId); }

    private function isEvent(): bool { return $this->scope === self::SCOPE_EVENT; }

    // ───────────────────────────── Katalog ─────────────────────────────

    /** Induk katalog untuk jenis item: master_design -> design, motion_graphic -> motion_graphic. */
    public static function indukDariTipe(?string $tipe): ?string
    {
        return $tipe === 'master_design' ? 'design' : ($tipe === 'motion_graphic' ? 'motion_graphic' : null);
    }

    /** Uraikan JSON katalog menjadi struktur siap pakai. */
    public static function uraikanJenis(array $row): array
    {
        $baku = json_decode((string) ($row['ukuran_baku'] ?? ''), true);
        $bebas = false; $opsi = [];
        if (is_array($baku)) {
            if (! empty($baku['bebas'])) $bebas = true;
            elseif ($baku === array_values($baku)) {
                foreach ($baku as $o) {
                    if (is_array($o) && trim((string) ($o['label'] ?? '')) !== '') $opsi[] = $o;
                }
            }
        }
        $dur = json_decode((string) ($row['durasi_opsi'] ?? ''), true);
        $dur = is_array($dur) ? array_values(array_filter(array_map('intval', $dur), fn($d) => $d > 0)) : [];

        $row['_bebas'] = $bebas;
        $row['_opsi']  = $opsi;
        $row['_durasi'] = $dur;
        return $row;
    }

    /** Seluruh katalog (untuk halaman kelola) atau hanya yang aktif, dikelompokkan per induk. */
    public static function katalog(bool $hanyaAktif = true): array
    {
        $b = db_connect()->table('creative_turunan_jenis')->orderBy('induk')->orderBy('urutan')->orderBy('id');
        if ($hanyaAktif) $b->where('aktif', 1);
        $out = ['design' => [], 'motion_graphic' => []];
        foreach ($b->get()->getResultArray() as $r) {
            $out[$r['induk']][] = self::uraikanJenis($r);
        }
        return $out;
    }

    /** Jenis cetak aktif + ukuran bakunya, untuk pilihan jenis media / ukuran di formulir item Media Cetak. */
    public static function cetakKatalog(): array
    {
        $out = [];
        foreach (self::katalog(true)['design'] as $j) {
            if ($j['kelompok'] !== 'cetak') continue;
            $out[] = ['nama' => $j['nama'], 'bebas' => $j['_bebas'], 'ukuran' => array_column($j['_opsi'], 'label')];
        }
        return $out;
    }

    /** Format Konten Digital (jenis digital aktif dari katalog) + ukuran bakunya, untuk pilihan format di formulir item digital. */
    public static function digitalKatalog(): array
    {
        $out = [];
        foreach (self::katalog(true)['design'] as $j) {
            if ($j['kelompok'] !== 'digital') continue;
            $out[] = ['nama' => $j['nama'], 'bebas' => $j['_bebas'], 'ukuran' => array_column($j['_opsi'], 'label')];
        }
        return $out;
    }

    // ───────────────────────────── Pembacaan ─────────────────────────────

    /**
     * Turunan beberapa item sekaligus (lengkap dengan berkas dan nama pelaku).
     * @return array<int,array{rows:array,total:int,selesai:int}> keyed by item_id
     */
    public function untukItems(array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if (! $itemIds) return [];
        $db = db_connect();

        $rows = $db->table('creative_turunan t')
            ->select('t.*, ua.name AS diajukan_nama, uk.name AS keputusan_nama')
            ->join('users ua', 'ua.id = t.diajukan_oleh', 'left')
            ->join('users uk', 'uk.id = t.keputusan_oleh', 'left')
            ->where('t.scope', $this->scope)->whereIn('t.item_id', $itemIds)
            ->orderBy('t.kelompok')->orderBy('t.nama')->orderBy('t.id')
            ->get()->getResultArray();

        $files = [];
        if ($rows) {
            $ids = array_column($rows, 'id');
            foreach ($db->table('creative_turunan_file')->whereIn('turunan_id', $ids)->orderBy('id')->get()->getResultArray() as $f) {
                $files[(int) $f['turunan_id']][] = $f;
            }
        }

        $out = [];
        foreach ($itemIds as $id) $out[$id] = ['rows' => [], 'total' => 0, 'selesai' => 0];
        foreach ($rows as $r) {
            $r['files'] = $files[(int) $r['id']] ?? [];
            $iid = (int) $r['item_id'];
            $out[$iid]['rows'][] = $r;
            $out[$iid]['total']++;
            if ($r['approval_status'] === 'disetujui') $out[$iid]['selesai']++;
        }
        return $out;
    }

    // ───────────────────────────── Induk & penguncian ─────────────────────────────

    private function temukanInduk(int $itemId): ?array
    {
        $item = $this->isEvent() ? (new EventCreativeItemModel())->find($itemId) : (new CreativeItemModel())->find($itemId);
        if (! $item) return null;
        if ($this->isEvent() && (int) ($item['event_id'] ?? 0) !== (int) $this->eventId) return null;
        if (! CreativeApprovalService::isDesain($item['tipe'])) return null;
        return $item;
    }

    /** Pesan penolakan bila induk tak boleh diubah (Tema ditutup / modul event diselesaikan); null bila boleh. */
    private function indukTerkunci(array $item): ?string
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

    /** Turunan + induknya; ['err'=>pesan] bila tak ditemukan / tak sesuai lingkup. */
    private function temukan(int $id): array
    {
        $t = db_connect()->table('creative_turunan')->where('id', $id)->get()->getRowArray();
        if (! $t || $t['scope'] !== $this->scope) return ['err' => 'Turunan tidak ditemukan.'];
        $item = $this->temukanInduk((int) $t['item_id']);
        if (! $item) return ['err' => 'Item induk turunan tidak ditemukan.'];
        return ['t' => $t, 'item' => $item];
    }

    public static function terkunci(array $t): bool
    {
        return ($t['approval_status'] ?? '') === 'disetujui';
    }

    // ───────────────────────────── Pembuatan ─────────────────────────────

    private static function kunci(string $scope, int $itemId, ?int $jenisId, string $nama, string $ukuran, ?int $durasi): string
    {
        $isi = $jenisId ? (string) $jenisId : 'k';
        $h   = md5(mb_strtolower(($jenisId ? '' : trim($nama) . '|') . trim($ukuran)));
        return $scope . '|' . $itemId . '|' . $isi . '|' . $h . '|' . (int) $durasi;
    }

    /** Pisahkan teks ukuran bebas (koma, titik koma, atau baris baru) menjadi daftar. */
    private static function pecahUkuran(string $s): array
    {
        $out = [];
        foreach (preg_split('/[;\n\r,]+/', $s) as $p) {
            $p = trim(preg_replace('/\s+/', ' ', $p));
            if ($p !== '') $out[mb_substr($p, 0, 160)] = true;
        }
        return array_keys($out);
    }

    /**
     * Buat turunan dari pilihan daftar centang.
     *
     * $post: pilih[] = "jenisId:indeksOpsi"; lain[jenisId] = ukuran ketikan (boleh banyak, dipisah koma);
     *        dur[jenisId][] = detik (jenis dengan durasi); k_nama / k_kelompok / k_ukuran / k_durasi = kustom.
     */
    public function buat(int $itemId, array $post, array $user): array
    {
        $item = $this->temukanInduk($itemId);
        if (! $item) return $this->gagal('Item tidak ditemukan atau bukan Design / Motion Graphic.');
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);

        $induk = self::indukDariTipe($item['tipe']);
        $jenisAktif = [];
        foreach (self::katalog(true)[$induk] ?? [] as $j) $jenisAktif[(int) $j['id']] = $j;

        $pilih = [];   // jenisId => [ukuran,...]
        foreach ((array) ($post['pilih'] ?? []) as $v) {
            if (! preg_match('/^(\d+):(\d+)$/', (string) $v, $m)) continue;
            $jid = (int) $m[1]; $idx = (int) $m[2];
            $j = $jenisAktif[$jid] ?? null;
            if (! $j || ! isset($j['_opsi'][$idx])) continue;
            $pilih[$jid][] = trim((string) $j['_opsi'][$idx]['label']);
        }
        foreach ((array) ($post['lain'] ?? []) as $jid => $txt) {
            $jid = (int) $jid;
            if (! isset($jenisAktif[$jid]) || ! is_string($txt)) continue;
            foreach (self::pecahUkuran($txt) as $u) $pilih[$jid][] = $u;
        }

        $rencana = [];   // baris yang akan dibuat
        $kurangDurasi = [];
        foreach ($pilih as $jid => $ukurans) {
            $j = $jenisAktif[$jid];
            $durs = [null];
            if ($j['_durasi']) {
                $durs = [];
                foreach ((array) ($post['dur'][$jid] ?? []) as $d) {
                    $d = (int) $d;
                    if (in_array($d, $j['_durasi'], true)) $durs[$d] = $d;
                }
                $durs = array_values($durs);
                if (! $durs) { $kurangDurasi[] = $j['nama']; continue; }
            }
            foreach (array_unique($ukurans) as $u) {
                foreach ($durs as $d) {
                    $rencana[] = ['jenis_id' => $jid, 'nama' => $j['nama'], 'kelompok' => $j['kelompok'], 'ukuran' => $u, 'durasi' => $d];
                }
            }
        }

        // Kustom (di luar katalog)
        $kNama = trim((string) ($post['k_nama'] ?? ''));
        if ($kNama !== '') {
            $kel = (string) ($post['k_kelompok'] ?? 'digital');
            if (! isset(self::KELOMPOK[$kel])) $kel = 'digital';
            $kd = (int) ($post['k_durasi'] ?? 0);
            $uk = self::pecahUkuran((string) ($post['k_ukuran'] ?? '')) ?: [''];
            foreach ($uk as $u) {
                $rencana[] = ['jenis_id' => null, 'nama' => mb_substr($kNama, 0, 160), 'kelompok' => $kel, 'ukuran' => $u, 'durasi' => $kd > 0 ? $kd : null];
            }
        }

        if (! $rencana) {
            return $this->gagal($kurangDurasi
                ? 'Pilih minimal satu durasi untuk: ' . implode(', ', $kurangDurasi) . '.'
                : 'Belum ada turunan yang dipilih. Centang jenis / ukuran, atau isi turunan kustom.');
        }

        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $baru = 0; $ada = 0;
        foreach ($rencana as $r) {
            $kunci = self::kunci($this->scope, $itemId, $r['jenis_id'], $r['nama'], $r['ukuran'], $r['durasi']);
            if ($db->table('creative_turunan')->where('kunci', $kunci)->countAllResults() > 0) { $ada++; continue; }
            $db->table('creative_turunan')->insert([
                'scope' => $this->scope, 'item_id' => $itemId, 'jenis_id' => $r['jenis_id'],
                'nama' => $r['nama'], 'kelompok' => $r['kelompok'], 'ukuran' => $r['ukuran'], 'durasi_detik' => $r['durasi'],
                'status' => 'belum', 'approval_status' => 'belum', 'sumber' => 'manual', 'kunci' => $kunci,
                'dibuat_oleh' => (int) $user['id'], 'created_at' => $now, 'updated_at' => $now,
            ]);
            $baru++;
        }

        if ($baru > 0) {
            CreativeTemaService::tandaiDikerjakan($this->scope, $itemId);
            ActivityLog::write('create', 'creative_turunan', $this->scope . ':' . $itemId, $item['nama'], ['dibuat' => $baru, 'sudah_ada' => $ada]);
        }
        $msg = $baru . ' turunan dibuat' . ($ada ? ', ' . $ada . ' sudah ada (dilewati)' : '') . '.';
        if ($kurangDurasi) $msg .= ' Tanpa durasi dilewati: ' . implode(', ', $kurangDurasi) . '.';
        return $baru > 0 ? ['ok' => true, 'message' => $msg] : $this->gagal($msg);
    }

    // ───────────────────────────── Ubah / hapus ─────────────────────────────

    public function ubah(int $id, array $post, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if (self::terkunci($t)) return $this->gagal('Turunan sudah disetujui dan terkunci. Lead dapat membukanya kembali bila perlu diubah.');

        $data = [];
        if (array_key_exists('pic', $post))     $data['pic'] = trim((string) $post['pic']) !== '' ? mb_substr(trim((string) $post['pic']), 0, 150) : null;
        if (array_key_exists('catatan', $post)) $data['catatan'] = trim((string) $post['catatan']) !== '' ? trim((string) $post['catatan']) : null;
        if (array_key_exists('tenggat', $post)) {
            $tg = (string) $post['tenggat'];
            $data['tenggat'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tg) ? $tg : null;
        }
        if (array_key_exists('status', $post) && $t['approval_status'] !== 'diajukan') {
            $s = (string) $post['status'];
            if (! in_array($s, ['belum', 'dikerjakan', 'review'], true)) return $this->gagal('Status tidak valid.');
            $data['status'] = $s;
        }
        if (! $data) return $this->gagal('Tidak ada perubahan.');

        $data['updated_at'] = date('Y-m-d H:i:s');
        db_connect()->table('creative_turunan')->where('id', $id)->update($data);
        if (isset($data['status']) && $data['status'] !== 'belum') CreativeTemaService::tandaiDikerjakan($this->scope, (int) $t['item_id']);
        unset($data['updated_at']);
        ActivityLog::write('update', 'creative_turunan', (string) $id, $this->label($t), $data);
        return ['ok' => true, 'message' => 'Turunan diperbarui.'];
    }

    public function hapus(int $id, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if (self::terkunci($t)) return $this->gagal('Turunan sudah disetujui dan tidak bisa dihapus. Lead perlu membukanya kembali dulu.');
        if (($t['sumber'] ?? '') === 'otomatis' && empty($t['penanda'])) {
            $src = strpos((string) ($t['sumber_ref'] ?? ''), 'digital') === 0 ? 'Konten Digital' : 'Media Cetak';
            return $this->gagal('Kebutuhan ini berasal dari item ' . $src . ' yang masih memakainya. Ubah atau hapus item ' . $src . '-nya; kebutuhan akan ikut berubah.');
        }

        $this->hapusBaris([$id]);
        ActivityLog::write('delete', 'creative_turunan', (string) $id, $this->label($t), []);
        return ['ok' => true, 'message' => 'Turunan dihapus.'];
    }

    /** Hapus baris + berkas fisik. */
    private function hapusBaris(array $ids): void
    {
        $db = db_connect();
        foreach ($ids as $tid) {
            $dir = FCPATH . 'uploads/creative-turunan/' . (int) $tid . '/';
            foreach ($db->table('creative_turunan_file')->where('turunan_id', $tid)->get()->getResultArray() as $f) {
                $p = $dir . $f['nama_berkas'];
                if (is_file($p)) @unlink($p);
            }
            if (is_dir($dir)) @rmdir($dir);
            $db->table('creative_turunan_file')->where('turunan_id', $tid)->delete();
            $db->table('creative_turunan')->where('id', $tid)->delete();
        }
    }

    /** Hapus turunan (baris + berkas fisik) menurut id; dipakai sinkron turunan otomatis. */
    public static function hapusTurunanIds(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids) (new self(self::SCOPE_STANDALONE))->hapusBaris($ids);
    }

    /** Jumlah turunan yang sudah disetujui milik sebuah induk (menghalangi penghapusan induk). */
    public static function adaDisetujui(string $scope, int $itemId): int
    {
        return db_connect()->table('creative_turunan')->where('scope', $scope)->where('item_id', $itemId)
            ->where('approval_status', 'disetujui')->countAllResults();
    }

    /** Hapus semua turunan sebuah induk (dipanggil saat induk dihapus). */
    public static function hapusUntukInduk(string $scope, array $itemIds): void
    {
        $itemIds = array_values(array_filter(array_map('intval', $itemIds)));
        if (! $itemIds) return;
        $ids = array_column(db_connect()->table('creative_turunan')->select('id')->where('scope', $scope)->whereIn('item_id', $itemIds)->get()->getResultArray(), 'id');
        if ($ids) (new self($scope))->hapusBaris($ids);
    }

    // ───────────────────────────── Berkas ─────────────────────────────

    /** Periksa izin sebelum controller memindahkan berkas; kembalikan [turunan, item] atau pesan. */
    public function siapUnggah(int $id): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return ['err' => $x['err']];
        if ($msg = $this->indukTerkunci($x['item'])) return ['err' => $msg];
        if (self::terkunci($x['t'])) return ['err' => 'Turunan sudah disetujui dan terkunci. Lead dapat membukanya kembali.'];
        return $x;
    }

    public function catatBerkas(int $id, string $nama, string $asli, string $tipe, int $ukuran, array $user): void
    {
        db_connect()->table('creative_turunan_file')->insert([
            'turunan_id' => $id, 'nama_berkas' => $nama, 'asli' => mb_substr($asli, 0, 255), 'tipe' => mb_substr($tipe, 0, 100),
            'ukuran' => $ukuran, 'dibuat_oleh' => (int) $user['id'], 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $x = $this->temukan($id);
        if (isset($x['t'])) {
            if ($x['t']['status'] === 'belum') {
                db_connect()->table('creative_turunan')->where('id', $id)->update(['status' => 'dikerjakan', 'updated_at' => date('Y-m-d H:i:s')]);
            }
            CreativeTemaService::tandaiDikerjakan($this->scope, (int) $x['t']['item_id']);
            ActivityLog::write('upload', 'creative_turunan', (string) $id, $this->label($x['t']), ['berkas' => $asli]);
        }
    }

    public function hapusBerkas(int $id, int $fileId, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        if ($msg = $this->indukTerkunci($x['item'])) return $this->gagal($msg);
        if (self::terkunci($x['t'])) return $this->gagal('Turunan sudah disetujui dan terkunci. Lead dapat membukanya kembali.');

        $db = db_connect();
        $f = $db->table('creative_turunan_file')->where('id', $fileId)->where('turunan_id', $id)->get()->getRowArray();
        if (! $f) return $this->gagal('Berkas tidak ditemukan.');
        $p = FCPATH . 'uploads/creative-turunan/' . $id . '/' . $f['nama_berkas'];
        $db->table('creative_turunan_file')->where('id', $fileId)->delete();
        if (is_file($p)) @unlink($p);
        ActivityLog::write('delete_file', 'creative_turunan', (string) $id, $this->label($x['t']), ['berkas' => $f['asli']]);
        return ['ok' => true, 'message' => 'Berkas dihapus.'];
    }

    // ───────────────────────────── Persetujuan ─────────────────────────────

    public function ajukan(int $id, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if (! in_array($t['approval_status'], ['belum', 'ditolak'], true)) {
            return $this->gagal($t['approval_status'] === 'diajukan' ? 'Turunan ini sudah menunggu keputusan lead.' : 'Turunan ini sudah disetujui.');
        }
        $db = db_connect();
        if ($db->table('creative_turunan_file')->where('turunan_id', $id)->countAllResults() === 0) {
            return $this->gagal('Unggah minimal satu berkas hasil sebelum mengajukan persetujuan.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('creative_turunan')->where('id', $id)->update([
            'approval_status' => 'diajukan', 'status' => 'review', 'diajukan_oleh' => (int) $user['id'], 'diajukan_pada' => $now,
            'keputusan_oleh' => null, 'keputusan_pada' => null, 'alasan' => null, 'updated_at' => $now,
        ]);
        CreativeTemaService::tandaiDikerjakan($this->scope, (int) $t['item_id']);
        ActivityLog::write('submit', 'creative_turunan', (string) $id, $this->label($t), ['versi' => (int) $t['versi']]);
        return ['ok' => true, 'message' => 'Turunan diajukan ke lead untuk disetujui.'];
    }

    public function setujui(int $id, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if ($t['approval_status'] !== 'diajukan') return $this->gagal('Hanya turunan yang sedang diajukan yang bisa disetujui.');

        $now = date('Y-m-d H:i:s');
        db_connect()->table('creative_turunan')->where('id', $id)->update([
            'approval_status' => 'disetujui', 'status' => 'selesai', 'keputusan_oleh' => (int) $user['id'], 'keputusan_pada' => $now,
            'alasan' => null, 'updated_at' => $now,
        ]);
        ActivityLog::write('approve', 'creative_turunan', (string) $id, $this->label($t), ['versi' => (int) $t['versi']]);
        return ['ok' => true, 'message' => 'Turunan disetujui dan dikunci.'];
    }

    public function tolak(int $id, string $alasan, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if ($t['approval_status'] !== 'diajukan') return $this->gagal('Hanya turunan yang sedang diajukan yang bisa ditolak.');
        $alasan = trim($alasan);
        if (mb_strlen($alasan) < self::MIN_ALASAN) return $this->gagal('Alasan penolakan wajib diisi (minimal ' . self::MIN_ALASAN . ' karakter).');

        $now = date('Y-m-d H:i:s');
        db_connect()->table('creative_turunan')->where('id', $id)->update([
            'approval_status' => 'ditolak', 'status' => 'dikerjakan', 'versi' => (int) $t['versi'] + 1,
            'keputusan_oleh' => (int) $user['id'], 'keputusan_pada' => $now, 'alasan' => $alasan, 'updated_at' => $now,
        ]);
        ActivityLog::write('reject', 'creative_turunan', (string) $id, $this->label($t), ['alasan' => $alasan]);
        return ['ok' => true, 'message' => 'Turunan ditolak; alasan tercatat dan dikirim kembali ke pengerjanya.'];
    }

    public function buka(int $id, string $catatan, array $user): array
    {
        $x = $this->temukan($id);
        if (isset($x['err'])) return $this->gagal($x['err']);
        ['t' => $t, 'item' => $item] = $x;
        if ($msg = $this->indukTerkunci($item)) return $this->gagal($msg);
        if ($t['approval_status'] !== 'disetujui') return $this->gagal('Turunan ini tidak sedang terkunci.');

        $now = date('Y-m-d H:i:s');
        $catatan = trim($catatan);
        db_connect()->table('creative_turunan')->where('id', $id)->update([
            'approval_status' => 'belum', 'status' => 'dikerjakan', 'versi' => (int) $t['versi'] + 1,
            'keputusan_oleh' => (int) $user['id'], 'keputusan_pada' => $now,
            'alasan' => $catatan !== '' ? 'Dibuka kembali: ' . $catatan : 'Dibuka kembali oleh lead.', 'updated_at' => $now,
        ]);
        ActivityLog::write('reopen', 'creative_turunan', (string) $id, $this->label($t), ['catatan' => $catatan]);
        return ['ok' => true, 'message' => 'Turunan dibuka kembali dan dapat diubah.'];
    }

    // ───────────────────────────── Bantu ─────────────────────────────

    private function label(array $t): string
    {
        return trim($t['nama'] . ($t['ukuran'] !== '' ? ' ' . $t['ukuran'] : '') . ($t['durasi_detik'] ? ' ' . $t['durasi_detik'] . ' dtk' : ''));
    }

    private function gagal(string $msg): array
    {
        return ['ok' => false, 'message' => $msg];
    }
}
