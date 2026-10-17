<?php

namespace App\Services;

use App\Libraries\ActivityLog;

/**
 * Paket item awal Creative (Tahap 2a).
 *
 * Saat Tema Bulanan atau Event dibuat, sistem menyiapkan item "Rencana" dari tabel
 * `creative_paket_awal` (dapat diatur lead/admin). Item rencana bertanda `dari_paket = 1`
 * dan kembali 0 begitu dikerjakan / diubah. Tema atau event yang sudah ada TIDAK otomatis
 * mendapat paket; tersedia aksi `tambahkan()` yang idempoten (tidak membuat item bernama sama dua kali).
 */
class CreativePaketService
{
    public const TIPE_BOLEH = ['master_design', 'motion_graphic', 'digital', 'cetak'];

    /** Baris paket aktif untuk satu konteks. */
    public static function baris(string $konteks, bool $hanyaAktif = true): array
    {
        $b = db_connect()->table('creative_paket_awal')->where('konteks', $konteks)->orderBy('urutan')->orderBy('id');
        if ($hanyaAktif) $b->where('aktif', 1);
        return $b->get()->getResultArray();
    }

    public static function namaDariPola(string $pola, string $tema): string
    {
        return mb_substr(str_replace('{tema}', $tema, $pola), 0, 255);
    }

    /**
     * Buat item paket untuk Tema Bulanan ($jenis 'bulanan') atau Event ('event').
     * @return array{dibuat:int,dilewati:int}
     */
    public function tambahkan(string $jenis, int $id, string $namaTema, int $userId): array
    {
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $isEv = $jenis === 'event';
        $tabel = $isEv ? 'event_creative_items' : 'creative_items';

        // item yang sudah ada di Tema ini (jenis + nama, tak peduli huruf besar/kecil)
        $b = $db->table($tabel)->select('tipe,nama');
        if ($isEv) $b->where('event_id', $id); else $b->where('tema_id', $id);
        $ada = [];
        foreach ($b->get()->getResultArray() as $r) $ada[$r['tipe'] . '|' . mb_strtolower(trim($r['nama']))] = true;

        $dibuat = 0; $dilewati = 0;
        foreach (self::baris($isEv ? 'event' : 'bulanan') as $p) {
            if (! in_array($p['tipe'], self::TIPE_BOLEH, true)) continue;
            $nama = self::namaDariPola($p['nama_pola'], $namaTema);
            $k = $p['tipe'] . '|' . mb_strtolower(trim($nama));
            if (isset($ada[$k])) { $dilewati++; continue; }

            $row = [
                'tipe' => $p['tipe'], 'nama' => $nama,
                'platform' => $p['tipe'] === 'digital' ? ($p['platform'] ?: 'ig') : null,
                'jenis_media' => in_array($p['tipe'], ['cetak', 'digital'], true) ? ($p['jenis_media'] ?: null) : null,
                'ukuran' => in_array($p['tipe'], ['cetak', 'digital'], true) ? ($p['ukuran'] ?: null) : null,
                'budget' => 0, 'status' => 'draft', 'urutan' => (int) $p['urutan'], 'dari_paket' => 1,
                'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now,
            ];
            if ($isEv) $row['event_id'] = $id; else $row['tema_id'] = $id;
            $db->table($tabel)->insert($row);
            $ada[$k] = true;
            $dibuat++;
        }

        if ($dibuat > 0) {
            ActivityLog::write('create', 'creative_paket', $jenis . ':' . $id, $namaTema, ['aksi' => 'paket_awal', 'dibuat' => $dibuat, 'dilewati' => $dilewati]);
        }
        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /** Item rencana (dari paket, belum dikerjakan) milik Tema/event. */
    public static function itemRencana(string $jenis, int $id): array
    {
        $isEv = $jenis === 'event';
        $b = db_connect()->table($isEv ? 'event_creative_items' : 'creative_items')->select('id,nama')->where('dari_paket', 1);
        if ($isEv) $b->where('event_id', $id); else $b->where('tema_id', $id);
        return $b->get()->getResultArray();
    }

    /** Hapus item rencana yang belum dikerjakan; item yang sudah punya isi (berkas / realisasi / insight / turunan) dipertahankan. */
    public function hapusRencana(string $jenis, int $id, string $namaTema): int
    {
        $db   = db_connect();
        $isEv = $jenis === 'event';
        $scope = $isEv ? CreativeTurunanService::SCOPE_EVENT : CreativeTurunanService::SCOPE_STANDALONE;
        $n = 0;
        foreach (self::itemRencana($jenis, $id) as $r) {
            $iid = (int) $r['id'];
            $pakai = $db->table('creative_turunan')->where('scope', $scope)->where('item_id', $iid)->countAllResults();
            $pakai += $db->table($isEv ? 'event_creative_files' : 'creative_files')->where('creative_item_id', $iid)->countAllResults();
            $pakai += $db->table($isEv ? 'event_creative_realisasi' : 'creative_realisasi')->where('creative_item_id', $iid)->countAllResults();
            $pakai += $db->table($isEv ? 'event_creative_insight' : 'creative_insight')->where('creative_item_id', $iid)->countAllResults();
            if ($pakai > 0) {
                $db->table($isEv ? 'event_creative_items' : 'creative_items')->where('id', $iid)->update(['dari_paket' => 0]);
                continue;
            }
            $db->table($isEv ? 'event_creative_items' : 'creative_items')->where('id', $iid)->delete();
            $n++;
        }
        if ($n > 0) ActivityLog::write('delete', 'creative_paket', $jenis . ':' . $id, $namaTema, ['aksi' => 'hapus_rencana', 'dihapus' => $n]);
        return $n;
    }

    // ───────────────────────────── Kelola baris paket ─────────────────────────────

    public function simpan(?int $id, array $post, int $userId): array
    {
        $konteks = (string) ($post['konteks'] ?? '');
        $tipe    = (string) ($post['tipe'] ?? '');
        $pola    = trim((string) ($post['nama_pola'] ?? ''));
        if (! in_array($konteks, ['bulanan', 'event'], true)) return ['ok' => false, 'message' => 'Konteks paket tidak valid.'];
        if (! in_array($tipe, self::TIPE_BOLEH, true)) return ['ok' => false, 'message' => 'Jenis item paket tidak valid.'];
        if ($pola === '') return ['ok' => false, 'message' => 'Pola nama wajib diisi.'];
        $platform = $tipe === 'digital' ? (in_array(($post['platform'] ?? 'ig'), ['ig', 'tiktok', 'keduanya'], true) ? $post['platform'] : 'ig') : null;

        $db = db_connect(); $now = date('Y-m-d H:i:s');
        $baru = ! $id;
        $jm = in_array($tipe, ['cetak', 'digital'], true) ? trim((string) ($post['jenis_media'] ?? '')) : '';
        $uk = in_array($tipe, ['cetak', 'digital'], true) ? trim((string) ($post['ukuran'] ?? '')) : '';
        $data = ['konteks' => $konteks, 'tipe' => $tipe, 'nama_pola' => mb_substr($pola, 0, 200), 'platform' => $platform,
                 'jenis_media' => $jm !== '' ? mb_substr($jm, 0, 60) : null, 'ukuran' => $uk !== '' ? mb_substr($uk, 0, 80) : null, 'updated_at' => $now];
        if ($id) {
            if (! $db->table('creative_paket_awal')->where('id', $id)->countAllResults()) return ['ok' => false, 'message' => 'Baris paket tidak ditemukan.'];
            $db->table('creative_paket_awal')->where('id', $id)->update($data);
        } else {
            $max = (int) ($db->table('creative_paket_awal')->selectMax('urutan', 'm')->where('konteks', $konteks)->get()->getRow()->m ?? 0);
            $db->table('creative_paket_awal')->insert($data + ['urutan' => $max + 1, 'aktif' => 1, 'created_at' => $now]);
            $id = (int) $db->insertID();
        }
        ActivityLog::write($baru ? 'create' : 'update', 'creative_paket', (string) $id, $pola, ['aksi' => 'paket_baris', 'konteks' => $konteks]);
        return ['ok' => true, 'message' => 'Baris paket disimpan.'];
    }

    public function toggle(int $id): array
    {
        $db = db_connect();
        $r = $db->table('creative_paket_awal')->where('id', $id)->get()->getRowArray();
        if (! $r) return ['ok' => false, 'message' => 'Baris paket tidak ditemukan.'];
        $db->table('creative_paket_awal')->where('id', $id)->update(['aktif' => $r['aktif'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'creative_paket', (string) $id, $r['nama_pola'], ['aksi' => $r['aktif'] ? 'nonaktif' : 'aktif']);
        return ['ok' => true, 'message' => $r['aktif'] ? 'Baris paket dinonaktifkan.' : 'Baris paket diaktifkan.'];
    }

    public function hapus(int $id): array
    {
        $db = db_connect();
        $r = $db->table('creative_paket_awal')->where('id', $id)->get()->getRowArray();
        if (! $r) return ['ok' => false, 'message' => 'Baris paket tidak ditemukan.'];
        $db->table('creative_paket_awal')->where('id', $id)->delete();
        ActivityLog::write('delete', 'creative_paket', (string) $id, $r['nama_pola'], ['aksi' => 'paket_baris']);
        return ['ok' => true, 'message' => 'Baris paket dihapus. Item yang sudah dibuat dari paket ini tidak berubah.'];
    }

    public function geser(int $id, string $arah): array
    {
        $db = db_connect();
        $r = $db->table('creative_paket_awal')->where('id', $id)->get()->getRowArray();
        if (! $r) return ['ok' => false, 'message' => 'Baris paket tidak ditemukan.'];
        $list = self::baris($r['konteks'], false);
        $ids = array_column($list, 'id');
        $i = array_search((string) $id, array_map('strval', $ids), true);
        $j = $arah === 'naik' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= count($ids)) return ['ok' => true, 'message' => ''];
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $n => $pid) $db->table('creative_paket_awal')->where('id', $pid)->update(['urutan' => $n + 1]);
        return ['ok' => true, 'message' => ''];
    }
}
