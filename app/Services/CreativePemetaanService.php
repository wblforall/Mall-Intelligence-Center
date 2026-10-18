<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Models\CreativePemetaanLogModel;
use App\Models\CreativeTemaModel;

/**
 * Pemetaan item lama (yang belum punya Tema) ke Tema.
 *
 * Hanya USULAN: tak ada yang tertaut sebelum pengguna mencentang dan menekan
 * "Terapkan". Tiap penerapan dicatat per batch (creative_pemetaan_log +
 * ActivityLog) dan bisa dibatalkan selama item belum dipindahkan lagi.
 */
class CreativePemetaanService
{
    private const BULAN_NAMA = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        'agu' => 8, 'ags' => 8, 'sept' => 9, 'okt' => 10, 'des' => 12,
    ];

    /** Kata terlalu umum — tak dipakai untuk mencocokkan nama. */
    private const STOP = ['master', 'desain', 'design', 'konten', 'content', 'jakarta', 'promo', 'event', 'mall', 'one7e1even',
                          'festival', 'fest', 'untuk', 'dengan', 'yang', 'dari', 'feed', 'story', 'reels', 'video', 'poster', 'banner',
                          'pentacity', 'ewalk', 'walk', 'balikpapan', 'pengunjung', 'tenant', 'reward', 'exclusive', 'sticker', 'hanging', 'materi', 'lainnya'];

    // ───────────────────────────── Usulan ─────────────────────────────

    /**
     * @return array{groups:array,ragu:array,jumlahBelumBertema:int,batches:array}
     */
    public function usulan(): array
    {
        $db = db_connect();

        $items = $db->table('creative_items')->where('tema_id', null)->where('event_id', null)
                    ->orderBy('tipe')->orderBy('id')->get()->getResultArray();

        $events = $db->table('events')->select('id,name,mall,start_date,event_days')->get()->getResultArray();
        foreach ($events as &$e) {
            $e['end'] = $e['start_date'] ? date('Y-m-d', strtotime($e['start_date'] . ' +' . max(0, (int) $e['event_days'] - 1) . ' day')) : null;
            $e['kata'] = $this->kata($e['name']);
            $e['norm'] = $this->norm($e['name']);
        }
        unset($e);

        $temas = (new CreativeTemaModel())->findAll();

        // Tema Bulanan baru yang diusulkan dari item Design/Motion Graphic yang tak cocok dengan event
        $baru = [];      // hash => [nama, bulan, dari, bulanAwal, bulanAkhir]
        $perItem = [];   // item_id => list kandidat

        foreach ($items as $it) {
            $perItem[$it['id']] = [];
            if (! CreativeApprovalService::isDesain($it['tipe'])) continue;

            $match = $this->cocokEvent($it['nama'], $events);
            foreach ($match as $m) {
                $perItem[$it['id']][] = ['type' => 'event', 'id' => (int) $m['event']['id'], 'conf' => $m['conf'],
                                         'alasan' => $m['alasan'], 'pre' => $m['conf'] === 'tinggi' && count($match) === 1];
            }
            if (! $match) {
                [$ym, $dari] = $this->bulanItem($it, true);
                [$awal, $akhir] = $this->rentangNama($it['nama'], $ym);
                $h = sha1(mb_strtolower($it['nama']) . '|' . $ym);
                $baru[$h] = ['nama' => $it['nama'], 'bulan' => $ym, 'dari' => $dari, 'awal' => $awal, 'akhir' => $akhir];
                $perItem[$it['id']][] = ['type' => 'baru', 'id' => $h, 'conf' => 'tinggi',
                                         'alasan' => 'Nama tidak cocok dengan event; diusulkan jadi Tema Bulanan sendiri (' . $dari . ').', 'pre' => true];
            }
        }

        // Item non-Design: cocokkan nama & tanggal
        foreach ($items as $it) {
            if (CreativeApprovalService::isDesain($it['tipe'])) continue;
            $ref = $this->tanggalRef($it);
            $ym  = substr($ref, 0, 7);
            $kand = [];

            $nama = $this->cocokEvent($it['nama'], $events, 2);
            $namaIds = [];
            foreach ($nama as $m) { $namaIds[(int) $m['event']['id']] = $m; }

            $dlm = [];
            foreach ($events as $e) {
                if ($e['start_date'] && $ref >= $e['start_date'] && $ref <= $e['end']) $dlm[] = $e;
            }
            foreach ($dlm as $e) {
                $nm = $namaIds[(int) $e['id']] ?? null;
                if ($nm)                       { $conf = 'tinggi'; $al = 'Nama cocok dan tanggal (' . $this->tgl($ref) . ') dalam periode event.'; }
                elseif (count($dlm) === 1)     { $conf = 'sedang'; $al = 'Tanggal (' . $this->tgl($ref) . ') jatuh dalam periode event.'; }
                else                           { $conf = 'rendah'; $al = 'Tanggal (' . $this->tgl($ref) . ') beririsan dengan ' . count($dlm) . ' event.'; }
                $kand[] = ['type' => 'event', 'id' => (int) $e['id'], 'conf' => $conf, 'alasan' => $al, 'pre' => $conf === 'tinggi'];
                unset($namaIds[(int) $e['id']]);
            }
            foreach ($namaIds as $eid => $m) {   // nama cocok tapi tanggal di luar periode
                $kand[] = ['type' => 'event', 'id' => $eid, 'conf' => 'sedang', 'alasan' => $m['alasan'] . ' (tanggal di luar periode event).', 'pre' => false];
            }

            // Tema Bulanan (yang sudah ada & yang baru diusulkan): bulan dalam rentang
            foreach ($temas as $t) {
                if ($t['bulan'] === $ym) $kand[] = ['type' => 'tema', 'id' => (int) $t['id'], 'conf' => 'rendah', 'alasan' => 'Bulan sama dengan Tema Bulanan.', 'pre' => false];
            }
            foreach ($baru as $h => $b) {
                if ($ym >= $b['awal'] && $ym <= $b['akhir']) {
                    $kand[] = ['type' => 'baru', 'id' => $h, 'conf' => 'rendah', 'alasan' => 'Bulan masuk rentang “' . $b['nama'] . '”.', 'pre' => false];
                }
            }
            $perItem[$it['id']] = $kand;
        }

        // Kelompokkan per kandidat Tema
        $groups = []; $ragu = [];
        $evById = []; foreach ($events as $e) $evById[(int) $e['id']] = $e;
        $temaById = []; foreach ($temas as $t) $temaById[(int) $t['id']] = $t;

        foreach ($items as $it) {
            $kand = $perItem[$it['id']];
            if (! $kand) { $ragu[] = $it; continue; }
            foreach ($kand as $k) {
                $gk = $k['type'] . ':' . $k['id'];
                if (! isset($groups[$gk])) {
                    if ($k['type'] === 'event') {
                        $e = $evById[$k['id']];
                        $groups[$gk] = ['key' => $gk, 'type' => 'event', 'id' => $k['id'], 'nama' => $e['name'], 'bulan' => $e['start_date'] ? substr($e['start_date'], 0, 7) : '',
                                        'label' => 'Event · ' . $this->tgl($e['start_date']) . ' · ' . strtoupper($e['mall']), 'sort' => (string) $e['start_date'], 'items' => []];
                    } elseif ($k['type'] === 'tema') {
                        $t = $temaById[$k['id']];
                        $groups[$gk] = ['key' => $gk, 'type' => 'tema', 'id' => $k['id'], 'nama' => $t['nama'], 'bulan' => $t['bulan'],
                                        'label' => 'Tema Bulanan · ' . CreativeTemaService::bulanLabel($t['bulan']), 'sort' => $t['bulan'] . '-01', 'items' => []];
                    } else {
                        $b = $baru[$k['id']];
                        $groups[$gk] = ['key' => $gk, 'type' => 'baru', 'id' => $k['id'], 'nama' => $b['nama'], 'bulan' => $b['bulan'],
                                        'label' => 'Tema Bulanan BARU · bulan dari ' . $b['dari'], 'sort' => $b['bulan'] . '-00', 'items' => []];
                    }
                }
                $groups[$gk]['items'][] = ['item' => $it, 'conf' => $k['conf'], 'alasan' => $k['alasan'], 'pre' => $k['pre']];
            }
        }
        uasort($groups, function ($a, $b) {
            $order = ['event' => 0, 'baru' => 1, 'tema' => 2];
            return strcmp($b['sort'], $a['sort']) ?: ($order[$a['type']] <=> $order[$b['type']]);
        });

        // Ringkasan keyakinan & pembeda: item dengan >1 kandidat
        return [
            'groups' => array_values($groups),
            'ragu'   => $ragu,
            'jumlahBelumBertema' => count($items),
            'perItem' => $perItem,
            'batches' => $this->daftarBatch(),
        ];
    }

    // ───────────────────────────── Terapkan / batalkan ─────────────────────────────

    /**
     * @param string $type 'event' | 'tema' | 'baru'
     * @return array{ok:bool,message:string,batch?:string,n?:int}
     */
    public function terapkan(string $type, $id, string $nama, string $bulan, array $itemIds, array $user): array
    {
        $db = db_connect();
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        if (! $itemIds) return ['ok' => false, 'message' => 'Centang dulu item yang akan dipetakan.'];

        $temaDibuat = 0; $temaId = null; $eventId = null; $label = '';

        $db->transStart();
        if ($type === 'event') {
            $ev = $db->table('events')->select('id,name')->where('id', (int) $id)->get()->getRowArray();
            if (! $ev) { $db->transComplete(); return ['ok' => false, 'message' => 'Event tidak ditemukan.']; }
            $eventId = (int) $ev['id']; $label = 'Event ' . $ev['name'];
        } elseif ($type === 'tema') {
            $t = (new CreativeTemaModel())->find((int) $id);
            if (! $t) { $db->transComplete(); return ['ok' => false, 'message' => 'Tema tidak ditemukan.']; }
            $temaId = (int) $t['id']; $label = 'Tema ' . $t['nama'];
        } else {
            $nama = trim($nama);
            if ($nama === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
                $db->transComplete();
                return ['ok' => false, 'message' => 'Nama dan bulan Tema Bulanan baru wajib diisi.'];
            }
            $ada = (new CreativeTemaModel())->where('nama', $nama)->where('bulan', $bulan)->first();
            if ($ada) { $temaId = (int) $ada['id']; }
            else {
                $temaId = (int) (new CreativeTemaModel())->insert(['nama' => $nama, 'bulan' => $bulan, 'ditutup' => 0, 'dibuat_oleh' => (int) $user['id']]);
                $temaDibuat = 1;
            }
            $label = 'Tema Bulanan ' . $nama;
        }

        $batch = bin2hex(random_bytes(8));
        $log   = new CreativePemetaanLogModel();
        $now   = date('Y-m-d H:i:s');
        $n = 0;
        foreach ($itemIds as $iid) {
            $row = $db->table('creative_items')->select('id,tema_id,event_id')->where('id', $iid)->get()->getRowArray();
            if (! $row || $row['tema_id'] !== null || $row['event_id'] !== null) continue;   // hanya yang masih Tanpa Tema
            $db->table('creative_items')->where('id', $iid)->update(['tema_id' => $temaId, 'event_id' => $eventId]);
            $log->insert(['batch' => $batch, 'item_id' => $iid, 'tema_id_lama' => null, 'event_id_lama' => null,
                          'tema_id_baru' => $temaId, 'event_id_baru' => $eventId, 'tema_dibuat' => $n === 0 ? $temaDibuat : 0,
                          'oleh' => (int) $user['id'], 'created_at' => $now]);
            $n++;
        }
        $db->transComplete();

        if ($n === 0) {
            // tak ada yang dipetakan: batalkan Tema baru bila sempat dibuat
            if ($temaDibuat && $temaId) (new CreativeTemaModel())->delete($temaId);
            return ['ok' => false, 'message' => 'Tidak ada item yang dipetakan (mungkin sudah bertema).'];
        }

        ActivityLog::write('update', 'creative_tema', 'pemetaan:' . $batch, $label, ['aksi' => 'pemetaan', 'jumlah_item' => $n, 'item' => $itemIds]);

        return ['ok' => true, 'batch' => $batch, 'n' => $n, 'message' => $n . ' item dipetakan ke “' . trim(preg_replace('/^(Event|Tema Bulanan|Tema) /', '', $label)) . '”. Bisa dibatalkan dari riwayat pemetaan.'];
    }

    /** Kembalikan satu batch ke keadaan sebelumnya. */
    public function batalkan(string $batch, array $user): array
    {
        $db  = db_connect();
        $log = new CreativePemetaanLogModel();
        $rows = $log->where('batch', $batch)->where('dibatalkan_pada', null)->findAll();
        if (! $rows) return ['ok' => false, 'message' => 'Batch pemetaan tidak ditemukan atau sudah dibatalkan.'];

        $db->transStart();
        $pulih = 0; $lewat = 0; $temaDibuat = [];
        foreach ($rows as $r) {
            if ($r['tema_dibuat'] && $r['tema_id_baru']) $temaDibuat[] = (int) $r['tema_id_baru'];
            $it = $db->table('creative_items')->select('id,tema_id,event_id')->where('id', $r['item_id'])->get()->getRowArray();
            if (! $it) continue;
            // hanya bila item masih di tempat yang ditetapkan batch ini (belum dipindah orang lain)
            if ((string) $it['tema_id'] === (string) $r['tema_id_baru'] && (string) $it['event_id'] === (string) $r['event_id_baru']) {
                $db->table('creative_items')->where('id', $it['id'])->update(['tema_id' => $r['tema_id_lama'], 'event_id' => $r['event_id_lama']]);
                $pulih++;
            } else {
                $lewat++;
            }
        }
        $log->where('batch', $batch)->set(['dibatalkan_pada' => date('Y-m-d H:i:s')])->update();

        foreach (array_unique($temaDibuat) as $tid) {   // Tema yang dibuat batch ini ikut dibuang bila kosong
            $sisa = $db->table('creative_items')->where('tema_id', $tid)->countAllResults();
            if ($sisa === 0) (new CreativeTemaModel())->delete($tid);
        }
        $db->transComplete();

        ActivityLog::write('update', 'creative_tema', 'pemetaan:' . $batch, 'Batalkan pemetaan', ['aksi' => 'batal_pemetaan', 'dipulihkan' => $pulih, 'dilewati' => $lewat]);

        return ['ok' => true, 'message' => $pulih . ' item dikembalikan ke Tanpa Tema' . ($lewat ? " ({$lewat} dilewati karena sudah dipindah ke tema lain)" : '') . '.'];
    }

    /** Riwayat batch terbaru. */
    public function daftarBatch(int $limit = 8): array
    {
        $db = db_connect();
        $rows = $db->query("SELECT l.batch, COUNT(*) AS n, MIN(l.created_at) AS waktu, MAX(l.dibatalkan_pada) AS batal,
                                   MAX(l.tema_id_baru) AS tema_id, MAX(l.event_id_baru) AS event_id, u.name AS oleh
                            FROM creative_pemetaan_log l LEFT JOIN users u ON u.id = l.oleh
                            GROUP BY l.batch, u.name ORDER BY waktu DESC LIMIT " . (int) $limit)->getResultArray();
        foreach ($rows as &$r) {
            $r['tujuan'] = '';
            if ($r['event_id']) {
                $e = $db->table('events')->select('name')->where('id', $r['event_id'])->get()->getRowArray();
                $r['tujuan'] = 'Event ' . ($e['name'] ?? '#' . $r['event_id']);
            } elseif ($r['tema_id']) {
                $t = $db->table('creative_tema')->select('nama')->where('id', $r['tema_id'])->get()->getRowArray();
                $r['tujuan'] = 'Tema ' . ($t['nama'] ?? '(sudah dihapus)');
            }
        }
        return $rows;
    }

    // ───────────────────────────── Pembantu pencocokan ─────────────────────────────

    private function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
        return trim($s);
    }

    /** Kata bermakna (>=4 huruf, bukan kata umum). */
    private function kata(string $s): array
    {
        $out = [];
        foreach (explode(' ', $this->norm($s)) as $w) {
            if (mb_strlen($w) >= 4 && ! in_array($w, self::STOP, true)) $out[$w] = true;
        }
        return array_keys($out);
    }

    /** Event yang cocok menurut nama. @return array<int,array{event:array,conf:string,alasan:string}> */
    private function cocokEvent(string $nama, array $events, int $minKata = 1): array
    {
        $n = $this->norm($nama);
        $k = $this->kata($nama);
        $hasil = [];
        foreach ($events as $e) {
            // seluruh nama event ada di nama item (mis. "Pesta Bola ...")
            $enorm = $e['norm'];
            if ($enorm !== '' && (str_contains($n, $enorm) || str_contains($enorm, $n) && mb_strlen($n) >= 6)) {
                $hasil[] = ['event' => $e, 'conf' => 'tinggi', 'alasan' => 'Nama cocok dengan event “' . $e['name'] . '”.'];
                continue;
            }
            $sama = array_values(array_intersect($k, $e['kata']));
            if (count($sama) >= 2) {
                $hasil[] = ['event' => $e, 'conf' => 'tinggi', 'alasan' => 'Nama memuat “' . implode('”, “', $sama) . '” (dari nama event).'];
            } elseif (count($sama) === 1 && $minKata <= 1) {
                $hasil[] = ['event' => $e, 'conf' => 'sedang', 'alasan' => 'Nama memuat kata “' . $sama[0] . '” (dari nama event).'];
            }
        }
        // bila ada yang 'tinggi', buang yang hanya 'sedang' kecuali nama event sendiri kembar
        $tinggi = array_filter($hasil, fn($h) => $h['conf'] === 'tinggi');
        return $tinggi ?: $hasil;
    }

    private function tanggalRef(array $it): string
    {
        foreach (['tanggal_take', 'tanggal'] as $f) {
            if (! empty($it[$f]) && $it[$f] !== '0000-00-00') return $it[$f];
        }
        return substr((string) $it['created_at'], 0, 10);
    }

    /** Bulan (YYYY-MM) untuk Tema Bulanan baru dan asalnya. */
    private function bulanItem(array $it, bool $pakaiNama): array
    {
        $tahun = (int) substr($this->tanggalRef($it), 0, 4) ?: (int) date('Y');
        if ($pakaiNama) {
            $bulan = $this->bulanDariNama($it['nama']);
            if ($bulan) return [sprintf('%04d-%02d', $tahun, $bulan[0]), 'nama item'];
        }
        if (! empty($it['tanggal']) && $it['tanggal'] !== '0000-00-00') return [substr($it['tanggal'], 0, 7), 'tanggal item'];
        return [substr((string) $it['created_at'], 0, 7), 'tanggal dibuat'];
    }

    /** @return int[] nomor bulan yang disebut dalam nama */
    private function bulanDariNama(string $nama): array
    {
        $ada = [];
        foreach (explode(' ', $this->norm($nama)) as $w) {
            if (isset(self::BULAN_NAMA[$w])) $ada[] = self::BULAN_NAMA[$w];
        }
        return $ada;
    }

    /** Rentang bulan (awal, akhir) sebuah Tema Bulanan baru, mis. "Juni - Juli". */
    private function rentangNama(string $nama, string $ym): array
    {
        $b = $this->bulanDariNama($nama);
        $tahun = substr($ym, 0, 4);
        if (! $b) return [$ym, $ym];
        return [sprintf('%s-%02d', $tahun, min($b)), sprintf('%s-%02d', $tahun, max($b))];
    }

    private function tgl(?string $d): string
    {
        if (! $d) return '—';
        $m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($d);
        return date('j', $t) . ' ' . $m[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    }

    // ───────────────────────── Usulan ukuran Media Cetak ─────────────────────────

    /** Ekstrak pola "angka x angka (+ satuan)" dari teks; null bila tak ada. Hanya USULAN, tidak pernah ditulis otomatis. */
    public static function ekstrakUkuran(string $teks): ?string
    {
        $n = '(\d{1,4}(?:[.,]\d{1,2})?)';
        $u = '(?:(mm|cm|meter|m)\b)?';
        if (! preg_match('/' . $n . '\s*' . $u . '\s*(?:x|×|\*)\s*' . $n . '\s*' . $u . '/iu', $teks, $m)) return null;
        $unit = strtolower($m[4] ?? '') ?: strtolower($m[2] ?? '');
        $unit = $unit === 'meter' ? 'm' : $unit;
        $fmt = fn($v) => str_replace('.', ',', $v);
        return $fmt($m[1]) . '×' . $fmt($m[3]) . ($unit !== '' ? ' ' . $unit : '');
    }

    /** Item Media Cetak yang kolom ukurannya masih kosong, beserta usulan hasil ekstraksi dari nama / deskripsi / catatan. */
    public function usulanUkuran(): array
    {
        $rows = db_connect()->table('creative_items')->select('id,nama,deskripsi,catatan,ukuran,jenis_media')
            ->where('tipe', 'cetak')->where('dari_paket', 0)->groupStart()->where('ukuran', null)->orWhere('ukuran', '')->groupEnd()
            ->orderBy('id')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['saran'] = self::ekstrakUkuran($r['nama']) ?? self::ekstrakUkuran((string) $r['deskripsi']) ?? self::ekstrakUkuran((string) $r['catatan']);
        }
        return $rows;
    }

    /** Terapkan ukuran yang dikonfirmasi tim ([id => ukuran]); hanya item cetak yang ukurannya masih kosong. */
    public function terapkanUkuran(array $pairs, array $user): array
    {
        $db = db_connect(); $n = 0;
        foreach ($pairs as $id => $uk) {
            $uk = trim((string) $uk);
            if ($uk === '') continue;
            $row = $db->table('creative_items')->select('id,nama,ukuran')->where('id', (int) $id)->where('tipe', 'cetak')->where('dari_paket', 0)->get()->getRowArray();
            if (! $row || trim((string) $row['ukuran']) !== '') continue;   // (usulan tidak pernah memuat item Rencana)
            $db->table('creative_items')->where('id', $row['id'])->update(['ukuran' => mb_substr($uk, 0, 80), 'updated_at' => date('Y-m-d H:i:s')]);
            ActivityLog::write('update', 'creative_standalone', (string) $row['id'], $row['nama'], ['aksi' => 'ukuran_dari_usulan', 'ukuran' => $uk]);
            $n++;
        }
        return ['ok' => $n > 0, 'message' => $n > 0 ? $n . ' ukuran Media Cetak disimpan.' : 'Tidak ada ukuran yang disimpan (centang dan isi ukurannya dulu).'];
    }
}
