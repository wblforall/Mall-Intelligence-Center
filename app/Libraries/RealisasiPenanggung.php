<?php

namespace App\Libraries;

/**
 * Pengeluaran satu event dipecah per penanggung (dept · pos budget) — keputusan pemilik 5 Nov 2026: laporan per event
 * menunjukkan seluruh pengeluaran event, termasuk biaya Creative yang ditanggung CCD, beserta pos siapa yang menanggungnya.
 * Budget Departemen tiap dept tetap hanya menghitung posnya sendiri, jadi tidak ada angka yang terhitung dua kali.
 *
 * Sumber (sama dengan total realisasi di Summary event):
 *  - Content Event: event_content_realisasi, pos = pos item / pos event (dept pemilik pos);
 *  - Creative: BTB FlowStore tertaut ke item Creative event (pos dari budget_realisasi CCD) + entri manual tanpa PO;
 *  - Loyalty & Dekorasi/VM: belum berpos — satu baris per modul.
 */
class RealisasiPenanggung
{
    /** @return list<array{penanggung:string,pos:?string,sumber:string,nilai:int}> urut nilai terbesar */
    public static function untuk(int $eventId, int $loyalty, int $vm): array
    {
        $db = db_connect();
        $out = [];
        $tambah = function (string $penanggung, ?string $pos, string $sumber, float $nilai) use (&$out) {
            if (round($nilai) == 0) return;
            $k = $penanggung . '|' . $pos . '|' . $sumber;
            if (! isset($out[$k])) $out[$k] = ['penanggung' => $penanggung, 'pos' => $pos, 'sumber' => $sumber, 'nilai' => 0];
            $out[$k]['nilai'] += (int) round($nilai);
        };

        // Content Event
        try {
            foreach ($db->query("SELECT d.name AS dept, bp.nama AS pos, SUM(r.nilai) AS n
                    FROM event_content_realisasi r JOIN event_content_items i ON i.id = r.content_item_id JOIN events e ON e.id = r.event_id
                    LEFT JOIN budget_pos bp ON bp.id = COALESCE(i.pos_id, e.pos_id) LEFT JOIN departments d ON d.id = bp.dept_id
                   WHERE r.event_id = ? GROUP BY d.name, bp.nama", [$eventId])->getResultArray() as $r) {
                $tambah($r['dept'] ?: 'Event & Promotion', $r['pos'], 'Content Event', (float) $r['n']);
            }
        } catch (\Throwable $e) { /* tabel lama tanpa pos: dilewati */ }

        // Creative
        $ccd = CreativeFsPo::deptCcd();
        $namaCcd = $ccd['name'] ?? 'Creative Concept & Design';
        try {
            $itemIds = array_column($db->table('event_creative_items')->select('id')->where('event_id', $eventId)->get()->getResultArray(), 'id');
            $entri = $db->table('event_creative_realisasi')->select('id, nilai')->where('event_id', $eventId)->get()->getResultArray();
            $berPo = [];
            if ($itemIds) {
                foreach ($db->query("SELECT d.name AS dept, bp.nama AS pos, SUM(x.nilai) AS n, GROUP_CONCAT(DISTINCT p.creative_realisasi_id) AS entri
                        FROM budget_fs_pemetaan p JOIN budget_realisasi x ON x.id = p.realisasi_id
                        LEFT JOIN budget_pos bp ON bp.id = x.pos_id LEFT JOIN departments d ON d.id = x.dept_id
                       WHERE p.creative_scope = 'event' AND p.creative_item_id IN (" . implode(',', array_map('intval', $itemIds)) . ")
                         AND p.status IN ('dicatat', 'perlu_ditinjau')
                       GROUP BY d.name, bp.nama", [])->getResultArray() as $r) {
                    $tambah($r['dept'] ?: $namaCcd, $r['pos'], 'Creative · PO FlowStore', (float) $r['n']);
                    foreach (array_filter(explode(',', (string) $r['entri'])) as $id) $berPo[(int) $id] = 1;
                }
            }
            $manual = 0;
            foreach ($entri as $e) if (! isset($berPo[(int) $e['id']])) $manual += (float) $e['nilai'];
            $tambah($namaCcd, null, 'Creative · dicatat manual', $manual);
        } catch (\Throwable $e) { /* migrasi PO belum jalan: dilewati */ }

        $tambah('Loyalty', null, 'Program Loyalty (voucher & hadiah)', $loyalty);
        $tambah('Dekorasi & VM', null, 'Dekorasi & VM', $vm);

        $out = array_values($out);
        usort($out, fn ($a, $b) => [$a['penanggung'], $b['nilai']] <=> [$b['penanggung'], $a['nilai']]);
        return $out;
    }
}
