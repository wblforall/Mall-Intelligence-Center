<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Models\EventCompletionModel;

/**
 * Konten Digital Creative (Tahap 3): STATUS KONTEN sederhana dan "Atur status" massal untuk item lama.
 *
 *   Rencana -> Produksi -> Tayang -> Dilaporkan   (kolom `status_konten`; kolom `status` lama = alur persetujuan, tidak disentuh)
 *
 * Aturan:
 *  - NULL = "belum diatur" (item lama). Tidak pernah diisi otomatis; tampilan turunan hanya untuk NULL + ada insight -> "Dilaporkan".
 *  - Dari status yang sudah diatur: maju / mundur SATU langkah. Dari NULL: boleh langsung ke status mana pun yang prasyaratnya terpenuhi.
 *  - 'Tayang' butuh tanggal_take. 'Dilaporkan' butuh >= 1 insight, kecuali dengan alasan (>= 10 karakter) sebagai pengecualian tercatat.
 *  - Item "Rencana" dari paket (dari_paket = 1) belum boleh diberi status: isi / ubah dulu lewat formulir.
 *  - Tema ditutup (atau modul event diselesaikan) mengunci status.
 *  - Semua perubahan tercatat di Activity Log (modul `creative_digital`).
 * Hak ubah diperiksa pemanggil (controller): `creative_main` untuk standalone, `creative` untuk event.
 */
class CreativeKontenService
{
    public const SCOPE_STANDALONE = 'standalone';
    public const SCOPE_EVENT      = 'event';

    public const STATUS = ['rencana' => 'Rencana', 'produksi' => 'Produksi', 'tayang' => 'Tayang', 'dilaporkan' => 'Dilaporkan'];
    private const URUT  = ['rencana', 'produksi', 'tayang', 'dilaporkan'];

    private static function tabel(string $scope): string { return $scope === self::SCOPE_EVENT ? 'event_creative_items' : 'creative_items'; }
    private static function tabelInsight(string $scope): string { return $scope === self::SCOPE_EVENT ? 'event_creative_insight' : 'creative_insight'; }

    /** Jumlah entri insight per item: [item_id => n]. */
    public static function jumlahInsight(string $scope, array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if (! $itemIds) return [];
        $out = [];
        $rows = db_connect()->table(self::tabelInsight($scope))->select('creative_item_id, COUNT(*) n')->whereIn('creative_item_id', $itemIds)->groupBy('creative_item_id')->get()->getResultArray();
        foreach ($rows as $r) $out[(int) $r['creative_item_id']] = (int) $r['n'];
        return $out;
    }

    /**
     * Status yang TAMPIL untuk sebuah item digital (tidak menulis apa pun).
     * @return array{key:string,label:string,diatur:bool,turunan:bool} key: rencana|produksi|tayang|dilaporkan|belum
     */
    public static function tampil(array $item, int $nInsight): array
    {
        $sk = $item['status_konten'] ?? null;
        if ($sk && isset(self::STATUS[$sk])) return ['key' => $sk, 'label' => self::STATUS[$sk], 'diatur' => true, 'turunan' => false];
        if ($nInsight > 0) return ['key' => 'dilaporkan', 'label' => 'Dilaporkan', 'diatur' => false, 'turunan' => true];
        return ['key' => 'belum', 'label' => 'Belum diatur', 'diatur' => false, 'turunan' => false];
    }

    /** Hitungan cepat item digital yang statusnya belum diatur (tanpa Rencana dari paket dan tanpa Tema ditutup), untuk tombol "Atur status". */
    public static function jumlahBelum(array $scopes): int
    {
        $db = db_connect(); $n = 0;
        if (in_array(self::SCOPE_STANDALONE, $scopes, true)) {
            $n += (int) $db->query("SELECT COUNT(*) c FROM creative_items i LEFT JOIN creative_tema t ON t.id = i.tema_id LEFT JOIN creative_tema_status s ON s.event_id = i.event_id
                WHERE i.tipe = 'digital' AND i.status_konten IS NULL AND i.dari_paket = 0 AND COALESCE(t.ditutup, 0) = 0 AND COALESCE(s.ditutup, 0) = 0")->getRow()->c;
        }
        if (in_array(self::SCOPE_EVENT, $scopes, true)) {
            $n += (int) $db->query("SELECT COUNT(*) c FROM event_creative_items i LEFT JOIN creative_tema_status s ON s.event_id = i.event_id
                WHERE i.tipe = 'digital' AND i.status_konten IS NULL AND i.dari_paket = 0 AND COALESCE(s.ditutup, 0) = 0")->getRow()->c;
        }
        return $n;
    }

    /** Usulan status untuk item lama (NULL): ada insight -> Dilaporkan; tanggal take sudah lewat -> Tayang; selain itu Rencana. */
    public static function usulan(array $item, int $nInsight): array
    {
        if ($nInsight > 0) return ['status' => 'dilaporkan', 'alasan' => $nInsight . ' entri insight'];
        $tk = (string) ($item['tanggal_take'] ?? '');
        if ($tk !== '' && $tk < date('Y-m-d')) return ['status' => 'tayang', 'alasan' => 'tanggal take sudah lewat'];
        return ['status' => 'rencana', 'alasan' => $tk !== '' ? 'tanggal take belum tiba' : 'belum ada tanggal take / insight'];
    }

    // ───────────────────────────── Ubah status satu item ─────────────────────────────

    private function item(string $scope, int $id): ?array
    {
        $r = db_connect()->table(self::tabel($scope))->where('id', $id)->get()->getRowArray();
        return ($r && $r['tipe'] === 'digital') ? $r : null;
    }

    private function terkunci(string $scope, array $item): ?string
    {
        if ($scope === self::SCOPE_EVENT) {
            $eid = (int) $item['event_id'];
            if (CreativeTemaService::eventDitutup($eid)) return CreativeTemaService::PESAN_TEMA_TUTUP;
            if ((new EventCompletionModel())->getByEvent($eid)['creative'] ?? null) return 'Modul Creative event ini sudah ditandai selesai sehingga terkunci.';
            return null;
        }
        return CreativeTemaService::itemTerkunci($item) ? CreativeTemaService::PESAN_TEMA_TUTUP : null;
    }

    /** @return array{ok:bool,message:string,butuhAlasan?:bool} */
    public function ubah(string $scope, int $id, string $target, string $alasan, array $user): array
    {
        $item = $this->item($scope, $id);
        if (! $item) return $this->gagal('Konten digital tidak ditemukan.');
        if (! isset(self::STATUS[$target])) return $this->gagal('Status tidak dikenal.');
        if ($msg = $this->terkunci($scope, $item)) return $this->gagal($msg);
        if (! empty($item['dari_paket'])) return $this->gagal('Item ini masih berstatus Rencana dari paket awal. Isi dulu lewat Ubah item (PIC wajib), baru status konten bisa diatur.');

        $dari = $item['status_konten'] ?: null;
        if ($dari === $target) return $this->gagal('Status sudah ' . self::STATUS[$target] . '.');
        if ($dari !== null && abs(array_search($target, self::URUT, true) - array_search($dari, self::URUT, true)) !== 1) {
            return $this->gagal('Status hanya bisa maju atau mundur satu langkah dari ' . self::STATUS[$dari] . '.');
        }
        $alasan = trim($alasan);
        $pengecualian = false;
        if ($msg = $this->prasyarat($scope, $item, $target, $alasan, $pengecualian)) {
            return $this->gagal($msg['message']) + (isset($msg['butuhAlasan']) ? ['butuhAlasan' => true] : []);
        }

        db_connect()->table(self::tabel($scope))->where('id', $id)->update(['status_konten' => $target, 'updated_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'creative_digital', $scope . ':' . $id, $item['nama'], [
            'aksi' => 'status_konten', 'dari' => $dari ?? 'belum diatur', 'ke' => $target,
        ] + ($pengecualian ? ['pengecualian_tanpa_insight' => $alasan] : []));
        return ['ok' => true, 'message' => 'Status “' . $item['nama'] . '” menjadi ' . self::STATUS[$target] . '.'];
    }

    /** Prasyarat status; null bila terpenuhi. $pengecualian diisi true bila Dilaporkan lolos lewat alasan. */
    private function prasyarat(string $scope, array $item, string $target, string $alasan, bool &$pengecualian, bool $izinkanAlasan = true): ?array
    {
        if ($target === 'tayang' && empty($item['tanggal_take'])) {
            return ['message' => 'Status Tayang butuh Tanggal Take. Isi dulu lewat Ubah item.'];
        }
        if ($target === 'dilaporkan') {
            $n = self::jumlahInsight($scope, [(int) $item['id']])[(int) $item['id']] ?? 0;
            if ($n < 1) {
                if (! $izinkanAlasan) return ['message' => 'Status Dilaporkan butuh minimal satu entri insight.'];
                if (mb_strlen($alasan) < 10) return ['message' => 'Belum ada insight. Catat insight di tab Insight, atau isi alasan (min. 10 karakter) bila tetap ingin melaporkan tanpa insight.', 'butuhAlasan' => true];
                $pengecualian = true;
            }
        }
        return null;
    }

    private function gagal(string $m): array { return ['ok' => false, 'message' => $m]; }

    // ───────────────────────────── Atur status massal (item lama) ─────────────────────────────

    /**
     * Item digital berstatus_konten NULL (bukan Rencana dari paket, Tema belum ditutup) beserta usulannya.
     * @param array $scopes ['standalone','event'] yang boleh diubah pemanggil
     * @return array<int,array>
     */
    public function kandidat(array $scopes): array
    {
        $db = db_connect();
        $out = [];
        foreach ($scopes as $scope) {
            $isEv = $scope === self::SCOPE_EVENT;
            $b = $db->table(self::tabel($scope))->where('tipe', 'digital')->where('status_konten', null)->where('dari_paket', 0)->orderBy('id');
            $rows = $b->get()->getResultArray();
            if (! $rows) continue;
            $ins = self::jumlahInsight($scope, array_column($rows, 'id'));
            foreach ($rows as $r) {
                if ($this->terkunci($scope, $r)) continue;
                $info = CreativeTitikService::infoTema($scope, $r);
                $u = self::usulan($r, $ins[(int) $r['id']] ?? 0);
                $out[] = [
                    'key' => ($isEv ? 'e' : 's') . ':' . $r['id'], 'nama' => $r['nama'], 'tema' => $info['nama'] ?: 'Tanpa Tema',
                    'platform' => (string) $r['platform'], 'tanggal_take' => (string) ($r['tanggal_take'] ?? ''), 'pic' => (string) ($r['pic'] ?? ''),
                    'insight' => $ins[(int) $r['id']] ?? 0, 'usulan' => $u['status'], 'alasan' => $u['alasan'],
                ];
            }
        }
        return $out;
    }

    /**
     * Terapkan pilihan pengguna: $pilih = ['s:12' => 'tayang', ...]. Hanya item yang masih NULL dan memenuhi prasyarat (tanpa pengecualian alasan).
     * @return array{diterapkan:int,dilewati:array<int,string>}
     */
    public function terapkan(array $pilih, array $scopesBoleh): array
    {
        $db = db_connect();
        $hasil = ['diterapkan' => 0, 'dilewati' => []];
        $now = date('Y-m-d H:i:s');
        foreach ($pilih as $key => $status) {
            if (! preg_match('/^([se]):(\d+)$/', (string) $key, $m) || ! isset(self::STATUS[$status])) continue;
            $scope = $m[1] === 'e' ? self::SCOPE_EVENT : self::SCOPE_STANDALONE;
            $id = (int) $m[2];
            if (! in_array($scope, $scopesBoleh, true)) { $hasil['dilewati'][] = "#{$id}: tidak ada hak ubah"; continue; }
            $item = $this->item($scope, $id);
            if (! $item) { $hasil['dilewati'][] = "#{$id}: tidak ditemukan"; continue; }
            $nama = $item['nama'];
            if ($item['status_konten']) { $hasil['dilewati'][] = "{$nama}: sudah diatur"; continue; }
            if (! empty($item['dari_paket'])) { $hasil['dilewati'][] = "{$nama}: masih Rencana dari paket"; continue; }
            if ($this->terkunci($scope, $item)) { $hasil['dilewati'][] = "{$nama}: Tema ditutup"; continue; }
            $dummy = false;
            if ($err = $this->prasyarat($scope, $item, $status, '', $dummy, false)) { $hasil['dilewati'][] = "{$nama}: " . $err['message']; continue; }
            $db->table(self::tabel($scope))->where('id', $id)->update(['status_konten' => $status, 'updated_at' => $now]);
            ActivityLog::write('update', 'creative_digital', $scope . ':' . $id, $nama, ['aksi' => 'status_konten', 'dari' => 'belum diatur', 'ke' => $status, 'massal' => true]);
            $hasil['diterapkan']++;
        }
        return $hasil;
    }
}
