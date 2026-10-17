<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Models\CreativeTemaModel;
use App\Models\CreativeTemaStatusModel;

/**
 * Tema = induk semua item Creative.
 *
 *  - Tema EVENT  : baris tabel `events` (item dikerjakan di tab Creative event).
 *                  Penutupannya disimpan di `creative_tema_status`.
 *  - Tema BULANAN: tabel `creative_tema` (item dikerjakan di /creative).
 *
 * Tema yang DITUTUP mengunci semua itemnya (ubah, hapus, unggah, realisasi,
 * insight, dan alur persetujuan) sampai dibuka kembali oleh lead/admin.
 */
class CreativeTemaService
{
    /** Urutan jenis di semua tampilan. */
    public const TIPE_URUT = ['master_design', 'motion_graphic', 'digital', 'cetak', 'influencer', 'media_prescon'];

    public const PESAN_TEMA_TUTUP = 'Tema ini sudah ditutup sehingga materinya terkunci. Minta lead Creative atau admin membuka kembali tema bila perlu diubah.';

    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public static function bulanLabel(string $ym): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $ym, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) return 'Tanpa tanggal';
        return self::BULAN[(int) $m[2] - 1] . ' ' . $m[1];
    }

    // ───────────────────────────── Penguncian ─────────────────────────────

    public static function eventDitutup(int $eventId): bool
    {
        $row = db_connect()->table('creative_tema_status')->select('ditutup')->where('event_id', $eventId)->get()->getRowArray();
        return $row && (int) $row['ditutup'] === 1;
    }

    public static function temaDitutup(int $temaId): bool
    {
        $row = db_connect()->table('creative_tema')->select('ditutup')->where('id', $temaId)->get()->getRowArray();
        return $row && (int) $row['ditutup'] === 1;
    }

    /** Item standalone (creative_items) terkunci karena Tema-nya ditutup? */
    public static function itemTerkunci(?array $item): bool
    {
        if (! $item) return false;
        if (! empty($item['tema_id']))  return self::temaDitutup((int) $item['tema_id']);
        if (! empty($item['event_id'])) return self::eventDitutup((int) $item['event_id']);
        return false;
    }

    /**
     * Tandai item sebagai sudah dikerjakan: lencana "Rencana" (dari paket awal) hilang.
     * Aman dipanggil berulang; hanya menyentuh baris yang masih dari_paket = 1.
     */
    public static function tandaiDikerjakan(string $scope, int $itemId): void
    {
        db_connect()->table($scope === 'event' ? 'event_creative_items' : 'creative_items')
            ->where('id', $itemId)->where('dari_paket', 1)->update(['dari_paket' => 0]);
    }

    // ───────────────────────────── Tutup / buka ─────────────────────────────

    /**
     * @param string $jenis 'event' | 'bulanan'
     * @return array{ok:bool,message:string}
     */
    public function setDitutup(string $jenis, int $id, bool $tutup, array $user): array
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        if ($jenis === 'event') {
            $ev = $db->table('events')->select('id,name')->where('id', $id)->get()->getRowArray();
            if (! $ev) return ['ok' => false, 'message' => 'Event tidak ditemukan.'];
            $nama  = $ev['name'];
            $model = new CreativeTemaStatusModel();
            $row   = $model->where('event_id', $id)->first();
            $data  = ['event_id' => $id, 'ditutup' => $tutup ? 1 : 0,
                      'ditutup_oleh' => $tutup ? (int) $user['id'] : null, 'ditutup_pada' => $tutup ? $now : null];
            if ($row) $model->update($row['id'], $data); else $model->insert($data);
        } else {
            $model = new CreativeTemaModel();
            $tema  = $model->find($id);
            if (! $tema) return ['ok' => false, 'message' => 'Tema tidak ditemukan.'];
            $nama = $tema['nama'];
            $model->update($id, ['ditutup' => $tutup ? 1 : 0,
                                 'ditutup_oleh' => $tutup ? (int) $user['id'] : null, 'ditutup_pada' => $tutup ? $now : null]);
        }

        ActivityLog::write('update', 'creative_tema', $jenis . ':' . $id, $nama, ['aksi' => $tutup ? 'tutup_tema' : 'buka_tema']);

        return ['ok' => true, 'message' => $tutup
            ? 'Tema “' . $nama . '” ditutup. Materinya kini terkunci dan dipindah ke tab Terkunci.'
            : 'Tema “' . $nama . '” dibuka kembali.'];
    }

    // ───────────────────────────── Penyusunan daftar ─────────────────────────────

    /**
     * Susun kartu Tema untuk halaman /creative.
     *
     * @param array $sItems  item creative_items (sudah ber-_source 's')
     * @param array $eItems  item event_creative_items (ber-_source 'e')
     * @param array $ctx     realisasi/insight: sReal,eReal,sIns,eIns (keyed by item id)
     * @return array{none:array,aktif:array,terkunci:array,nTerkunci:int,nTema:int}
     */
    public function susun(array $sItems, array $eItems, array $ctx): array
    {
        $db = db_connect();

        $events = $db->table('events')->select('id,name,mall,start_date,event_days,status')->get()->getResultArray();
        $temas  = (new CreativeTemaModel())->orderBy('bulan', 'DESC')->orderBy('id', 'DESC')->findAll();
        $stat   = [];
        foreach ((new CreativeTemaStatusModel())->findAll() as $r) $stat[(int) $r['event_id']] = $r;

        $userIds = [];
        foreach ($stat as $r)  if (! empty($r['ditutup_oleh'])) $userIds[] = (int) $r['ditutup_oleh'];
        foreach ($temas as $r) if (! empty($r['ditutup_oleh'])) $userIds[] = (int) $r['ditutup_oleh'];
        $names = [];
        if ($userIds) {
            foreach ($db->table('users')->select('id,name')->whereIn('id', array_unique($userIds))->get()->getResultArray() as $u) $names[(int) $u['id']] = $u['name'];
        }

        $cards = [];
        foreach ($events as $ev) {
            $s   = $stat[(int) $ev['id']] ?? null;
            $ym  = $ev['start_date'] ? substr($ev['start_date'], 0, 7) : '';
            $end = $ev['start_date'] ? date('Y-m-d', strtotime($ev['start_date'] . ' +' . max(0, (int) $ev['event_days'] - 1) . ' day')) : '';
            $cards['e' . $ev['id']] = [
                'key' => 'e' . $ev['id'], 'kind' => 'event', 'id' => (int) $ev['id'], 'nama' => $ev['name'],
                'bulan' => $ym, 'mall' => $ev['mall'], 'start' => $ev['start_date'], 'end' => $end,
                'deskripsi' => '', 'closed' => $s && (int) $s['ditutup'] === 1,
                'closed_by' => $s ? ($names[(int) ($s['ditutup_oleh'] ?? 0)] ?? '') : '', 'closed_at' => $s['ditutup_pada'] ?? null,
                'items' => [],
            ];
        }
        foreach ($temas as $t) {
            $cards['t' . $t['id']] = [
                'key' => 't' . $t['id'], 'kind' => 'bulanan', 'id' => (int) $t['id'], 'nama' => $t['nama'],
                'bulan' => $t['bulan'], 'mall' => '', 'start' => null, 'end' => null,
                'deskripsi' => (string) ($t['deskripsi'] ?? ''), 'closed' => (int) $t['ditutup'] === 1,
                'closed_by' => $names[(int) ($t['ditutup_oleh'] ?? 0)] ?? '', 'closed_at' => $t['ditutup_pada'],
                'items' => [],
            ];
        }
        $none = [
            'key' => 'none', 'kind' => 'none', 'id' => 0, 'nama' => 'Tanpa Tema', 'bulan' => '', 'mall' => '', 'start' => null, 'end' => null,
            'deskripsi' => 'Item yang belum ditautkan ke Tema. Pindahkan lewat menu Ubah item atau panel Susun Tema.',
            'closed' => false, 'closed_by' => '', 'closed_at' => null, 'items' => [],
        ];

        foreach ($sItems as $it) {
            $k = ! empty($it['tema_id']) ? 't' . $it['tema_id'] : (! empty($it['event_id']) ? 'e' . $it['event_id'] : 'none');
            if ($k === 'none' || ! isset($cards[$k])) { $none['items'][] = $it; continue; }
            $cards[$k]['items'][] = $it;
        }
        foreach ($eItems as $it) {
            $k = 'e' . $it['event_id'];
            if (! isset($cards[$k])) { $none['items'][] = $it; continue; }
            $cards[$k]['items'][] = $it;
        }

        foreach ($cards as &$c) $this->hitung($c, $ctx);
        unset($c);
        $this->hitung($none, $ctx);

        // Kelompokkan per bulan
        $aktif = []; $terkunci = []; $nTerkunci = 0;
        foreach ($cards as $c) {
            $ym = $c['bulan'] ?: '0000-00';
            if ($c['closed']) { $terkunci[$ym][] = $c; $nTerkunci++; } else { $aktif[$ym][] = $c; }
        }

        return [
            'none'      => $none,
            'aktif'     => $this->kelompokBulan($aktif),
            'terkunci'  => $this->kelompokBulan($terkunci),
            'nTerkunci' => $nTerkunci,
            'nTema'     => count($cards),
            'cards'     => $cards,
        ];
    }

    private function kelompokBulan(array $byMonth): array
    {
        krsort($byMonth);                       // terbaru di atas; '0000-00' (tanpa tanggal) paling bawah
        $out = [];
        foreach ($byMonth as $ym => $list) {
            usort($list, function ($a, $b) {
                // event dulu (mulai paling awal bulan itu di atas), lalu tema bulanan
                if ($a['kind'] !== $b['kind']) return $a['kind'] === 'event' ? -1 : 1;
                return strcmp((string) $b['start'], (string) $a['start']) ?: strcasecmp($a['nama'], $b['nama']);
            });
            $out[] = [
                'bulan'     => $ym,
                'label'     => self::bulanLabel($ym),
                'cards'     => $list,
                'budget'    => array_sum(array_column($list, 'budget')),
                'realisasi' => array_sum(array_column($list, 'realisasi')),
            ];
        }
        return $out;
    }

    /** Statistik satu kartu Tema + kelompok item per jenis. */
    private function hitung(array &$c, array $ctx): void
    {
        // Item "Rencana" (dari paket awal, belum dikerjakan) dihitung terpisah agar tidak merusak statistik.
        $c['nAll']     = count($c['items']);
        $c['nRencana'] = count(array_filter($c['items'], fn($it) => ! empty($it['dari_paket'])));
        $c['n']        = $c['nAll'] - $c['nRencana'];
        $c['selesai'] = 0; $c['budget'] = 0; $c['realisasi'] = 0; $c['fs'] = 0; $c['reach'] = 0; $c['impr'] = 0;
        $c['byTipe'] = [];

        foreach ($c['items'] as &$it) {          // tandai asal Tema pada tiap item (dipakai kartu & pencarian)
            $it['_temaKey'] = $c['key']; $it['_temaNama'] = $c['nama']; $it['_temaTutup'] = ! empty($c['closed']);
        }
        unset($it);

        foreach ($c['items'] as $it) {
            if (! empty($it['dari_paket'])) { $c['byTipe'][$it['tipe']][] = $it; continue; }
            $iid = (int) $it['id'];
            $s   = $it['_source'] === 's';
            $real = ($s ? ($ctx['sReal'][$iid] ?? null) : ($ctx['eReal'][$iid] ?? null));
            $ins  = ($s ? ($ctx['sIns'][$iid] ?? null)  : ($ctx['eIns'][$iid] ?? null));

            $c['budget']    += (int) ($it['budget'] ?? 0);
            $c['realisasi'] += (int) ($real['total'] ?? 0);
            $c['fs']        += (int) ($real['fs_total'] ?? 0);   // bagian yang berasal dari BTB FlowStore tertaut (sudah termasuk di realisasi)
            $c['reach']     += (int) ($ins['max_reach'] ?? 0);
            $c['impr']      += (int) ($ins['max_impressions'] ?? 0);
            if ($this->selesai($it, $s, $real, $ins)) $c['selesai']++;
            $c['byTipe'][$it['tipe']][] = $it;
        }
    }

    /**
     * Definisi "selesai" per jenis:
     *  - Design / Motion Graphic : status approved.
     *  - Konten Digital          : dicentang selesai, atau sudah tayang (ada data insight).
     *  - Cetak / Influencer / Prescon : dicentang selesai (item event: ada realisasi, karena tak punya tanda selesai).
     */
    private function selesai(array $it, bool $standalone, ?array $real, ?array $ins): bool
    {
        if (CreativeApprovalService::isDesain($it['tipe'])) return ($it['status'] ?? '') === 'approved';
        if (! empty($it['is_closed'])) return true;
        if ($it['tipe'] === 'digital') return ! empty($ins['entries']);
        if (! $standalone) return ($real['total'] ?? 0) > 0;
        return false;
    }
}
